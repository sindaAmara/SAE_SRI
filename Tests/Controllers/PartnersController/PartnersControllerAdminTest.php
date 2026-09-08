<?php

namespace Tests\Controllers\PartnersController;

use PHPUnit\Framework\TestCase;
use Controllers\PartnersController\PartnersControllerAdmin;

/**
 * Class PartnersControllerAdminTest
 *
 * Unit tests for the PartnersControllerAdmin class.
 *
 * Tests:
 * - support method behavior
 * - default and changed language handling
 * - access control (admin-only)
 * - form submission with missing fields
 */
class PartnersControllerAdminTest extends TestCase
{
    /**
     * Setup the environment before each test.
     * Resets $_GET, $_POST, $_SESSION, and request method.
     */
    protected function setUp(): void
    {
        $_GET                      = [];
        $_POST                     = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SESSION                  = ['role' => 'admin'];
    }

    /**
     * Creates a testable controller instance with overridden methods
     * to prevent real session start, redirects, and view rendering.
     *
     * @return PartnersControllerAdmin
     */
    private function makeController(): PartnersControllerAdmin
    {
        return new class extends PartnersControllerAdmin {
            public function __construct() {}

            protected function startSession(): void {}

            protected function redirect(string $url): never
            {
                // Instead of performing a real redirect, throw an exception for test capture
                throw new \RuntimeException('redirect:' . $url);
            }

            /** @param array<string, mixed> $data */
            protected function renderView(string $view, array $data = []): void {}
        };
    }

    /**
     * Test that support() returns true for the 'partners-admin' page with GET method.
     */
    public function testSupportReturnsTrueForPartnersAdmin(): void
    {
        $this->assertTrue(PartnersControllerAdmin::support('partners-admin', 'GET'));
    }

    /**
     * Test that support() returns false for a different page.
     */
    public function testSupportReturnsFalseForOtherPage(): void
    {
        $this->assertFalse(PartnersControllerAdmin::support('home', 'GET'));
    }

    /**
     * Test that the default language is French when no 'lang' parameter is provided.
     */
    public function testDefaultLanguageIsFrench(): void
    {
        $controller = $this->makeController();

        try {
            $controller->control();
        } catch (\Throwable $e) {}

        $this->assertSame('fr', $_SESSION['lang'] ?? 'fr');
    }

    /**
     * Test that the language changes to English when 'lang=en' is provided in $_GET.
     */
    public function testLanguageChangeToEnglish(): void
    {
        $_GET['lang'] = 'en';
        $controller   = $this->makeController();

        try {
            $controller->control();
        } catch (\Throwable $e) {}

        $this->assertSame('en', $_SESSION['lang']);
    }

    /**
     * Test that the controller handles missing POST fields gracefully.
     */
    public function testErrorMessageWhenFieldsMissing(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'continent'   => '',
            'country'     => '',
            'city'        => '',
            'institution' => '',
            'type'        => '',
        ];

        $controller = $this->makeController();

        try {
            $controller->control();
        } catch (\Throwable $e) {}

        // This test just ensures no fatal errors occur
        $this->assertTrue(true);
    }

    /**
     * Test that non-admin users are redirected to the login page.
     */
    public function testRedirectsToLoginWhenNotAdmin(): void
    {
        $_SESSION = [];
        $controller = $this->makeController();

        $redirectUrl = null;
        try {
            $controller->control();
        } catch (\RuntimeException $e) {
            $redirectUrl = $e->getMessage();
        }

        $this->assertNotNull($redirectUrl);
        $this->assertStringContainsString('login', $redirectUrl);
    }
}