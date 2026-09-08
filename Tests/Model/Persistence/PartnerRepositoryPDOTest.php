<?php

namespace Tests\Model\Persistence;

use Model\Entity\Partner;
use Model\Persistence\PartnerRepositoryPDO;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit tests for {@see PartnerRepositoryPDO}.
 *
 * Coverage areas:
 * - addPartner() — correct parameter bindings, INSERT query shape, all fields
 *   forwarded, PDOException propagation
 *
 * Strategy: newInstanceWithoutConstructor() to bypass the constructor, plus a
 * Database singleton replacement via ReflectionProperty so no real database
 * connection is ever opened.
 *
 * Run with:
 *   ./vendor/bin/phpunit Tests/Model/Persistence/PartnerRepositoryPDOTest.php
 */
class PartnerRepositoryPDOTest extends TestCase
{
    /** Repository instance created without invoking the real constructor. */
    private PartnerRepositoryPDO $repo;

    /**
     * Creates the repository via reflection so the constructor (which would
     * open a real database connection) is never called.
     */
    protected function setUp(): void
    {
        $this->repo = (new \ReflectionClass(PartnerRepositoryPDO::class))
            ->newInstanceWithoutConstructor();
    }

    // -------------------------------------------------------------------------
    // Fixture / helper factories
    // -------------------------------------------------------------------------

    /**
     * Builds a mocked Partner entity with the supplied field values.
     *
     * Defaults represent a typical European university partner; override
     * individual arguments to test other scenarios.
     */
    private function makePartner(
        string $continent   = 'Europe',
        string $country     = 'Allemagne',
        string $city        = 'Berlin',
        string $institution = 'TU Berlin',
        string $type        = 'université'
    ): Partner {
        $p = $this->createMock(Partner::class);
        $p->method('getContinent')->willReturn($continent);
        $p->method('getCountry')->willReturn($country);
        $p->method('getCity')->willReturn($city);
        $p->method('getInstitution')->willReturn($institution);
        $p->method('getType')->willReturn($type);
        return $p;
    }

    /**
     * Replaces the Database singleton's PDO connection with the given mock so
     * all repository queries go through the mock instead of the real database.
     */
    private function injectPdo(PDO $pdo): void
    {
        $dbMock = $this->createMock(\Database::class);
        $dbMock->method('getConnection')->willReturn($pdo);

        $ref  = new \ReflectionClass(\Database::class);
        $prop = $ref->getProperty('instance');
        $prop->setAccessible(true);
        $prop->setValue(null, $dbMock);
    }

    // =========================================================
    // addPartner()
    // =========================================================

    /**
     * @test
     * addPartner() must execute the prepared statement with all five partner
     * fields bound to their respective keys (continent, pays, ville,
     * universite, type).
     */
    public function testAddPartnerExecutesInsertWithCorrectBindings(): void
    {
        $pdoMock  = $this->createMock(PDO::class);
        $stmtMock = $this->createMock(PDOStatement::class);

        $this->injectPdo($pdoMock);

        $pdoMock->expects($this->once())
            ->method('prepare')
            ->willReturn($stmtMock);

        $stmtMock->expects($this->once())
            ->method('execute')
            ->with([
                'continent'  => 'Europe',
                'pays'       => 'Allemagne',
                'ville'      => 'Berlin',
                'universite' => 'TU Berlin',
                'type'       => 'université',
            ])
            ->willReturn(true);

        $this->repo->addPartner($this->makePartner());
    }

    /**
     * @test
     * addPartner() must prepare an INSERT INTO Partenaires statement so that
     * the new record is persisted in the correct table.
     */
    public function testAddPartnerCallsPrepareWithInsertQuery(): void
    {
        $pdoMock  = $this->createMock(PDO::class);
        $stmtMock = $this->createMock(PDOStatement::class);

        $this->injectPdo($pdoMock);
        $stmtMock->method('execute')->willReturn(true);

        $pdoMock->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('INSERT INTO Partenaires'))
            ->willReturn($stmtMock);

        $this->repo->addPartner($this->makePartner());
    }

    /**
     * @test
     * All five fields returned by the Partner entity (continent, country, city,
     * institution, type) must be forwarded to the prepared statement exactly
     * as returned by the getter methods — no field may be dropped or
     * transformed.
     */
    public function testAddPartnerPassesAllPartnerFieldsToStatement(): void
    {
        $pdoMock  = $this->createMock(PDO::class);
        $stmtMock = $this->createMock(PDOStatement::class);

        $this->injectPdo($pdoMock);
        $pdoMock->method('prepare')->willReturn($stmtMock);

        $stmtMock->expects($this->once())
            ->method('execute')
            ->with([
                'continent'  => 'Asie',
                'pays'       => 'Japon',
                'ville'      => 'Tokyo',
                'universite' => 'Université de Tokyo',
                'type'       => 'université',
            ])
            ->willReturn(true);

        $this->repo->addPartner(
            $this->makePartner('Asie', 'Japon', 'Tokyo', 'Université de Tokyo', 'université')
        );
    }

    /**
     * @test
     * When the database throws a PDOException (e.g. connection lost, unique
     * constraint violation), addPartner() must let it propagate so the caller
     * can handle the failure — no silent swallowing.
     */
    public function testAddPartnerThrowsPDOExceptionOnFailure(): void
    {
        $pdoMock = $this->createMock(PDO::class);

        $this->injectPdo($pdoMock);

        $pdoMock->method('prepare')
            ->willThrowException(new \PDOException('DB error'));

        $this->expectException(\PDOException::class);
        $this->repo->addPartner($this->makePartner());
    }
}