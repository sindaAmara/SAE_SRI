<?php

namespace Tests\Model\Persistence;

use Model\Entity\FolderStats;
use Model\Entity\GenderStats;
use Model\Persistence\FolderRepositoryPDO;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit tests for {@see FolderRepositoryPDO}.
 *
 * Coverage areas:
 * - getGlobalStats() — exception fallback (zeros)
 * - getGenderStats() — exception fallback (zeros)
 * - getTopCountries() — normal result, exception fallback
 * - getDepartmentStats() — normal result, exception fallback
 * - getIncomingOutgoingStats() — exception fallback (zeros)
 * - getContinentStats() — exception fallback (empty)
 * - getEuropeVsNonEuropeStats() — exception fallback (zeros)
 * - getZoneStats() — normal result with integer cast
 * - inferContinent() — Europe zone, known non-Europe countries, unknown country
 * - findAll() — normal result, exception fallback
 * - findByNumEtu() — exception fallback (null)
 * - create() — success, exception fallback
 * - setStatus() — valid statuses, invalid status rejection
 * - setAvisChef() — accepte, null, invalid value
 * - getAllDepartements() — normal result, failed query
 * - enregistrerValidation() — success, invalid date, null date/comment
 * - searchWithPagination() — exception fallback
 *
 * Strategy: newInstanceWithoutConstructor() + ReflectionProperty injection on
 * the $db field so the Database singleton is never touched.
 *
 * Run with:
 *   ./vendor/bin/phpunit Tests/Model/Persistence/FolderRepositoryPDOTest.php
 */
class FolderRepositoryPDOTest extends TestCase
{
    /** Mocked PDO connection injected directly into the repository. */
    /** @var PDO&MockObject */
    private PDO $pdoMock;

    /** Repository instance created without invoking the real constructor. */
    private FolderRepositoryPDO $repo;

    /**
     * Creates the repository via reflection (bypassing the constructor) and
     * injects a PDO mock so no real database connection is opened.
     *
     * Note: the injected property is named $db (not $pdo) in this repository.
     */
    protected function setUp(): void
    {
        $this->repo = (new \ReflectionClass(FolderRepositoryPDO::class))
            ->newInstanceWithoutConstructor();

        $this->pdoMock = $this->createMock(PDO::class);

        $prop = new \ReflectionProperty(FolderRepositoryPDO::class, 'db');
        $prop->setAccessible(true);
        $prop->setValue($this->repo, $this->pdoMock);
    }

    // -------------------------------------------------------------------------
    // Statement factory helper
    // -------------------------------------------------------------------------

    /**
     * Builds a mock PDOStatement whose fetchAll() returns the given row set.
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

    // =========================================================
    // getGlobalStats()
    // =========================================================

    /**
     * @test
     * When the database throws a PDOException, getGlobalStats() must return a
     * zero-filled FolderStats object rather than propagating the exception.
     */
    public function testGetGlobalStatsReturnsZerosOnPDOException(): void
    {
        $this->pdoMock->method('query')->willThrowException(new \PDOException('fail'));

        $stats = $this->repo->getGlobalStats();
        $this->assertSame(0, $stats->getTotal());
        $this->assertSame(0, $stats->getCompleted());
    }

    // =========================================================
    // getGenderStats()
    // =========================================================

    /**
     * @test
     * When the database throws a PDOException, getGenderStats() must return a
     * zero-filled GenderStats object rather than propagating the exception.
     */
    public function testGetGenderStatsReturnsZerosOnPDOException(): void
    {
        $this->pdoMock->method('query')->willThrowException(new \PDOException('fail'));

        $stats = $this->repo->getGenderStats();
        $this->assertSame(0, $stats->getMale());
        $this->assertSame(0, $stats->getFemale());
    }

    // =========================================================
    // getTopCountries()
    // =========================================================

