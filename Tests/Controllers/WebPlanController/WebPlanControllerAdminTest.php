<?php

namespace Tests\Controllers\WebPlanController;

use PHPUnit\Framework\TestCase;
use Controllers\WebPlanController\WebPlanControllerAdmin;

/**
 * Class WebPlanControllerAdminTest
 *
 * Unit tests for the WebPlanControllerAdmin class.
 *
 * Tests include:
 * - support() method behavior for page and HTTP method
 * - basic controller instantiation
 */
class WebPlanControllerAdminTest extends TestCase
{
    /**
     * Setup before each test.
     * Resets $_SESSION, $_GET, and request method.
     */
    protected function setUp(): void
    {
        $_SESSION = [];
        $_GET     = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    /**
     * Test that support() returns true for the correct page and GET method.
     */
    public function testSupportReturnsTrue(): void
    {
        $this->assertTrue(
            WebPlanControllerAdmin::support('web_plan-admin', 'GET')
        );
    }

    /**
     * Test that support() returns false when the page does not match.
     */
    public function testSupportReturnsFalseWrongPage(): void
    {
        $this->assertFalse(
            WebPlanControllerAdmin::support('web_plan', 'GET')
        );
    }

    /**
     * Test that support() returns false when the HTTP method does not match.
     */
    public function testSupportReturnsFalseWrongMethod(): void
    {
        $this->assertFalse(
            WebPlanControllerAdmin::support('web_plan-admin', 'POST')
        );
    }

    /**
     * Test that the controller can be instantiated correctly.
     */
    public function testControllerInstanceCreation(): void
    {
        $controller = new WebPlanControllerAdmin();

        $this->assertInstanceOf(
            WebPlanControllerAdmin::class,
            $controller
        );
    }
}