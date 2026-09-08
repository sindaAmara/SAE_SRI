<?php

namespace Tests\Controllers\WebPlanController;

use PHPUnit\Framework\TestCase;
use Controllers\WebPlanController\WebPlanController;

/**
 * Class WebPlanControllerTest
 *
 * Unit tests for the WebPlanController class.
 *
 * Tests include:
 * - support() method behavior for page and HTTP method
 * - basic controller instantiation
 */
class WebPlanControllerTest extends TestCase
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
            WebPlanController::support('web_plan', 'GET')
        );
    }

    /**
     * Test that support() returns false when the page does not match.
     */
    public function testSupportReturnsFalseWrongPage(): void
    {
        $this->assertFalse(
            WebPlanController::support('home', 'GET')
        );
    }

    /**
     * Test that support() returns false when the HTTP method does not match.
     */
    public function testSupportReturnsFalseWrongMethod(): void
    {
        $this->assertFalse(
            WebPlanController::support('web_plan', 'POST')
        );
    }

    /**
     * Test that the controller can be instantiated correctly.
     */
    public function testControllerInstanceCreation(): void
    {
        $controller = new WebPlanController();

        $this->assertInstanceOf(
            WebPlanController::class,
            $controller
        );
    }
}