<?php

namespace Tests\Service;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Service\SuperAdminService;
use Model\Persistence\UserRepositoryPDO;

/**
 * Class SuperAdminServiceTest
 *
 * Unit tests for the SuperAdminService.
 *
 * These tests cover administrative functionalities such as:
 * - Retrieving available departments and sites
 * - Adding custom departments and sites
 * - Managing user accounts (create, fetch, delete)
 */
class SuperAdminServiceTest extends TestCase
{
    /** @var UserRepositoryPDO&MockObject Mocked user repository */
    private UserRepositoryPDO $userRepoMock;

    /** @var SuperAdminService Service under test */
    private SuperAdminService $service;

    /**
     * Setup mocked repository and service instance.
     */
    protected function setUp(): void
    {
        $this->userRepoMock = $this->createMock(UserRepositoryPDO::class);
        $this->service      = new SuperAdminService($this->userRepoMock);
    }

    // -------------------------------------------------------------------------
    // Departments
    // -------------------------------------------------------------------------

    /**
     * Test retrieving all available departments.
     */
    public function testGetAvailableDepartments(): void
    {
        $expected = ['INFO', 'MATH'];

        $this->userRepoMock
            ->expects($this->once())
            ->method('getDistinctDepartments')
            ->willReturn($expected);

        $result = $this->service->getAvailableDepartments();

        $this->assertEquals($expected, $result);
    }

    /**
     * Test adding a custom department.
     */
    public function testAddDepartment(): void
    {
        $this->userRepoMock
            ->expects($this->once())
            ->method('addCustomDepartment')
            ->with('INFO');

        $this->service->addDepartment('INFO');
    }

    // -------------------------------------------------------------------------
    // Sites
    // -------------------------------------------------------------------------

    /**
     * Test retrieving all available sites.
     */
    public function testGetAvailableSites(): void
    {
        $expected = ['Aix', 'Marseille'];

        $this->userRepoMock
            ->expects($this->once())
            ->method('getDistinctSites')
            ->willReturn($expected);

        $result = $this->service->getAvailableSites();

        $this->assertEquals($expected, $result);
    }

    /**
     * Test adding a custom site.
     */
    public function testAddSite(): void
    {
        $this->userRepoMock
            ->expects($this->once())
            ->method('addCustomSite')
            ->with('Aix');

        $this->service->addSite('Aix');
    }

    // -------------------------------------------------------------------------
    // User accounts
    // -------------------------------------------------------------------------

    /**
     * Test retrieving all accounts.
     */
    public function testGetAllAccounts(): void
    {
        $accounts = [
            [
                'login'       => 'admin@test.com',
                'role'        => 'admin',
                'departement' => 'INFO',
                'site'        => 'Aix',
                'nom'         => 'Doe',
                'prenom'      => 'John',
                'created_at'  => '2025-01-01',
            ],
        ];

        $this->userRepoMock
            ->expects($this->once())
            ->method('findAll')
            ->willReturn($accounts);

        $result = $this->service->getAllAccounts();

        $this->assertEquals($accounts, $result);
    }

    /**
     * Test creating a new account successfully.
     */
    public function testCreateAccountSuccess(): void
    {
        $this->userRepoMock
            ->method('findByLogin')
            ->willReturn(null); // No existing user

        $this->userRepoMock
            ->expects($this->once())
            ->method('create')
            ->with(
                'test@test.com',
                $this->callback(fn($hash) => password_verify('password123', $hash)),
                'admin',
                'INFO',
                'Aix',
                'Doe',
                'John'
            );

        $this->service->createAccount(
            'test@test.com',
            'password123',
            'admin',
            'INFO',
            'Aix',
            'Doe',
            'John'
        );

        $this->assertTrue(true);
    }

    /**
     * Test creating an account that already exists throws an exception.
     */
    public function testCreateAccountAlreadyExists(): void
    {
        $this->userRepoMock
            ->method('findByLogin')
            ->willReturn(['login' => 'test@test.com']);

        $this->expectException(\RuntimeException::class);

        $this->service->createAccount(
            'test@test.com',
            'password123',
            'admin'
        );
    }

    /**
     * Test deleting an account by login.
     */
    public function testDeleteAccount(): void
    {
        $this->userRepoMock
            ->expects($this->once())
            ->method('deleteByLogin')
            ->with('test@test.com')
            ->willReturn(true);

        $result = $this->service->deleteAccount('test@test.com');

        $this->assertTrue($result);
    }
}