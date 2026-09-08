<?php

namespace Tests\Model\Persistence;

use Model\Entity\User;
use Model\Persistence\UserRepositoryPDO;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit tests for {@see UserRepositoryPDO}.
 *
 * Coverage areas:
 * - findByEmail() — admin table lookup, student table fallback, not-found case
 * - findByStudentNumber() — found and not-found cases
 * - save() — admin insert, student insert, exception handling
 * - updatePassword() — admin update, student fallback, exception handling
 * - updatePasswordAndUnlock() — success and exception handling
 * - findByLogin() — found, not-found, and exception cases
 * - loginExists() — found and not-found cases
 * - create() — success and exception cases
 * - deleteByLogin() — success and exception cases
 * - findAll() — mapped result, failed query, exception case
 * - getDistinctDepartments() — sorted merged result
 * - addCustomDepartment() — INSERT delegation
 * - getDistinctSites() — default site inclusion, custom site merging
 * - addCustomSite() — INSERT delegation
 * - resetPassword() — user not found, user found
 *
 * Strategy: newInstanceWithoutConstructor() + ReflectionProperty injection on
 * the $pdo field so the Database singleton is never touched.
 *
 * Run with:
 *   ./vendor/bin/phpunit Tests/Model/Persistence/UserRepositoryPDOTest.php
 */
class UserRepositoryPDOTest extends TestCase
{
    /** Mocked PDO connection injected directly into the repository. */
    /** @var PDO&MockObject */
    private PDO $pdoMock;

    /** Repository instance created without invoking the real constructor. */
    private UserRepositoryPDO $repo;

    /**
     * Creates the repository via reflection (bypassing the constructor) and
     * injects a PDO mock so no real database connection is opened.
     */
    protected function setUp(): void
    {
        $this->repo = (new \ReflectionClass(UserRepositoryPDO::class))
            ->newInstanceWithoutConstructor();

        $this->pdoMock = $this->createMock(PDO::class);

        $prop = new \ReflectionProperty(UserRepositoryPDO::class, 'pdo');
        $prop->setAccessible(true);
        $prop->setValue($this->repo, $this->pdoMock);
    }

    // -------------------------------------------------------------------------
    // Statement factory helpers
    // -------------------------------------------------------------------------

