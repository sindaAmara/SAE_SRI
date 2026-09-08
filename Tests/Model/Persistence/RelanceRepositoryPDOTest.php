<?php

namespace Tests\Model\Persistence;

use Model\Persistence\RelanceRepositoryPDO;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit tests for {@see RelanceRepositoryPDO}.
 *
 * Coverage areas:
 * - wasRecentlySent() — row found (true), no row found (false), parameter
 *   binding assertions, SQL query shape
 * - save() — correct parameter bindings, INSERT query shape, exception
 *   propagation
 *
 * Strategy: newInstanceWithoutConstructor() + ReflectionProperty injection on
 * the $pdo field so the Database singleton is never touched.
 *
 * Run with:
 *   ./vendor/bin/phpunit Tests/Model/Persistence/RelanceRepositoryPDOTest.php
 */
class RelanceRepositoryPDOTest extends TestCase
{
    /** Mocked PDO connection injected directly into the repository. */
    /** @var PDO&MockObject */
    private PDO $pdoMock;

    /** Repository instance created without invoking the real constructor. */
    private RelanceRepositoryPDO $repo;

    /**
     * Creates the repository via reflection (bypassing the constructor) and
     * injects a PDO mock so no real database connection is opened.
     */
    protected function setUp(): void
    {
        $this->repo = (new \ReflectionClass(RelanceRepositoryPDO::class))
            ->newInstanceWithoutConstructor();

        $this->pdoMock = $this->createMock(PDO::class);

        $prop = new \ReflectionProperty(RelanceRepositoryPDO::class, 'pdo');
        $prop->setAccessible(true);
        $prop->setValue($this->repo, $this->pdoMock);
    }

    // =========================================================
    // wasRecentlySent()
    // =========================================================

    /**
     * @test
     * When the SELECT returns a truthy fetchColumn value, wasRecentlySent()
     * must return true. The test also verifies that the :id and :days
     * parameters are bound with the correct values and types.
     */
    public function testWasRecentlySentReturnsTrueWhenRowFound(): void
    {
        $stmt = $this->createMock(PDOStatement::class);

        // Capture all bindValue() calls so we can assert on each argument.
        $calls = [];
        $stmt->method('bindValue')->willReturnCallback(function () use (&$calls) {
            $calls[] = func_get_args();
            return true;
        });
        $stmt->method('execute');
        $stmt->method('fetchColumn')->willReturn('1'); // truthy → recent relance found

        $this->pdoMock->method('prepare')->willReturn($stmt);

        $result = $this->repo->wasRecentlySent('12345678', 7);

        $this->assertTrue($result);
        $this->assertSame(':id',          $calls[0][0]);
        $this->assertSame('12345678',     $calls[0][1]);
        $this->assertSame(':days',        $calls[1][0]);
        $this->assertSame(7,              $calls[1][1]);
        $this->assertSame(PDO::PARAM_INT, $calls[1][2]); // days must be bound as integer
    }

    /**
     * @test
     * When fetchColumn() returns false (no recent relance in the window),
     * wasRecentlySent() must return false.
     */
    public function testWasRecentlySentReturnsFalseWhenNoRowFound(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('bindValue');
        $stmt->method('execute');
        $stmt->method('fetchColumn')->willReturn(false); // no row → not recently sent

        $this->pdoMock->method('prepare')->willReturn($stmt);

        $this->assertFalse($this->repo->wasRecentlySent('12345678', 30));
    }

    /**
     * @test
     * wasRecentlySent() must bind the supplied numetu to :id and the days
     * value to :days, regardless of the fetchColumn result.
     */
    public function testWasRecentlySentBindsCorrectNumEtu(): void
    {
        $stmt = $this->createMock(PDOStatement::class);

        $calls = [];
        $stmt->method('bindValue')->willReturnCallback(function () use (&$calls) {
            $calls[] = func_get_args();
            return true;
        });
        $stmt->method('execute');
        $stmt->method('fetchColumn')->willReturn(false);

        $this->pdoMock->method('prepare')->willReturn($stmt);

        $this->repo->wasRecentlySent('99999999', 14);

        $this->assertSame(':id',      $calls[0][0]);
        $this->assertSame('99999999', $calls[0][1]);
        $this->assertSame(':days',    $calls[1][0]);
        $this->assertSame(14,         $calls[1][1]);
    }

    /**
     * @test
     * wasRecentlySent() must issue a SELECT query so that no data is mutated
     * when checking the relance history.
     */
    public function testWasRecentlySentUsesSelectQuery(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('bindValue');
        $stmt->method('execute');
        $stmt->method('fetchColumn')->willReturn(false);

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('SELECT'))
            ->willReturn($stmt);

        $this->repo->wasRecentlySent('12345678', 7);
    }

    // =========================================================
    // save()
    // =========================================================

    /**
     * @test
     * save() must execute the prepared statement with the student number and
     * message text bound to their respective keys.
     */
    public function testSaveExecutesInsertWithCorrectBindings(): void
    {
        $stmt = $this->createMock(PDOStatement::class);

        $stmt->expects($this->once())
            ->method('execute')
            ->with([
                'id'      => '12345678',
                'message' => 'Merci de compléter votre dossier.',
            ])
            ->willReturn(true);

        $this->pdoMock->method('prepare')->willReturn($stmt);

        $this->repo->save('12345678', 'Merci de compléter votre dossier.');
    }

    /**
     * @test
     * save() must prepare an INSERT INTO relances statement so that the new
     * relance record is persisted in the correct table.
     */
    public function testSaveCallsPrepareWithInsertQuery(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('INSERT INTO relances'))
            ->willReturn($stmt);

        $this->repo->save('12345678', 'Rappel');
    }

    /**
     * @test
     * When the database throws a PDOException, save() must let it propagate so
     * the caller can handle the failure (no silent swallowing).
     */
    public function testSaveThrowsOnPDOException(): void
    {
        $this->pdoMock->method('prepare')
            ->willThrowException(new \PDOException('DB error'));

        $this->expectException(\PDOException::class);
        $this->repo->save('12345678', 'Rappel');
    }
}