    /**
     * @test
     * getTopCountries() must return the rows fetched from the database in the
     * order they were returned, preserving the name and count values.
     */
    public function testGetTopCountriesReturnsArray(): void
    {
        $rows = [
            ['name' => 'Allemagne', 'count' => 8],
            ['name' => 'Espagne',   'count' => 5],
        ];
        $stmt = $this->buildFetchAllStmt($rows);
        $this->pdoMock->method('prepare')->willReturn($stmt);

        $result = $this->repo->getTopCountries(5);
        $this->assertCount(2, $result);
        $this->assertSame('Allemagne', $result[0]['name']);
    }

    /**
     * @test
     * When the database throws a PDOException, getTopCountries() must return
     * an empty array rather than propagating the exception.
     */
    public function testGetTopCountriesReturnsEmptyArrayOnException(): void
    {
        $this->pdoMock->method('prepare')->willThrowException(new \PDOException('fail'));

        $this->assertSame([], $this->repo->getTopCountries(5));
    }

    // =========================================================
    // getDepartmentStats()
    // =========================================================

    /**
     * @test
     * getDepartmentStats() must return the rows fetched from the database,
     * preserving the department name and count values.
     */
    public function testGetDepartmentStatsReturnsArray(): void
    {
        $rows = [['name' => 'INFO', 'count' => 12]];
        $stmt = $this->buildFetchAllStmt($rows);
        $this->pdoMock->method('prepare')->willReturn($stmt);

        $result = $this->repo->getDepartmentStats(10);
        $this->assertCount(1, $result);
        $this->assertSame('INFO', $result[0]['name']);
    }

    /**
     * @test
     * When the database throws a PDOException, getDepartmentStats() must
     * return an empty array.
     */
    public function testGetDepartmentStatsReturnsEmptyArrayOnException(): void
    {
        $this->pdoMock->method('prepare')->willThrowException(new \PDOException('fail'));

        $this->assertSame([], $this->repo->getDepartmentStats(10));
    }

    // =========================================================
    // getIncomingOutgoingStats()
    // =========================================================

    /**
     * @test
     * When the database throws a PDOException, getIncomingOutgoingStats()
     * must return both counters set to zero.
     */
    public function testGetIncomingOutgoingStatsReturnsZerosOnException(): void
    {
        $this->pdoMock->method('query')->willThrowException(new \PDOException('fail'));

        $result = $this->repo->getIncomingOutgoingStats();
        $this->assertSame(['incoming' => 0, 'outgoing' => 0], $result);
    }

    // =========================================================
    // getContinentStats()
    // =========================================================

    /**
     * @test
     * When the database throws a PDOException, getContinentStats() must
     * return an empty array.
     */
    public function testGetContinentStatsReturnsEmptyOnException(): void
    {
        $this->pdoMock->method('query')->willThrowException(new \PDOException('fail'));

        $this->assertSame([], $this->repo->getContinentStats());
    }

    // =========================================================
    // getEuropeVsNonEuropeStats()
    // =========================================================

    /**
     * @test
     * When the database throws a PDOException, getEuropeVsNonEuropeStats()
     * must return both counters set to zero.
     */
    public function testGetEuropeVsNonEuropeStatsReturnsZerosOnException(): void
    {
        $this->pdoMock->method('query')->willThrowException(new \PDOException('fail'));

        $result = $this->repo->getEuropeVsNonEuropeStats();
        $this->assertSame(['europe_countries' => 0, 'non_europe_countries' => 0], $result);
    }

    // =========================================================
    // getZoneStats()
    // =========================================================

    /**
     * @test
     * getZoneStats() must return the rows from the database and cast the
     * "count" column from a string to an integer.
     */
    public function testGetZoneStatsReturnsArray(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetchAll')->willReturn([['name' => 'europe', 'count' => '40']]);
        $this->pdoMock->method('query')->willReturn($stmt);

        $result = $this->repo->getZoneStats();
        $this->assertCount(1, $result);
        $this->assertSame(40, $result[0]['count']); // string "40" must be cast to int
    }

    // =========================================================
    // inferContinent()
    // =========================================================

    /**
     * @test
     * When the zone is "Europe", inferContinent() must return "Europe"
     * regardless of the country name.
     */
    public function testInferContinentReturnsEuropeForEuropeZone(): void
    {
        $result = $this->repo->inferContinent('Allemagne', 'Europe');
        $this->assertSame('Europe', $result);
    }

