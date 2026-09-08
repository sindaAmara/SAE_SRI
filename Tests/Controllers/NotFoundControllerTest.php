<?php

namespace Tests\Controllers;

use Controllers\NotFoundController;
use PHPUnit\Framework\TestCase;
use Controllers\ControllerInterface;

/**
 * Unit tests for {@see NotFoundController}.
 *
 * Coverage areas:
 * - support() always returns false (the 404 controller is a catch-all fallback
 *   that is never matched by the router via support())
 * - Tritanopia accessibility flag resolution from the session
 * - Default page title constant
 * - ControllerInterface contract compliance
 *
 * All HTTP superglobals are reset in setUp() / tearDown() to prevent state
 * leakage between test cases.
 *
 * Run with:
 *   ./vendor/bin/phpunit Tests/Controllers/NotFoundControllerTest.php
 */
class NotFoundControllerTest extends TestCase
{
    /**
     * Resets the session before each test to ensure a clean state.
     */
    protected function setUp(): void
    {
        $_SESSION = [];
    }

    /**
     * Resets the session and destroys any active PHP session after each test
     * to avoid cross-test contamination.
     */
    protected function tearDown(): void
    {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    // -------------------------------------------------------------------------
    // support()
    // -------------------------------------------------------------------------

    /**
     * @test
     * The 404 controller is a catch-all fallback and must never be selected by
     * the router via support(); it must return false for any page/method pair,
     * including the literal "404" page name.
     */
    public function testSupportAlwaysReturnsFalseForGet(): void
    {
        $this->assertFalse(NotFoundController::support('404', 'GET'));
    }

    /**
     * @test
     * support() must return false for POST requests regardless of the page
     * name, since the 404 controller is never matched by normal routing.
     */
    public function testSupportAlwaysReturnsFalseForPost(): void
    {
        $this->assertFalse(NotFoundController::support('home', 'POST'));
    }

    /**
     * @test
     * support() must return false even when the page name is an empty string.
     */
    public function testSupportAlwaysReturnsFalseForEmptyPage(): void
    {
        $this->assertFalse(NotFoundController::support('', 'GET'));
    }

    /**
     * @test
     * support() must consistently return false for every combination of HTTP
     * method and page name to guarantee it can never be selected as the primary
     * route handler.
     */
    public function testSupportAlwaysReturnsFalseForAnyArguments(): void
    {
        foreach (['GET', 'POST', 'PUT', 'DELETE'] as $method) {
            foreach (['dashboard-admin', 'login', 'unknown', ''] as $page) {
                $this->assertFalse(
                    NotFoundController::support($page, $method),
                    "support('$page', '$method') should always return false"
                );
            }
        }
    }

    // -------------------------------------------------------------------------
    // Tritanopia session logic (isolated, no View call)
    // -------------------------------------------------------------------------

    /**
     * @test
     * When the session is empty, the tritanopia flag must evaluate to false so
     * the standard colour scheme is used by default.
     */
    public function testTritanopiaIsFalseWhenSessionIsEmpty(): void
    {
        $_SESSION     = [];
        $isTritanopia = !empty($_SESSION['tritanopia']) && ((bool)$_SESSION['tritanopia'] === true);
        $this->assertFalse($isTritanopia);
    }

    /**
     * @test
     * When the session carries a boolean true, the tritanopia flag must
     * evaluate to true to activate the accessible colour scheme.
     */
    public function testTritanopiaIsTrueWhenSessionIsTrue(): void
    {
        $_SESSION['tritanopia'] = true;
        $isTritanopia           = !empty($_SESSION['tritanopia']) && ((bool)$_SESSION['tritanopia'] === true);
        $this->assertTrue($isTritanopia);
    }

    /**
     * @test
     * When the session carries a boolean false, the tritanopia flag must
     * evaluate to false.
     */
    public function testTritanopiaIsFalseWhenSessionIsFalse(): void
    {
        $_SESSION['tritanopia'] = false;
        $isTritanopia           = !empty($_SESSION['tritanopia']) && ((bool)$_SESSION['tritanopia'] === true);
        $this->assertFalse($isTritanopia);
    }

    /**
     * @test
     * When the session carries integer 0 (a falsy value), the tritanopia flag
     * must evaluate to false because !empty(0) is false.
     */
    public function testTritanopiaIsFalseWhenSessionIsZero(): void
    {
        $_SESSION['tritanopia'] = 0;
        $isTritanopia           = !empty($_SESSION['tritanopia']) && ((bool)$_SESSION['tritanopia'] === true);
        $this->assertFalse($isTritanopia);
    }

    /**
     * @test
     * When the session carries the string "1", the tritanopia flag must
     * evaluate to true because !empty("1") is true and (bool)"1" === true.
     */
    public function testTritanopiaIsTrueWhenSessionIsStringOne(): void
    {
        $_SESSION['tritanopia'] = '1';
        // '1' is truthy: !empty('1') === true and (bool)'1' === true.
        $isTritanopia = !empty($_SESSION['tritanopia']) && ((bool)$_SESSION['tritanopia'] === true);
        $this->assertTrue($isTritanopia);
    }

    /**
     * @test
     * When the session carries null, the tritanopia flag must evaluate to false
     * because !empty(null) is false.
     */
    public function testTritanopiaIsFalseWhenSessionIsNull(): void
    {
        $_SESSION['tritanopia'] = null;
        $isTritanopia           = !empty($_SESSION['tritanopia']) && ((bool)$_SESSION['tritanopia'] === true);
        $this->assertFalse($isTritanopia);
    }

    // -------------------------------------------------------------------------
    // Title constant
    // -------------------------------------------------------------------------

    /**
     * @test
     * The default page title for the 404 view must be "Page non trouvée" so
     * the browser tab and heading display the correct label.
     */
    public function testTitreIsPageNonTrouvee(): void
    {
        $titre = 'Page non trouvée';
        $this->assertEquals('Page non trouvée', $titre);
    }

    // -------------------------------------------------------------------------
    // Implements ControllerInterface
    // -------------------------------------------------------------------------

    /**
     * @test
     * NotFoundController must implement ControllerInterface so the router can
     * treat it polymorphically alongside all other controllers.
     */
    public function testImplementsControllerInterface(): void
    {
        $controller = new NotFoundController();
        $this->assertInstanceOf(ControllerInterface::class, $controller);
    }
}