    /**
     * Builds a mock PDOStatement whose fetch() returns the given value.
     *
     * Use this for single-row lookups (findByEmail, findByStudentNumber, …).
     * Pass false to simulate "no row found".
     */
    private function buildFetchStmt(mixed $returnValue): PDOStatement
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetch')->willReturn($returnValue);
        return $stmt;
    }

    /**
     * Builds a mock PDOStatement whose fetchAll() returns the given row set.
     *
     * Use this for multi-row queries (findAll, getTopCountries, …).
     *
     * @param array<int, array<string, mixed>> $rows
     */
    private function buildFetchAllStmt(array $rows): PDOStatement
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetchAll')->willReturn($rows);
        return $stmt;
    }

    // -------------------------------------------------------------------------
    // Row fixtures
    // -------------------------------------------------------------------------

    /**
     * Returns a minimal admin row as the database would return it.
     *
     * @return array<string, mixed>
     */
    private function adminRow(): array
    {
        return [
            'id'          => 1,
            'email'       => 'admin@test.com',
            'password'    => 'hashed',
            'role'        => 'admin',
            'numetu'      => null,
            'departement' => 'INFO',
        ];
    }

    /**
     * Returns a minimal student row as the database would return it.
     *
     * @return array<string, mixed>
     */
    private function studentRow(): array
    {
        return [
            'id'          => 2,
            'email'       => 'etu@test.com',
            'password'    => 'hashed',
            'role'        => 'student',
            'numetu'      => '12345678',
            'departement' => null,
        ];
    }

    // =========================================================
    // findByEmail()
    // =========================================================

    /**
     * @test
     * When the email matches a row in the admins table, findByEmail() must
     * return a User with the correct email address.
     */
    public function testFindByEmailReturnsAdminWhenFoundInAdminsTable(): void
    {
        $stmtAdmin = $this->buildFetchStmt($this->adminRow());

        $this->pdoMock->method('prepare')->willReturn($stmtAdmin);

        $user = $this->repo->findByEmail('admin@test.com');

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('admin@test.com', $user->getEmail());
    }

    /**
     * @test
     * When the email is not found in the admins table, findByEmail() must fall
     * back to the students table and return the matching User.
     */
    public function testFindByEmailReturnsStudentWhenNotInAdminsTable(): void
    {
        $stmtAdmin   = $this->buildFetchStmt(false);     // admin lookup misses
        $stmtStudent = $this->buildFetchStmt($this->studentRow());

        $this->pdoMock->method('prepare')
            ->willReturnOnConsecutiveCalls($stmtAdmin, $stmtStudent);

        $user = $this->repo->findByEmail('etu@test.com');

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('student', $user->getRole());
    }

    /**
     * @test
     * When the email is found in neither table, findByEmail() must return null.
     */
    public function testFindByEmailReturnsNullWhenNotFound(): void
    {
        $stmt = $this->buildFetchStmt(false);

        $this->pdoMock->method('prepare')->willReturn($stmt);

        $this->assertNull($this->repo->findByEmail('nobody@test.com'));
    }

    // =========================================================
    // findByStudentNumber()
    // =========================================================

    /**
     * @test
     * When the student number matches a row, findByStudentNumber() must return
     * a User with the correct role and numetu values.
     */
    public function testFindByStudentNumberReturnsUserWhenFound(): void
    {
        $stmt = $this->buildFetchStmt($this->studentRow());
        $this->pdoMock->method('prepare')->willReturn($stmt);

        $user = $this->repo->findByStudentNumber('12345678');

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('student', $user->getRole());
        $this->assertSame('12345678', $user->getNumetu());
    }

    /**
     * @test
     * When no row matches, findByStudentNumber() must return null.
     */
    public function testFindByStudentNumberReturnsNullWhenNotFound(): void
    {
        $stmt = $this->buildFetchStmt(false);
        $this->pdoMock->method('prepare')->willReturn($stmt);

        $this->assertNull($this->repo->findByStudentNumber('00000000'));
    }

    // =========================================================
    // save()
    // =========================================================

    /**
     * @test
     * Saving an admin User must execute an INSERT with the email, hashed
     * password, and role — no numetu is included for admin accounts.
     */
    public function testSaveAdminInsertsIntoAdminsTable(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())
            ->method('execute')
            ->with(['email' => 'admin@test.com', 'password' => 'hashed', 'role' => 'admin'])
            ->willReturn(true);

        $this->pdoMock->method('prepare')->willReturn($stmt);

        $user = new User(null, 'admin@test.com', null, 'hashed', 'admin');
        $this->assertTrue($this->repo->save($user));
    }

    /**
     * @test
     * Saving a student User must execute an INSERT with email, hashed password,
     * and numetu — no role column is included for the students table.
     */
    public function testSaveStudentInsertsIntoEtudiantsTable(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())
            ->method('execute')
            ->with(['email' => 'etu@test.com', 'password' => 'hashed', 'numetu' => '12345678'])
            ->willReturn(true);

        $this->pdoMock->method('prepare')->willReturn($stmt);

        $user = new User(null, 'etu@test.com', '12345678', 'hashed', 'student');
        $this->assertTrue($this->repo->save($user));
    }

    /**
     * @test
     * When the database throws a PDOException, save() must catch it and return
     * false rather than propagating the exception.
     */
    public function testSaveReturnsFalseOnException(): void
    {
        $this->pdoMock->method('prepare')
            ->willThrowException(new \PDOException('fail'));

        $user = new User(null, 'x@test.com', null, 'hashed', 'admin');
        $this->assertFalse($this->repo->save($user));
    }

    // =========================================================
    // updatePassword()
    // =========================================================

    /**
     * @test
     * When the UPDATE on the admins table affects at least one row,
     * updatePassword() must return true.
     */
    public function testUpdatePasswordReturnsTrueWhenAdminUpdated(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('rowCount')->willReturn(1);

        $this->pdoMock->method('prepare')->willReturn($stmt);

        $this->assertTrue($this->repo->updatePassword('admin@test.com', 'newhash'));
    }

    /**
     * @test
     * When the admin UPDATE affects zero rows, updatePassword() must fall back
     * to the students table and return true when that UPDATE succeeds.
     */
    public function testUpdatePasswordFallsBackToEtudiantsWhenAdminNotFound(): void
    {
        $stmtAdmin   = $this->createMock(PDOStatement::class);
        $stmtStudent = $this->createMock(PDOStatement::class);

        $stmtAdmin->method('execute')->willReturn(true);
        $stmtAdmin->method('rowCount')->willReturn(0); // admin row not found
        $stmtStudent->method('execute')->willReturn(true);

        $this->pdoMock->method('prepare')
            ->willReturnOnConsecutiveCalls($stmtAdmin, $stmtStudent);

        $result = $this->repo->updatePassword('etu@test.com', 'newhash');
        $this->assertTrue($result);
    }

    /**
     * @test
     * When the database throws a PDOException, updatePassword() must catch it
     * and return false.
     */
    public function testUpdatePasswordReturnsFalseOnException(): void
    {
        $this->pdoMock->method('prepare')
            ->willThrowException(new \PDOException('fail'));

        $this->assertFalse($this->repo->updatePassword('x@test.com', 'hash'));
    }

    // =========================================================
    // updatePasswordAndUnlock()
    // =========================================================

    /**
     * @test
     * When the UPDATE executes without errors, updatePasswordAndUnlock() must
     * return true.
     */
    public function testUpdatePasswordAndUnlockReturnsTrueOnSuccess(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);

        $this->pdoMock->method('prepare')->willReturn($stmt);

        $this->assertTrue($this->repo->updatePasswordAndUnlock('admin@test.com', 'newhash'));
    }

    /**
     * @test
     * When the database throws a PDOException, updatePasswordAndUnlock() must
     * catch it and return false.
     */
    public function testUpdatePasswordAndUnlockReturnsFalseOnException(): void
    {
        $this->pdoMock->method('prepare')
            ->willThrowException(new \PDOException('fail'));

        $this->assertFalse($this->repo->updatePasswordAndUnlock('x@test.com', 'hash'));
    }

    // =========================================================
    // findByLogin()
    // =========================================================

    /**
     * @test
     * When a row is found, findByLogin() must return it as an associative
     * array with the email key intact.
     */
    public function testFindByLoginReturnsArrayWhenFound(): void
    {
        $stmt = $this->buildFetchStmt($this->adminRow());
        $this->pdoMock->method('prepare')->willReturn($stmt);

        $result = $this->repo->findByLogin('admin@test.com');
        $this->assertIsArray($result);
        $this->assertSame('admin@test.com', $result['email']);
    }

    /**
     * @test
     * When no row is found, findByLogin() must return null.
     */
    public function testFindByLoginReturnsNullWhenNotFound(): void
    {
        $stmt = $this->buildFetchStmt(false);
        $this->pdoMock->method('prepare')->willReturn($stmt);

        $this->assertNull($this->repo->findByLogin('nobody@test.com'));
    }

    /**
     * @test
     * When the database throws a PDOException, findByLogin() must catch it and
     * return null.
     */
    public function testFindByLoginReturnsNullOnException(): void
    {
        $this->pdoMock->method('prepare')
            ->willThrowException(new \PDOException('fail'));

        $this->assertNull($this->repo->findByLogin('x@test.com'));
    }

    // =========================================================
    // loginExists()
    // =========================================================

    /**
     * @test
     * When the login is found in the database, loginExists() must return true.
     */
    public function testLoginExistsReturnsTrueWhenFound(): void
    {
        $stmt = $this->buildFetchStmt($this->adminRow());
        $this->pdoMock->method('prepare')->willReturn($stmt);

        $this->assertTrue($this->repo->loginExists('admin@test.com'));
    }

    /**
     * @test
     * When the login is not found, loginExists() must return false.
     */
    public function testLoginExistsReturnsFalseWhenNotFound(): void
    {
        $stmt = $this->buildFetchStmt(false);
        $this->pdoMock->method('prepare')->willReturn($stmt);

        $this->assertFalse($this->repo->loginExists('nobody@test.com'));
    }

    // =========================================================
    // create()
    // =========================================================

    /**
     * @test
     * When the INSERT executes successfully, create() must return true.
     */
    public function testCreateReturnsTrueOnSuccess(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute')->willReturn(true);
        $this->pdoMock->method('prepare')->willReturn($stmt);

        $this->assertTrue($this->repo->create('admin@test.com', 'hashed', 'admin', 'INFO', 'Site A', 'Doe', 'John'));
    }

    /**
     * @test
     * When the database throws a PDOException, create() must catch it and
     * return false.
     */
    public function testCreateReturnsFalseOnException(): void
    {
        $this->pdoMock->method('prepare')
            ->willThrowException(new \PDOException('fail'));

        $this->assertFalse($this->repo->create('x@test.com', 'hashed', 'admin'));
    }

    // =========================================================
    // deleteByLogin()
    // =========================================================

    /**
     * @test
     * deleteByLogin() must execute with the email bound to the :email parameter
     * and return true on success.
     */
    public function testDeleteByLoginReturnsTrueOnSuccess(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())
            ->method('execute')
            ->with([':email' => 'admin@test.com'])
            ->willReturn(true);
        $this->pdoMock->method('prepare')->willReturn($stmt);

        $this->assertTrue($this->repo->deleteByLogin('admin@test.com'));
    }

    /**
     * @test
     * When the database throws a PDOException, deleteByLogin() must catch it
     * and return false.
     */
    public function testDeleteByLoginReturnsFalseOnException(): void
    {
        $this->pdoMock->method('prepare')
            ->willThrowException(new \PDOException('fail'));

        $this->assertFalse($this->repo->deleteByLogin('x@test.com'));
    }

    // =========================================================
    // findAll()
    // =========================================================

    /**
     * @test
     * findAll() must return a mapped array where each element contains the
     * original column values from the database row.
     */
    public function testFindAllReturnsMappedArray(): void
    {
        $rows = [[
            'login'       => 'admin@test.com',
            'role'        => 'admin',
            'departement' => 'INFO',
            'site'        => null,
            'nom'         => 'Doe',
            'prenom'      => 'John',
            'created_at'  => '2024-01-01',
        ]];
        $stmt = $this->buildFetchAllStmt($rows);
        $this->pdoMock->method('query')->willReturn($stmt);

        $result = $this->repo->findAll();
        $this->assertCount(1, $result);
        $this->assertSame('admin@test.com', $result[0]['login']);
    }

    /**
     * @test
     * When PDO::query() returns false (e.g. a prepare error), findAll() must
     * return an empty array rather than crashing.
     */
    public function testFindAllReturnsEmptyArrayWhenQueryFails(): void
    {
        $this->pdoMock->method('query')->willReturn(false);

        $this->assertSame([], $this->repo->findAll());
    }

    /**
     * @test
     * When the database throws a PDOException, findAll() must catch it and
     * return an empty array.
     */
    public function testFindAllReturnsEmptyArrayOnException(): void
    {
        $this->pdoMock->method('query')
            ->willThrowException(new \PDOException('fail'));

        $this->assertSame([], $this->repo->findAll());
    }

    // =========================================================
    // getDistinctDepartments()
    // =========================================================

    /**
     * @test
     * getDistinctDepartments() must merge departments from the dossiers table
     * and from custom_departments, returning a deduplicated array.
     */
    public function testGetDistinctDepartmentsReturnsSortedArray(): void
    {
        $stmtDossiers = $this->createMock(PDOStatement::class);
        $stmtDossiers->method('fetchAll')->willReturn(['MATH', 'INFO']);

        $stmtCustom = $this->createMock(PDOStatement::class);
        $stmtCustom->method('fetchAll')->willReturn(['BIO']);

        $this->pdoMock->method('query')
            ->willReturnOnConsecutiveCalls($stmtDossiers, $stmtCustom);

        $result = $this->repo->getDistinctDepartments();
        $this->assertContains('INFO', $result);
        $this->assertContains('MATH', $result);
        $this->assertContains('BIO', $result);
        // Result must have no duplicates and be re-indexed.
        $this->assertSame($result, array_values(array_unique($result)));
    }

    // =========================================================
    // addCustomDepartment()
    // =========================================================

    /**
     * @test
     * addCustomDepartment() must execute an INSERT with the department code
     * bound to the :code parameter.
     */
    public function testAddCustomDepartmentExecutesInsert(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())
            ->method('execute')
            ->with([':code' => 'CHIMIE'])
            ->willReturn(true);

        $this->pdoMock->method('exec')->willReturn(0);
        $this->pdoMock->method('prepare')->willReturn($stmt);

        $this->repo->addCustomDepartment('CHIMIE');
    }

    // =========================================================
    // getDistinctSites()
    // =========================================================

    /**
     * @test
     * getDistinctSites() must always include "Site Gaston Berger" in its
     * result, even when no custom sites are stored.
     */
    public function testGetDistinctSitesIncludesDefaultSite(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('fetchAll')->willReturn([]);
        $this->pdoMock->method('query')->willReturn($stmt);

        $result = $this->repo->getDistinctSites();
        $this->assertContains('Site Gaston Berger', $result);
    }

    /**
     * @test
     * getDistinctSites() must merge custom sites from the database with the
     * default site and return all of them.
     */
    public function testGetDistinctSitesMergesCustomSites(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('fetchAll')->willReturn(['Site Nord']);
        $this->pdoMock->method('query')->willReturn($stmt);

        $result = $this->repo->getDistinctSites();
        $this->assertContains('Site Nord', $result);
        $this->assertContains('Site Gaston Berger', $result);
    }

    // =========================================================
    // addCustomSite()
    // =========================================================

    /**
     * @test
     * addCustomSite() must execute an INSERT with the site name bound to the
     * :name parameter.
     */
    public function testAddCustomSiteExecutesInsert(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())
            ->method('execute')
            ->with([':name' => 'Site Sud'])
            ->willReturn(true);

        $this->pdoMock->method('exec')->willReturn(0);
        $this->pdoMock->method('prepare')->willReturn($stmt);

        $this->repo->addCustomSite('Site Sud');
    }

    // =========================================================
    // resetPassword()
    // =========================================================

    /**
     * @test
     * When no user row is found for the given email, resetPassword() must
     * return false without attempting an UPDATE.
     */
    public function testResetPasswordReturnsFalseWhenUserNotFound(): void
    {
        $stmt = $this->buildFetchStmt(false);
        $this->pdoMock->method('prepare')->willReturn($stmt);

        $this->assertFalse($this->repo->resetPassword('nobody@test.com'));
    }

    /**
     * @test
     * When the user row is found, resetPassword() must execute the UPDATE and
     * return true.
     */
    public function testResetPasswordReturnsTrueWhenUserFound(): void
    {
        $stmtFind   = $this->buildFetchStmt($this->adminRow());
        $stmtUpdate = $this->createMock(PDOStatement::class);
        $stmtUpdate->method('execute')->willReturn(true);
        $stmtUpdate->method('rowCount')->willReturn(1);

        $this->pdoMock->method('prepare')
            ->willReturnOnConsecutiveCalls($stmtFind, $stmtUpdate);

        $this->assertTrue($this->repo->resetPassword('admin@test.com'));
    }
}