    /**
     * @test
     * For a known Asian country ("Japon") outside Europe, inferContinent()
     * must return "Asie".
     */
    public function testInferContinentReturnsAsieForJapon(): void
    {
        $result = $this->repo->inferContinent('Japon', 'hors_europe');
        $this->assertSame('Asie', $result);
    }

    /**
     * @test
     * When the country is not in the built-in lookup table, inferContinent()
     * must return null so the caller can handle the unknown case.
     */
    public function testInferContinentReturnsNullForUnknownCountry(): void
    {
        $result = $this->repo->inferContinent('PaysInconnu', 'hors_europe');
        $this->assertNull($result);
    }

    /**
     * @test
     * For a known American country ("Canada") outside Europe, inferContinent()
     * must return "Amérique".
     */
    public function testInferContinentReturnsAmeriqueForCanada(): void
    {
        $this->assertSame('Amérique', $this->repo->inferContinent('Canada', 'hors_europe'));
    }

    // =========================================================
    // findAll()
    // =========================================================

    /**
     * @test
     * findAll() must return all rows as an array of associative arrays with
     * the original column values intact.
     */
    public function testFindAllReturnsArray(): void
    {
        $row  = ['NumEtu' => '123', 'Nom' => 'Dupont', 'Prenom' => 'Jean'];
        $stmt = $this->buildFetchAllStmt([$row]);
        $this->pdoMock->method('query')->willReturn($stmt);

        $result = $this->repo->findAll();
        $this->assertCount(1, $result);
        $this->assertSame('Dupont', $result[0]['Nom']);
    }

    /**
     * @test
     * When the database throws a PDOException, findAll() must return an empty
     * array rather than propagating the exception.
     */
    public function testFindAllReturnsEmptyArrayOnException(): void
    {
        $this->pdoMock->method('query')->willThrowException(new \PDOException('fail'));

        $this->assertSame([], $this->repo->findAll());
    }

    // =========================================================
    // findByNumEtu()
    // =========================================================

    /**
     * @test
     * When the database throws a PDOException, findByNumEtu() must return null
     * rather than propagating the exception.
     */
    public function testFindByNumEtuReturnsNullOnException(): void
    {
        $this->pdoMock->method('prepare')->willThrowException(new \PDOException('fail'));

        $this->assertNull($this->repo->findByNumEtu('12345678'));
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

        $data = ['NumEtu' => '12345678', 'Nom' => 'Dupont', 'Prenom' => 'Alice', 'status' => 'depot'];
        $this->assertTrue($this->repo->create($data));
    }

    /**
     * @test
     * When the database throws a PDOException, create() must return false
     * rather than propagating the exception.
     */
    public function testCreateReturnsFalseOnException(): void
    {
        $this->pdoMock->method('prepare')->willThrowException(new \PDOException('fail'));

        $this->assertFalse($this->repo->create(['NumEtu' => '123']));
    }

    // =========================================================
    // setStatus()
    // =========================================================

    /**
     * @test
     * setStatus() must execute the UPDATE with the status and numetu bound
     * correctly and return true for the valid status "accepte".
     */
    public function testSetStatusReturnsTrueForValidStatus(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())
            ->method('execute')
            ->with([':status' => 'accepte', ':numetu' => '12345678'])
            ->willReturn(true);
        $this->pdoMock->method('prepare')->willReturn($stmt);

