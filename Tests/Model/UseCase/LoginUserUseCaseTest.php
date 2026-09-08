<?php

namespace Tests\Model\UseCase;

use Model\Repository\UserRepositoryInterface;
use Model\UseCase\LoginUserUseCase;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class LoginUserUseCaseTest
 *
 * Unit tests for the LoginUserUseCase.
 *
 * These tests verify that login requests are routed correctly to either the admin
 * or student repository based on input (email vs student number) and that results
 * are propagated correctly.
 */
class LoginUserUseCaseTest extends TestCase
{
    /**
     * @var UserRepositoryInterface&MockObject Mocked repository for admin users.
     */
    private UserRepositoryInterface $adminRepo;

    /**
     * @var UserRepositoryInterface&MockObject Mocked repository for student users.
     */
    private UserRepositoryInterface $studentRepo;

    /** @var LoginUserUseCase The use case under test. */
    private LoginUserUseCase $useCase;

    /**
     * Setup before each test: create repository mocks and the use case instance.
     */
    protected function setUp(): void
    {
        $this->adminRepo   = $this->createMock(UserRepositoryInterface::class);
        $this->studentRepo = $this->createMock(UserRepositoryInterface::class);
        $this->useCase     = new LoginUserUseCase($this->adminRepo, $this->studentRepo);
    }

    // -------------------------------------------------------------------------
    // Routing: Email → Admin Repository
    // -------------------------------------------------------------------------

    /**
     * Test that login with an email calls the admin repository.
     */
    public function testExecuteWithEmailUsesAdminRepo(): void
    {
        $this->adminRepo->expects($this->once())
            ->method('login')
            ->with('admin@test.com', 'secret')
            ->willReturn(['success' => true, 'role' => 'admin']);

        $this->studentRepo->expects($this->never())->method('login');

        $result = $this->useCase->execute('admin@test.com', 'secret');

        $this->assertTrue($result['success']);
        $this->assertSame('admin', $result['role']);
    }

    /**
     * Test that login failure from admin repository returns failure.
     */
    public function testExecuteWithEmailReturnsFailureWhenAdminRepoFails(): void
    {
        $this->adminRepo->method('login')->willReturn(['success' => false]);

        $result = $this->useCase->execute('admin@test.com', 'wrongpassword');

        $this->assertFalse($result['success']);
    }

    // -------------------------------------------------------------------------
    // Routing: Student Number → Student Repository
    // -------------------------------------------------------------------------

    /**
     * Test that login with a student number calls the student repository.
     */
    public function testExecuteWithStudentNumberUsesStudentRepo(): void
    {
        $this->studentRepo->expects($this->once())
            ->method('login')
            ->with('12345678', 'secret')
            ->willReturn(['success' => true, 'role' => 'student']);

        $this->adminRepo->expects($this->never())->method('login');

        $result = $this->useCase->execute('12345678', 'secret');

        $this->assertTrue($result['success']);
        $this->assertSame('student', $result['role']);
    }

    /**
     * Test that login failure from student repository returns failure.
     */
    public function testExecuteWithStudentNumberReturnsFailureWhenStudentRepoFails(): void
    {
        $this->studentRepo->method('login')->willReturn(['success' => false]);

        $result = $this->useCase->execute('12345678', 'wrongpassword');

        $this->assertFalse($result['success']);
    }

    // -------------------------------------------------------------------------
    // Email format validation
    // -------------------------------------------------------------------------

    /**
     * Test that invalid email format (not a valid email) routes to student repository.
     */
    public function testExecuteWithInvalidEmailUsesStudentRepo(): void
    {
        $this->studentRepo->expects($this->once())
            ->method('login')
            ->willReturn(['success' => false]);

        $this->adminRepo->expects($this->never())->method('login');

        $this->useCase->execute('not-an-email', 'pass');
    }

    /**
     * Test that valid email format routes to admin repository.
     */
    public function testExecuteWithValidEmailFormatUsesAdminRepo(): void
    {
        $this->adminRepo->expects($this->once())
            ->method('login')
            ->willReturn(['success' => true, 'role' => 'chef_departement']);

        $this->studentRepo->expects($this->never())->method('login');

        $result = $this->useCase->execute('chef@univ.fr', 'pass');
        $this->assertTrue($result['success']);
    }

    // -------------------------------------------------------------------------
    // Raw result propagation
    // -------------------------------------------------------------------------

    /**
     * Test that execute() returns the exact array from the admin repository.
     */
    public function testExecuteReturnsRawArrayFromAdminRepo(): void
    {
        $expected = ['success' => true, 'role' => 'admin', 'departement' => 'INFO', 'force_change_password' => false];
        $this->adminRepo->method('login')->willReturn($expected);

        $result = $this->useCase->execute('admin@test.com', 'pass');

        $this->assertSame($expected, $result);
    }

    /**
     * Test that execute() returns the exact array from the student repository.
     */
    public function testExecuteReturnsRawArrayFromStudentRepo(): void
    {
        $expected = ['success' => true, 'role' => 'student', 'numetu' => '12345678'];
        $this->studentRepo->method('login')->willReturn($expected);

        $result = $this->useCase->execute('12345678', 'pass');

        $this->assertSame($expected, $result);
    }
}