<?php

namespace Service;

use Model\Repository\FolderRepositoryInterface;
use Model\Repository\RelanceRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class CronReminderServiceTest
 *
 * Unit tests for the CronReminderService.
 *
 * These tests focus on verifying the behavior of the service when sending
 * reminders (relances) for incomplete student folders. Scenarios include:
 * - Dry run mode (no actual sending)
 * - Skipping folders with no email
 * - Skipping folders that already received a recent reminder
 */
class CronReminderServiceTest extends TestCase
{
    /**
     * @var FolderRepositoryInterface&MockObject Mocked folder repository.
     */
    private FolderRepositoryInterface $folderRepoMock;

    /**
     * @var RelanceRepositoryInterface&MockObject Mocked relance repository.
     */
    private RelanceRepositoryInterface $relanceRepoMock;

    /** @var CronReminderService Service under test. */
    private CronReminderService $service;

    /**
     * Setup the test environment with mocked repositories.
     */
    protected function setUp(): void
    {
        $this->folderRepoMock  = $this->createMock(FolderRepositoryInterface::class);
        $this->relanceRepoMock = $this->createMock(RelanceRepositoryInterface::class);

        $this->service = new CronReminderService(
            $this->folderRepoMock,
            $this->relanceRepoMock
        );
    }

    // -------------------------------------------------------------------------
    // Dry run tests
    // -------------------------------------------------------------------------

    /**
     * Ensure that in dry run mode, no relance is actually saved.
     */
    public function testDryRunDoesNotSendRelance(): void
    {
        $folders = [
            [
                'NumEtu'         => '12345',
                'EmailAMU'       => 'test@amu.fr',
                'EmailPersonnel' => '',
                'Prenom'         => 'John',
                'Nom'            => 'Doe',
            ],
        ];

        $this->folderRepoMock
            ->method('findIncompleteFolders')
            ->willReturn($folders);

        $this->relanceRepoMock
            ->method('wasRecentlySent')
            ->willReturn(false);

        $this->relanceRepoMock
            ->expects($this->never())
            ->method('save');

        // Run in dry mode
        $this->service->run(true, 7);

        $this->assertTrue(true); // Dummy assertion for PHPUnit
    }

    // -------------------------------------------------------------------------
    // Skipping folders with no email
    // -------------------------------------------------------------------------

    /**
     * Ensure that folders without any email are skipped.
     */
    public function testSkipIfNoEmail(): void
    {
        $folders = [
            [
                'NumEtu'         => '12345',
                'EmailAMU'       => '',
                'EmailPersonnel' => '',
                'Prenom'         => 'John',
                'Nom'            => 'Doe',
            ],
        ];

        $this->folderRepoMock
            ->method('findIncompleteFolders')
            ->willReturn($folders);

        $this->relanceRepoMock
            ->expects($this->never())
            ->method('wasRecentlySent');

        $this->service->run(false, 7);

        $this->assertTrue(true);
    }

    // -------------------------------------------------------------------------
    // Skipping folders with recent relance
    // -------------------------------------------------------------------------

    /**
     * Ensure that folders which already received a recent relance are skipped.
     */
    public function testSkipIfRecentlySent(): void
    {
        $folders = [
            [
                'NumEtu'         => '12345',
                'EmailAMU'       => 'test@amu.fr',
                'EmailPersonnel' => '',
                'Prenom'         => 'John',
                'Nom'            => 'Doe',
            ],
        ];

        $this->folderRepoMock
            ->method('findIncompleteFolders')
            ->willReturn($folders);

        $this->relanceRepoMock
            ->method('wasRecentlySent')
            ->willReturn(true);

        $this->relanceRepoMock
            ->expects($this->never())
            ->method('save');

        $this->service->run(false, 7);

        $this->assertTrue(true);
    }
}