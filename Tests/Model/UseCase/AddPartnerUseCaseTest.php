<?php

namespace Tests\Model\UseCase;

use Model\Entity\Partner;
use Model\Repository\PartnerRepositoryInterface;
use Model\UseCase\AddPartnerUseCase;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class AddPartnerUseCaseTest
 *
 * Unit tests for the AddPartnerUseCase.
 *
 * Tests verify that the use case correctly delegates adding a partner
 * to the repository, propagates exceptions, and passes the exact Partner instance.
 */
class AddPartnerUseCaseTest extends TestCase
{
    /**
     * @var PartnerRepositoryInterface&MockObject
     * Mocked repository for testing the use case.
     */
    private PartnerRepositoryInterface $repositoryMock;

    /** @var AddPartnerUseCase The use case under test. */
    private AddPartnerUseCase $useCase;

    /**
     * Setup before each test: create repository mock and use case instance.
     */
    protected function setUp(): void
    {
        $this->repositoryMock = $this->createMock(PartnerRepositoryInterface::class);
        $this->useCase = new AddPartnerUseCase($this->repositoryMock);
    }

    // -------------------------------------------------------------------------
    // Tests for execute() method
    // -------------------------------------------------------------------------

    /**
     * Test that execute() calls addPartner() on the repository.
     */
    public function testExecuteCallsAddPartnerOnRepository(): void
    {
        $partner = $this->createMock(Partner::class);

        $this->repositoryMock
            ->expects($this->once())
            ->method('addPartner')
            ->with($this->identicalTo($partner));

        $this->useCase->execute($partner);
    }

    /**
     * Test that execute() passes the exact Partner instance to the repository.
     */
    public function testExecutePassesExactPartnerInstance(): void
    {
        $partner = $this->createMock(Partner::class);
        $otherPartner = $this->createMock(Partner::class);

        $this->repositoryMock
            ->expects($this->once())
            ->method('addPartner')
            ->with($this->identicalTo($partner));

        $this->useCase->execute($partner);

        // Ensure $otherPartner was never passed
        $this->assertNotSame($otherPartner, $partner);
    }

    /**
     * Test that execute() propagates any PDOException thrown by the repository.
     */
    public function testExecutePropagatesPDOException(): void
    {
        $partner = $this->createMock(Partner::class);

        $this->repositoryMock
            ->method('addPartner')
            ->willThrowException(new \PDOException('DB error'));

        $this->expectException(\PDOException::class);
        $this->useCase->execute($partner);
    }

    /**
     * Test that execute() calls addPartner() exactly once.
     */
    public function testExecuteCallsAddPartnerExactlyOnce(): void
    {
        $partner = $this->createMock(Partner::class);

        $this->repositoryMock
            ->expects($this->once())
            ->method('addPartner');

        $this->useCase->execute($partner);
    }
}