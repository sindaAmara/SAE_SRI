<?php

namespace Tests\Controllers;

use PHPUnit\Framework\TestCase;
use Controllers\AuthController;

/**
 * Unit tests for {@see AuthController}.
 *
 * Coverage areas:
 * - Route matching via support() for authentication-related pages
 *
 * All HTTP superglobals are reset in setUp() before each test to prevent
 * state leakage between test cases.
 *
 * Run with:
 *   ./vendor/bin/phpunit Tests/Controllers/AuthControllerTest.php
 */
class AuthControllerTest extends TestCase
{
    /**
     * Resets all HTTP superglobals before each test to ensure full isolation.
     */
    protected function setUp(): void
    {
        $_SESSION = [];
        $_GET     = [];
        $_POST    = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    /**
     * @test
     * The auth controller must claim the "login" page on GET so the router
     * directs login traffic through it.
     */
    public function testSupportLogin(): void
    {
        $this->assertTrue(
            AuthController::support('login', 'GET')
        );
    }

    /**
     * @test
     * The auth controller must claim the "register" page on GET so the router
     * directs registration traffic through it.
     */
    public function testSupportRegister(): void
    {
        $this->assertTrue(
            AuthController::support('register', 'GET')
        );
    }

    /**
     * @test
     * The auth controller must claim the "forgot_password" page on GET so the
     * router directs password-reset traffic through it.
     */
    public function testSupportForgotPassword(): void
    {
        $this->assertTrue(
            AuthController::support('forgot_password', 'GET')
        );
    }

    /**
     * @test
     * Pages that do not belong to the authentication area must return false so
     * the router can delegate to another handler.
     */
    public function testSupportReturnsFalse(): void
    {
        $this->assertFalse(
            AuthController::support('home', 'GET')
        );
    }
}