        $this->assertTrue($this->repo->setStatus('12345678', 'accepte'));
    }

    /**
     * @test
     * setStatus() must return false immediately for an invalid status value
     * without issuing any database query.
     */
    public function testSetStatusReturnsFalseForInvalidStatus(): void
    {
        $this->assertFalse($this->repo->setStatus('12345678', 'invalide'));
    }

    /**
     * @test
     * All four workflow status values (depot, instruction, accepte, refuse)
     * must be accepted by setStatus() without returning false.
     */
    public function testSetStatusAllowedValues(): void
    {
        foreach (['depot', 'instruction', 'accepte', 'refuse'] as $status) {
            $stmt = $this->createMock(PDOStatement::class);
            $stmt->method('execute')->willReturn(true);
            $this->pdoMock->method('prepare')->willReturn($stmt);

            $this->assertTrue($this->repo->setStatus('12345678', $status));
        }
    }

    // =========================================================
    // setAvisChef()
    // =========================================================

    /**
     * @test
     * setAvisChef() must execute the UPDATE with "accepte" and the numetu
     * bound to the correct parameters and return true.
     */
    public function testSetAvisChefReturnsTrueForAccepte(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())
            ->method('execute')
            ->with([':avis' => 'accepte', ':numetu' => '12345678'])
            ->willReturn(true);
        $this->pdoMock->method('prepare')->willReturn($stmt);

        $this->assertTrue($this->repo->setAvisChef('12345678', 'accepte'));
    }

    /**
     * @test
     * Passing null as the avis value must be accepted (clears the chef opinion)
     * and return true.
     */
    public function testSetAvisChefReturnsTrueForNull(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $this->pdoMock->method('prepare')->willReturn($stmt);

        $this->assertTrue($this->repo->setAvisChef('12345678', null));
    }

    /**
     * @test
     * An invalid avis value must be rejected immediately with false, without
     * issuing any database query.
     */
    public function testSetAvisChefReturnsFalseForInvalidValue(): void
    {
        $this->assertFalse($this->repo->setAvisChef('12345678', 'invalide'));
    }

    // =========================================================
    // getAllDepartements()
    // =========================================================

    /**
     * @test
     * getAllDepartements() must return an array of department code strings
     * extracted from the CodeDepartement column of each row.
     */
    public function testGetAllDepartementsReturnsStringArray(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('fetch')->willReturnOnConsecutiveCalls(
            ['CodeDepartement' => 'INFO'],
            ['CodeDepartement' => 'MATH'],
            false // end of result set
        );
        $this->pdoMock->method('query')->willReturn($stmt);

        $result = $this->repo->getAllDepartements();
        $this->assertSame(['INFO', 'MATH'], $result);
    }

    /**
     * @test
     * When PDO::query() returns false, getAllDepartements() must return an
     * empty array rather than crashing.
     */
    public function testGetAllDepartementsReturnsEmptyArrayWhenQueryFails(): void
    {
        $this->pdoMock->method('query')->willReturn(false);

        $this->assertSame([], $this->repo->getAllDepartements());
    }

    // =========================================================
    // enregistrerValidation()
    // =========================================================

    /**
     * @test
     * When all arguments are valid, enregistrerValidation() must execute the
     * UPDATE and return true.
     */
    public function testEnregistrerValidationReturnsTrueOnSuccess(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute')->willReturn(true);
        $this->pdoMock->method('prepare')->willReturn($stmt);

        $result = $this->repo->enregistrerValidation(
            '12345678',
            ['photo' => 'valide'],
            '2025-06-01',
            'OK'
        );
        $this->assertTrue($result);
    }

    /**
     * @test
     * When the date string is not a valid date format, enregistrerValidation()
     * must return false without issuing any database query.
     */
    public function testEnregistrerValidationReturnsFalseForInvalidDate(): void
    {
        $result = $this->repo->enregistrerValidation('12345678', [], 'not-a-date');
        $this->assertFalse($result);
    }

    /**
     * @test
     * Passing null for both the date and comment (optional fields) must be
     * accepted and return true when the UPDATE succeeds.
     */
    public function testEnregistrerValidationAcceptsNullDateAndComment(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute')->willReturn(true);
        $this->pdoMock->method('prepare')->willReturn($stmt);

        $this->assertTrue($this->repo->enregistrerValidation('12345678', []));
    }

    // =========================================================
    // searchWithPagination()
    // =========================================================

    /**
     * @test
     * When the database throws a PDOException, searchWithPagination() must
     * return a safe empty-result structure rather than propagating the
     * exception.
     */
    public function testSearchWithPaginationReturnsEmptyOnException(): void
    {
        $this->pdoMock->method('prepare')->willThrowException(new \PDOException('fail'));

        $result = $this->repo->searchWithPagination([], 1, 10);
        $this->assertSame(['data' => [], 'total' => 0, 'totalPages' => 0], $result);
    }
}