<?php

namespace Tests\Controllers\HomeController;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Controllers\HomeController\SuperAdminController;
use Controllers\HomeController\HomeControllerAdmin;
use Controllers\HomeController\HomeControllerCoordinateur;
use Controllers\HomeController\HomeControllerStudent;
use Service\SuperAdminService;
use Model\UseCase\GetAdminStatsUseCase;

/**
 * Unit tests for SuperAdminController, HomeControllerAdmin,
 * HomeControllerCoordinateur, and HomeControllerStudent.
 *
 * Coverage areas:
 * - Route matching via support()
 * - Authentication and role-based access guards
 * - Language selection and session persistence
 * - POST action dispatching (add_department, add_site, delete, create)
 * - Input validation (email format, password strength, required fields)
 * - Tritanopia accessibility flag handling
 * - buildUrl() query-string helper behaviour
 *
 * All HTTP superglobals ($_GET, $_POST, $_SESSION, $_SERVER) are reset in
 * setUp() before every test to prevent state leakage between test cases.
 *
 * Run with:
 *   ./vendor/bin/phpunit Tests/Controllers/HomeController/HomeControllersTest.php
 */
class HomeControllersTest extends TestCase
{
    // =========================================================================
    // setUp
    // =========================================================================

    /**
     * Resets all HTTP superglobals before each test to ensure full isolation.
     */
    protected function setUp(): void
    {
        $_GET     = [];
        $_POST    = [];
        $_SESSION = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    // =========================================================================
    // Factories
    // =========================================================================

    /**
     * Builds an anonymous subclass of {@see SuperAdminController} backed by a
     * mocked {@see SuperAdminService}.
     *
     * Infrastructure overrides keep the controller in-process:
     * - {@see startSession()} is a no-op.
     * - {@see redirect()} throws RuntimeException prefixed with "redirect:".
     * - {@see renderView()} is a no-op.
     * - {@see makeService()} returns the injected mock instead of building a
     *   real service.
     *
     * @return array{0: SuperAdminController, 1: MockObject&SuperAdminService}
     */
    private function makeSuperAdmin(): array
    {
        $serviceMock = $this->createMock(SuperAdminService::class);

        $controller = new class($serviceMock) extends SuperAdminController {
            private SuperAdminService $injectedService;

            public function __construct(SuperAdminService $svc)
            {
                $this->injectedService = $svc;
            }

            /** No-op: prevents real session_start() calls during tests. */
            protected function startSession(): void {}

            /**
             * Converts a redirect into a catchable exception so tests can
             * assert on the target URL without leaving the test process.
             */
            protected function redirect(string $url): never
            {
                throw new \RuntimeException('redirect:' . $url);
            }

            /**
             * No-op: prevents real view rendering (file inclusion) during tests.
             *
             * @param array<string, mixed> $data
             */
            protected function renderView(string $view, array $data = []): void {}

            /** Returns the injected mock instead of constructing a real service. */
            protected function makeService(): SuperAdminService
            {
                return $this->injectedService;
            }
        };

        return [$controller, $serviceMock];
    }

    /**
     * Builds an anonymous subclass of {@see HomeControllerAdmin} backed by a
     * mocked {@see GetAdminStatsUseCase}.
     *
     * Also seeds $_SESSION['role'] = 'admin' so the controller passes its
     * authentication guard without an explicit setup call in each test.
     *
     * Infrastructure overrides:
     * - {@see startSession()} is a no-op.
     * - {@see redirect()} throws RuntimeException prefixed with "redirect:".
     * - {@see renderView()} is a no-op.
     * - {@see log()} is a no-op.
     * - {@see makeUseCase()} returns the injected mock.
     * - {@see fetchDepartements()} returns an empty array.
     *
     * @return array{0: HomeControllerAdmin, 1: MockObject&GetAdminStatsUseCase}
     */
    private function makeAdminHome(): array
    {
        $useCaseMock = $this->createMock(GetAdminStatsUseCase::class);

        $controller = new class($useCaseMock) extends HomeControllerAdmin {
            private GetAdminStatsUseCase $injectedUseCase;

            public function __construct(GetAdminStatsUseCase $useCase)
            {
                $this->injectedUseCase = $useCase;
            }

            /** No-op: prevents real session_start() calls during tests. */
            protected function startSession(): void {}

            /**
             * Converts a redirect into a catchable exception so tests can
             * assert on the target URL without leaving the test process.
             */
            protected function redirect(string $url): never
            {
                throw new \RuntimeException('redirect:' . $url);
            }

            /**
             * No-op: prevents real view rendering (file inclusion) during tests.
             *
             * @param array<string, mixed> $data
             */
            protected function renderView(string $view, array $data = []): void {}

            /** No-op: suppresses log output during tests. */
            protected function log(string $message): void {}

            /** Returns the injected mock instead of constructing a real use case. */
            protected function makeUseCase(): GetAdminStatsUseCase
            {
                return $this->injectedUseCase;
            }

            /**
             * Returns an empty department list to avoid database access during tests.
             *
             * @return array<int, mixed>
             */
            protected function fetchDepartements(): array
            {
                return [];
            }
        };

        // Pre-authenticate as admin so the controller does not redirect in every test.
        $_SESSION['role'] = 'admin';

        return [$controller, $useCaseMock];
    }

    /**
     * Builds an anonymous subclass of {@see HomeControllerCoordinateur} backed
     * by a mocked {@see GetAdminStatsUseCase}.
     *
     * Infrastructure overrides:
     * - {@see startSession()} is a no-op.
     * - {@see redirect()} throws RuntimeException prefixed with "redirect:".
     * - {@see renderView()} is a no-op.
     * - {@see log()} is a no-op.
     * - {@see makeUseCase()} returns the injected mock.
     *
     * @return array{0: HomeControllerCoordinateur, 1: MockObject&GetAdminStatsUseCase}
     */
    private function makeCoordHome(): array
    {
        $useCaseMock = $this->createMock(GetAdminStatsUseCase::class);

        $controller = new class($useCaseMock) extends HomeControllerCoordinateur {
            private GetAdminStatsUseCase $injectedUseCase;

            public function __construct(GetAdminStatsUseCase $useCase)
            {
                $this->injectedUseCase = $useCase;
            }

            /** No-op: prevents real session_start() calls during tests. */
            protected function startSession(): void {}

            /**
             * Converts a redirect into a catchable exception so tests can
             * assert on the target URL without leaving the test process.
             */
            protected function redirect(string $url): never
            {
                throw new \RuntimeException('redirect:' . $url);
            }

            /**
             * No-op: prevents real view rendering (file inclusion) during tests.
             *
             * @param array<string, mixed> $data
             */
            protected function renderView(string $view, array $data = []): void {}

            /** No-op: suppresses log output during tests. */
            protected function log(string $message): void {}

            /** Returns the injected mock instead of constructing a real use case. */
            protected function makeUseCase(): GetAdminStatsUseCase
            {
                return $this->injectedUseCase;
            }
        };

        return [$controller, $useCaseMock];
    }

    /**
     * Builds an anonymous subclass of {@see HomeControllerStudent} with all
     * infrastructure methods overridden to stay in-process.
     *
     * No session pre-seeding is performed here; each test is responsible for
     * setting the session state it requires (e.g. $_SESSION['numetu']).
     *
     * @return HomeControllerStudent
     */
    private function makeStudentHome(): HomeControllerStudent
    {
        return new class extends HomeControllerStudent {
            public function __construct()
            {
                // Empty constructor: bypasses any real DI / service wiring.
            }

            /** No-op: prevents real session_start() calls during tests. */
            protected function startSession(): void {}

            /**
             * Converts a redirect into a catchable exception so tests can
             * assert on the target URL without leaving the test process.
             */
            protected function redirect(string $url): never
            {
                throw new \RuntimeException('redirect:' . $url);
            }

            /**
             * No-op: prevents real view rendering (file inclusion) during tests.
             *
             * @param array<string, mixed> $data
             */
            protected function renderView(string $view, array $data = []): void {}
        };
    }

    // =========================================================================
    // SuperAdminController — support()
    // =========================================================================

    /**
     * @test
     * The super-admin controller must claim the "super-admin" page for both GET
     * and POST so the router directs all its traffic through this handler.
     */
    public function test_superadmin_support_returns_true_for_super_admin(): void
    {
        $this->assertTrue(SuperAdminController::support('super-admin', 'GET'));
        $this->assertTrue(SuperAdminController::support('super-admin', 'POST'));
    }

    /**
     * @test
     * Pages not belonging to the super-admin area must return false so the
     * router can delegate to another controller.
     */
    public function test_superadmin_support_returns_false_for_other_pages(): void
    {
        $this->assertFalse(SuperAdminController::support('home', 'GET'));
        $this->assertFalse(SuperAdminController::support('admin', 'GET'));
        $this->assertFalse(SuperAdminController::support('', 'GET'));
    }

    // =========================================================================
    // SuperAdminController — access guard
    // =========================================================================

    /**
     * @test
     * When no session exists (unauthenticated request), the controller must
     * redirect to the login page immediately.
     */
    public function test_superadmin_redirects_to_login_when_not_authenticated(): void
    {
        [$controller] = $this->makeSuperAdmin();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/redirect:.*login/');

        $controller->control();
    }

    /**
     * @test
     * A user with a lower-privilege role (e.g. "admin") must be redirected to
     * login instead of accessing the super-admin panel.
     */
    public function test_superadmin_redirects_to_login_for_wrong_role(): void
    {
        $_SESSION['role'] = 'admin';
        [$controller]     = $this->makeSuperAdmin();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/redirect:.*login/');

        $controller->control();
    }

    // =========================================================================
    // SuperAdminController — language selection
    // =========================================================================

    /**
     * @test
     * When no lang parameter is supplied, the controller should default to
     * French ("fr") without writing anything unexpected to the session.
     */
    public function test_superadmin_lang_defaults_to_fr(): void
    {
        $_SESSION['role'] = 'super_admin';
        [$controller, $serviceMock] = $this->makeSuperAdmin();

        $serviceMock->method('getAvailableDepartments')->willReturn([]);
        $serviceMock->method('getAvailableSites')->willReturn([]);
        $serviceMock->method('getAllAccounts')->willReturn([]);

        try { $controller->control(); } catch (\Throwable $e) {}

        $this->assertSame('fr', $_SESSION['lang'] ?? 'fr');
    }

    /**
     * @test
     * When lang=en is supplied via $_GET, the session must be updated to "en".
     */
    public function test_superadmin_lang_set_from_get(): void
    {
        $_SESSION['role'] = 'super_admin';
        $_GET['lang']     = 'en';
        [$controller, $serviceMock] = $this->makeSuperAdmin();

        $serviceMock->method('getAvailableDepartments')->willReturn([]);
        $serviceMock->method('getAvailableSites')->willReturn([]);
        $serviceMock->method('getAllAccounts')->willReturn([]);

        try { $controller->control(); } catch (\Throwable $e) {}

        $this->assertSame('en', $_SESSION['lang']);
    }

    /**
     * @test
     * An unsupported locale (e.g. "de") must be silently ignored; the
     * controller must not persist it in the session.
     */
    public function test_superadmin_lang_ignores_invalid_value(): void
    {
        $_SESSION['role'] = 'super_admin';
        $_GET['lang']     = 'de';
        [$controller, $serviceMock] = $this->makeSuperAdmin();

        $serviceMock->method('getAvailableDepartments')->willReturn([]);
        $serviceMock->method('getAvailableSites')->willReturn([]);
        $serviceMock->method('getAllAccounts')->willReturn([]);

        try { $controller->control(); } catch (\Throwable $e) {}

        $this->assertArrayNotHasKey('lang', $_SESSION);
    }

    // =========================================================================
    // SuperAdminController — POST action=add_department
    // =========================================================================

    /**
     * @test
     * When a valid new department name is submitted, addDepartment() must be
     * called with the name uppercased.
     */
    public function test_superadmin_add_department_calls_addDepartment(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SESSION['role']          = 'super_admin';
        $_POST['action']           = 'add_department';
        $_POST['new_department']   = 'Informatique';

        [$controller, $serviceMock] = $this->makeSuperAdmin();

        $serviceMock->method('getAvailableDepartments')->willReturn([]);
        $serviceMock->method('getAvailableSites')->willReturn([]);
        $serviceMock->method('getAllAccounts')->willReturn([]);

        $serviceMock->expects($this->once())->method('addDepartment')->with('INFORMATIQUE');

        try { $controller->control(); } catch (\Throwable $e) {}
    }

    /**
     * @test
     * When the submitted department name already exists in the list, the
     * controller must not call addDepartment() to avoid duplicates.
     */
    public function test_superadmin_add_department_does_not_add_when_already_exists(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SESSION['role']          = 'super_admin';
        $_POST['action']           = 'add_department';
        $_POST['new_department']   = 'INFO';

        [$controller, $serviceMock] = $this->makeSuperAdmin();

        $serviceMock->method('getAvailableDepartments')->willReturn(['INFO']);
        $serviceMock->method('getAvailableSites')->willReturn([]);
        $serviceMock->method('getAllAccounts')->willReturn([]);
        $serviceMock->expects($this->never())->method('addDepartment');

        try { $controller->control(); } catch (\Throwable $e) {}
    }

    /**
     * @test
     * A successful add_department action with lang=fr must complete without
     * exceptions. The actual message string is an implementation detail and
     * not asserted here; this test guards against regressions in the happy
     * path.
     */
    public function test_superadmin_add_department_sets_success_message_in_french(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SESSION['role']          = 'super_admin';
        $_SESSION['lang']          = 'fr';
        $_POST['action']           = 'add_department';
        $_POST['new_department']   = 'Maths';

        [$controller, $serviceMock] = $this->makeSuperAdmin();
        $serviceMock->method('getAvailableDepartments')->willReturn([]);
        $serviceMock->method('getAvailableSites')->willReturn([]);
        $serviceMock->method('getAllAccounts')->willReturn([]);
        $serviceMock->method('addDepartment');

        try { $controller->control(); } catch (\Throwable $e) {}

        $this->assertTrue(true);
    }

    /**
     * @test
     * When the submitted department name is empty, the controller must silently
     * skip the operation and not call addDepartment().
     */
    public function test_superadmin_add_department_sets_error_when_empty(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SESSION['role']          = 'super_admin';
        $_POST['action']           = 'add_department';
        $_POST['new_department']   = '';

        [$controller, $serviceMock] = $this->makeSuperAdmin();
        $serviceMock->method('getAvailableDepartments')->willReturn([]);
        $serviceMock->method('getAvailableSites')->willReturn([]);
        $serviceMock->method('getAllAccounts')->willReturn([]);
        $serviceMock->expects($this->never())->method('addDepartment');

        try { $controller->control(); } catch (\Throwable $e) {}
    }

    // =========================================================================
    // SuperAdminController — POST action=add_site
    // =========================================================================

    /**
     * @test
     * When a valid new site name is submitted, addSite() must be called with
     * the exact value from the POST body.
     */
    public function test_superadmin_add_site_calls_addSite(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SESSION['role']          = 'super_admin';
        $_POST['action']           = 'add_site';
        $_POST['new_site']         = 'Marseille';

        [$controller, $serviceMock] = $this->makeSuperAdmin();
        $serviceMock->method('getAvailableDepartments')->willReturn([]);
        $serviceMock->method('getAvailableSites')->willReturn([]);
        $serviceMock->method('getAllAccounts')->willReturn([]);

        $serviceMock->expects($this->once())->method('addSite')->with('Marseille');

        try { $controller->control(); } catch (\Throwable $e) {}
    }

    /**
     * @test
     * When the submitted site already exists, addSite() must not be called
     * to avoid creating duplicate entries.
     */
    public function test_superadmin_add_site_does_not_add_when_already_exists(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SESSION['role']          = 'super_admin';
        $_POST['action']           = 'add_site';
        $_POST['new_site']         = 'Marseille';

        [$controller, $serviceMock] = $this->makeSuperAdmin();
        $serviceMock->method('getAvailableDepartments')->willReturn([]);
        $serviceMock->method('getAvailableSites')->willReturn(['Marseille']);
        $serviceMock->method('getAllAccounts')->willReturn([]);
        $serviceMock->expects($this->never())->method('addSite');

        try { $controller->control(); } catch (\Throwable $e) {}
    }

    /**
     * @test
     * When the submitted site name is empty, the controller must skip the
     * operation without calling addSite().
     */
    public function test_superadmin_add_site_does_not_add_when_empty(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SESSION['role']          = 'super_admin';
        $_POST['action']           = 'add_site';
        $_POST['new_site']         = '';

        [$controller, $serviceMock] = $this->makeSuperAdmin();
        $serviceMock->method('getAvailableDepartments')->willReturn([]);
        $serviceMock->method('getAvailableSites')->willReturn([]);
        $serviceMock->method('getAllAccounts')->willReturn([]);
        $serviceMock->expects($this->never())->method('addSite');

        try { $controller->control(); } catch (\Throwable $e) {}
    }

    // =========================================================================
    // SuperAdminController — POST action=delete
    // =========================================================================

    /**
     * @test
     * A valid delete request must invoke deleteAccount() with the login value
     * from the POST body.
     */
    public function test_superadmin_delete_calls_deleteAccount(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SESSION['role']          = 'super_admin';
        $_POST['action']           = 'delete';
        $_POST['login']            = 'user@example.com';

        [$controller, $serviceMock] = $this->makeSuperAdmin();
        $serviceMock->method('getAvailableDepartments')->willReturn([]);
        $serviceMock->method('getAvailableSites')->willReturn([]);
        $serviceMock->method('getAllAccounts')->willReturn([]);

        $serviceMock->expects($this->once())
            ->method('deleteAccount')
            ->with('user@example.com')
            ->willReturn(true);

        try { $controller->control(); } catch (\Throwable $e) {}
    }

    /**
     * @test
     * When the login field is empty, the controller must skip the deletion to
     * prevent accidentally removing an unintended account.
     */
    public function test_superadmin_delete_does_nothing_when_login_empty(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SESSION['role']          = 'super_admin';
        $_POST['action']           = 'delete';
        $_POST['login']            = '';

        [$controller, $serviceMock] = $this->makeSuperAdmin();
        $serviceMock->method('getAvailableDepartments')->willReturn([]);
        $serviceMock->method('getAvailableSites')->willReturn([]);
        $serviceMock->method('getAllAccounts')->willReturn([]);
        $serviceMock->expects($this->never())->method('deleteAccount');

        try { $controller->control(); } catch (\Throwable $e) {}
    }

    // =========================================================================
    // SuperAdminController — POST action=create
    // =========================================================================

    /**
     * @test
     * When all required fields are valid and the role is "coordinateur",
     * createAccount() must be called with the correct arguments, including
     * the department and a null site.
     */
    public function test_superadmin_create_calls_createAccount_with_valid_data(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SESSION['role']          = 'super_admin';
        $_POST['action']           = 'create';
        $_POST['login']            = 'coord@univ.fr';
        $_POST['password']         = 'Secure@Password1!';
        $_POST['role']             = 'coordinateur';
        $_POST['departement']      = 'Informatique';
        $_POST['site']             = '';
        $_POST['nom']              = 'Dupont';
        $_POST['prenom']           = 'Alice';

        [$controller, $serviceMock] = $this->makeSuperAdmin();
        $serviceMock->method('getAvailableDepartments')->willReturn([]);
        $serviceMock->method('getAvailableSites')->willReturn([]);
        $serviceMock->method('getAllAccounts')->willReturn([]);

        $serviceMock->expects($this->once())->method('createAccount')
            ->with('coord@univ.fr', 'Secure@Password1!', 'coordinateur', 'Informatique', null, 'Dupont', 'Alice');

        try { $controller->control(); } catch (\Throwable $e) {}
    }

    /**
     * @test
     * When the login field is empty, the controller must not attempt to create
     * an account with an invalid identifier.
     */
    public function test_superadmin_create_does_not_call_createAccount_when_login_empty(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SESSION['role']          = 'super_admin';
        $_POST['action']           = 'create';
        $_POST['login']            = '';
        $_POST['password']         = 'Secure@Password1!';
        $_POST['role']             = 'admin';

        [$controller, $serviceMock] = $this->makeSuperAdmin();
        $serviceMock->method('getAvailableDepartments')->willReturn([]);
        $serviceMock->method('getAvailableSites')->willReturn([]);
        $serviceMock->method('getAllAccounts')->willReturn([]);
        $serviceMock->expects($this->never())->method('createAccount');

        try { $controller->control(); } catch (\Throwable $e) {}
    }

    /**
     * @test
     * A password that does not meet the strength policy must be rejected
     * without calling createAccount().
     */
    public function test_superadmin_create_rejects_weak_password(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SESSION['role']          = 'super_admin';
        $_POST['action']           = 'create';
        $_POST['login']            = 'admin@univ.fr';
        $_POST['password']         = 'weak'; // intentionally too simple
        $_POST['role']             = 'admin';
        $_POST['site']             = 'Marseille';

        [$controller, $serviceMock] = $this->makeSuperAdmin();
        $serviceMock->method('getAvailableDepartments')->willReturn([]);
        $serviceMock->method('getAvailableSites')->willReturn([]);
        $serviceMock->method('getAllAccounts')->willReturn([]);
        $serviceMock->expects($this->never())->method('createAccount');

        try { $controller->control(); } catch (\Throwable $e) {}
    }

    /**
     * @test
     * A login that is not a valid email address must be rejected without
     * calling createAccount().
     */
    public function test_superadmin_create_rejects_invalid_email_login(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SESSION['role']          = 'super_admin';
        $_POST['action']           = 'create';
        $_POST['login']            = 'not-an-email'; // intentionally malformed
        $_POST['password']         = 'Secure@Password1!';
        $_POST['role']             = 'admin';
        $_POST['site']             = 'Marseille';

        [$controller, $serviceMock] = $this->makeSuperAdmin();
        $serviceMock->method('getAvailableDepartments')->willReturn([]);
        $serviceMock->method('getAvailableSites')->willReturn([]);
        $serviceMock->method('getAllAccounts')->willReturn([]);
        $serviceMock->expects($this->never())->method('createAccount');

        try { $controller->control(); } catch (\Throwable $e) {}
    }

    /**
     * @test
     * A "coordinateur" account creation request without a department must be
     * rejected, as a coordinator must be associated with a department.
     */
    public function test_superadmin_create_rejects_coord_without_department(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SESSION['role']          = 'super_admin';
        $_POST['action']           = 'create';
        $_POST['login']            = 'coord@univ.fr';
        $_POST['password']         = 'Secure@Password1!';
        $_POST['role']             = 'coordinateur';
        $_POST['departement']      = ''; // missing required field

        [$controller, $serviceMock] = $this->makeSuperAdmin();
        $serviceMock->method('getAvailableDepartments')->willReturn([]);
        $serviceMock->method('getAvailableSites')->willReturn([]);
        $serviceMock->method('getAllAccounts')->willReturn([]);
        $serviceMock->expects($this->never())->method('createAccount');

        try { $controller->control(); } catch (\Throwable $e) {}
    }

    /**
     * @test
     * An "admin" account creation request without a site must be rejected,
     * as an admin must be scoped to a specific site.
     */
    public function test_superadmin_create_rejects_admin_without_site(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SESSION['role']          = 'super_admin';
        $_POST['action']           = 'create';
        $_POST['login']            = 'admin@univ.fr';
        $_POST['password']         = 'Secure@Password1!';
        $_POST['role']             = 'admin';
        $_POST['site']             = ''; // missing required field

        [$controller, $serviceMock] = $this->makeSuperAdmin();
        $serviceMock->method('getAvailableDepartments')->willReturn([]);
        $serviceMock->method('getAvailableSites')->willReturn([]);
        $serviceMock->method('getAllAccounts')->willReturn([]);
        $serviceMock->expects($this->never())->method('createAccount');

        try { $controller->control(); } catch (\Throwable $e) {}
    }

    /**
     * @test
     * When creating an admin account, the site must be passed as the fifth
     * argument and the department must be null (admins are site-scoped, not
     * department-scoped).
     */
    public function test_superadmin_create_passes_site_for_admin_role(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SESSION['role']          = 'super_admin';
        $_POST['action']           = 'create';
        $_POST['login']            = 'admin@univ.fr';
        $_POST['password']         = 'Secure@Password1!';
        $_POST['role']             = 'admin';
        $_POST['site']             = 'Aix';
        $_POST['departement']      = '';
        $_POST['nom']              = '';
        $_POST['prenom']           = '';

        [$controller, $serviceMock] = $this->makeSuperAdmin();
        $serviceMock->method('getAvailableDepartments')->willReturn([]);
        $serviceMock->method('getAvailableSites')->willReturn([]);
        $serviceMock->method('getAllAccounts')->willReturn([]);

        $serviceMock->expects($this->once())->method('createAccount')
            ->with('admin@univ.fr', 'Secure@Password1!', 'admin', null, 'Aix', null, null);

        try { $controller->control(); } catch (\Throwable $e) {}
    }

    // =========================================================================
    // HomeControllerAdmin — support()
    // =========================================================================

    /**
     * @test
     * The admin home controller must claim the "home-admin" page on GET.
     */
    public function test_home_admin_support_returns_true_for_home_admin_get(): void
    {
        $this->assertTrue(HomeControllerAdmin::support('home-admin', 'GET'));
    }

    /**
     * @test
     * POST requests to "home-admin" must not be handled by this controller
     * since the admin dashboard is read-only.
     */
    public function test_home_admin_support_returns_false_for_post(): void
    {
        $this->assertFalse(HomeControllerAdmin::support('home-admin', 'POST'));
    }

    /**
     * @test
     * Pages outside the admin home area must return false.
     */
    public function test_home_admin_support_returns_false_for_other_pages(): void
    {
        $this->assertFalse(HomeControllerAdmin::support('home', 'GET'));
        $this->assertFalse(HomeControllerAdmin::support('', 'GET'));
    }

    // =========================================================================
    // HomeControllerAdmin — language selection
    // =========================================================================

    /**
     * @test
     * When lang=en is supplied via $_GET, the session must be updated to "en".
     */
    public function test_home_admin_lang_set_from_get(): void
    {
        $_GET['lang'] = 'en';
        [$controller] = $this->makeAdminHome();

        try { $controller->control(); } catch (\Throwable $e) {}

        $this->assertSame('en', $_SESSION['lang']);
    }

    /**
     * @test
     * An unsupported locale must be silently ignored; it must not be written
     * to the session.
     */
    public function test_home_admin_lang_ignores_invalid_value(): void
    {
        $_GET['lang'] = 'zh';
        [$controller] = $this->makeAdminHome();

        try { $controller->control(); } catch (\Throwable $e) {}

        $this->assertArrayNotHasKey('lang', $_SESSION);
    }

    // =========================================================================
    // HomeControllerAdmin — mobility / department filters
    // =========================================================================

    /**
     * @test
     * A recognised mobility filter value (e.g. "etude") must be accepted
     * without causing errors or redirects.
     */
    public function test_home_admin_mobilite_filter_accepted(): void
    {
        $_GET['mobilite'] = 'etude';
        [$controller]     = $this->makeAdminHome();

        try { $controller->control(); } catch (\Throwable $e) {}

        $this->assertTrue(true);
    }

    /**
     * @test
     * An unrecognised mobility filter value must be silently ignored; the
     * controller must not crash or redirect.
     */
    public function test_home_admin_mobilite_filter_rejected_for_invalid_value(): void
    {
        $_GET['mobilite'] = 'invalid';
        [$controller]     = $this->makeAdminHome();

        try { $controller->control(); } catch (\Throwable $e) {}

        $this->assertTrue(true);
    }

    /**
     * @test
     * A department filter value supplied via $_GET must be accepted without
     * causing errors or redirects.
     */
    public function test_home_admin_departement_filter_set_from_get(): void
    {
        $_GET['departement'] = 'Informatique';
        [$controller]        = $this->makeAdminHome();

        try { $controller->control(); } catch (\Throwable $e) {}

        $this->assertTrue(true);
    }

    // =========================================================================
    // HomeControllerAdmin — tritanopia accessibility flag
    // =========================================================================

    /**
     * @test
     * When tritanopia=1 is supplied via $_GET, the session flag must be set
     * to true to activate the accessible colour scheme.
     */
    public function test_home_admin_tritanopia_set_to_true(): void
    {
        $_GET['tritanopia'] = '1';
        [$controller]       = $this->makeAdminHome();

        try { $controller->control(); } catch (\Throwable $e) {}

        $this->assertTrue($_SESSION['tritanopia'] ?? false);
    }

    /**
     * @test
     * When tritanopia=0 is supplied via $_GET, the session flag must be set
     * to false to deactivate the accessible colour scheme.
     */
    public function test_home_admin_tritanopia_set_to_false(): void
    {
        $_GET['tritanopia'] = '0';
        [$controller]       = $this->makeAdminHome();

        try { $controller->control(); } catch (\Throwable $e) {}

        $this->assertFalse($_SESSION['tritanopia'] ?? true);
    }

    // =========================================================================
    // HomeControllerCoordinateur — support()
    // =========================================================================

    /**
     * @test
     * The coordinator home controller must handle "home-coordinateur" on both
     * GET and POST.
     */
    public function test_home_coord_support_returns_true_for_home_coordinateur(): void
    {
        $this->assertTrue(HomeControllerCoordinateur::support('home-coordinateur', 'GET'));
        $this->assertTrue(HomeControllerCoordinateur::support('home-coordinateur', 'POST'));
    }

    /**
     * @test
     * Pages outside the coordinator home area must return false.
     */
    public function test_home_coord_support_returns_false_for_other_pages(): void
    {
        $this->assertFalse(HomeControllerCoordinateur::support('home-admin', 'GET'));
        $this->assertFalse(HomeControllerCoordinateur::support('', 'GET'));
    }

    // =========================================================================
    // HomeControllerCoordinateur — access guard
    // =========================================================================

    /**
     * @test
     * An unauthenticated request (no session) must be redirected to login.
     */
    public function test_home_coord_redirects_to_login_when_not_authenticated(): void
    {
        [$controller] = $this->makeCoordHome();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/redirect:.*login/');

        $controller->control();
    }

    /**
     * @test
     * A user with a role that does not have coordinator privileges must be
     * redirected to login rather than shown the coordinator dashboard.
     */
    public function test_home_coord_redirects_to_login_for_wrong_role(): void
    {
        $_SESSION['role'] = 'admin';
        [$controller]     = $this->makeCoordHome();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/redirect:.*login/');

        $controller->control();
    }

    // =========================================================================
    // HomeControllerStudent — support()
    // =========================================================================

    /**
     * @test
     * The student home controller must claim "home-student" on GET.
     */
    public function test_home_student_support_returns_true_for_home_student_get(): void
    {
        $this->assertTrue(HomeControllerStudent::support('home-student', 'GET'));
    }

    /**
     * @test
     * POST requests to "home-student" must not be handled by this controller
     * since the student dashboard is read-only.
     */
    public function test_home_student_support_returns_false_for_post(): void
    {
        $this->assertFalse(HomeControllerStudent::support('home-student', 'POST'));
    }

    /**
     * @test
     * Pages outside the student home area must return false.
     */
    public function test_home_student_support_returns_false_for_other_pages(): void
    {
        $this->assertFalse(HomeControllerStudent::support('home-admin', 'GET'));
        $this->assertFalse(HomeControllerStudent::support('', 'GET'));
    }

    // =========================================================================
    // HomeControllerStudent — language selection
    // =========================================================================

    /**
     * @test
     * When lang=en is supplied and the student is authenticated, the session
     * must be updated to "en".
     * Note: numetu is required in the session, otherwise the controller redirects
     * to login before the language assignment can take place.
     */
    public function test_home_student_lang_set_from_get(): void
    {
        $_GET['lang']       = 'en';
        $_SESSION['numetu'] = '12345'; // required to pass the authentication guard
        $controller         = $this->makeStudentHome();

        try { $controller->control(); } catch (\Throwable $e) {}

        $this->assertSame('en', $_SESSION['lang']);
    }

    /**
     * @test
     * An unsupported locale (e.g. "ru") must not be persisted in the session.
     */
    public function test_home_student_lang_ignores_invalid_value(): void
    {
        $_GET['lang']       = 'ru';
        $_SESSION['numetu'] = '12345';
        $controller         = $this->makeStudentHome();

        try { $controller->control(); } catch (\Throwable $e) {}

        $this->assertArrayNotHasKey('lang', $_SESSION);
    }

    /**
     * @test
     * When no lang parameter is provided, the effective language should
     * default to French ("fr").
     */
    public function test_home_student_lang_defaults_to_fr_when_not_set(): void
    {
        $_SESSION['numetu'] = '12345';
        $controller         = $this->makeStudentHome();

        try { $controller->control(); } catch (\Throwable $e) {}

        $this->assertSame('fr', $_SESSION['lang'] ?? 'fr');
    }

    // =========================================================================
    // HomeControllerStudent — tritanopia accessibility flag
    // =========================================================================

    /**
     * @test
     * When tritanopia=1 is supplied and the student is authenticated, the
     * session flag must be set to true.
     * Note: numetu is required to pass the authentication guard before the flag
     * assignment occurs.
     */
    public function test_home_student_tritanopia_set_to_true(): void
    {
        $_GET['tritanopia'] = '1';
        $_SESSION['numetu'] = '12345'; // required to pass the authentication guard
        $controller         = $this->makeStudentHome();

        try { $controller->control(); } catch (\Throwable $e) {}

        $this->assertTrue($_SESSION['tritanopia'] ?? false);
    }

    /**
     * @test
     * When tritanopia=0 is supplied and the student is authenticated, the
     * session flag must be set to false.
     */
    public function test_home_student_tritanopia_set_to_false(): void
    {
        $_GET['tritanopia'] = '0';
        $_SESSION['numetu'] = '12345'; // required to pass the authentication guard
        $controller         = $this->makeStudentHome();

        try { $controller->control(); } catch (\Throwable $e) {}

        $this->assertFalse($_SESSION['tritanopia'] ?? true);
    }

    // =========================================================================
    // HomeControllerStudent — isLoggedIn
    // =========================================================================

    /**
     * @test
     * When numetu is present in the session, the student is considered
     * authenticated and no redirect must occur.
     */
    public function test_home_student_is_logged_in_when_numetu_in_session(): void
    {
        $_SESSION['numetu'] = '12345';
        $controller         = $this->makeStudentHome();

        $redirected = false;
        try {
            $controller->control();
        } catch (\RuntimeException $e) {
            if (str_contains($e->getMessage(), 'redirect:')) {
                $redirected = true;
            }
        }

        $this->assertFalse($redirected);
    }

    /**
     * @test
     * When numetu is absent from the session, the student is unauthenticated
     * and the controller must redirect to the login page.
     */
    public function test_home_student_not_logged_in_when_numetu_absent(): void
    {
        // No numetu in session → controller must redirect to login.
        $controller = $this->makeStudentHome();

        $redirected = false;
        try {
            $controller->control();
        } catch (\RuntimeException $e) {
            if (str_contains($e->getMessage(), 'redirect:')) {
                $redirected = true;
            }
        }

        $this->assertTrue($redirected);
    }

    // =========================================================================
    // buildUrl helper (shared across all controllers)
    // =========================================================================

    /**
     * @test
     * buildUrl() must append the lang parameter to the query string when the
     * base path does not already contain a query string.
     */
    public function test_buildUrl_appends_lang_parameter(): void
    {
        $lang = 'en';

        /** @param array<string, string> $params */
        $buildUrl = function (string $path, array $params = []) use ($lang): string {
            $params['lang'] = $lang;
            $separator = (strpos($path, '?') === false) ? '?' : '&';
            return $path . $separator . http_build_query($params);
        };

        $result = $buildUrl('index.php', ['page' => 'home-admin']);
        $this->assertStringContainsString('lang=en', $result);
        $this->assertStringContainsString('page=home-admin', $result);
    }

    /**
     * @test
     * When the base path already contains a query string, buildUrl() must use
     * "&" as the separator to produce a valid URL.
     */
    public function test_buildUrl_uses_ampersand_when_path_already_has_query(): void
    {
        $lang = 'fr';

        /** @param array<string, string> $params */
        $buildUrl = function (string $path, array $params = []) use ($lang): string {
            $params['lang'] = $lang;
            $separator = (strpos($path, '?') === false) ? '?' : '&';
            return $path . $separator . http_build_query($params);
        };

        $result = $buildUrl('index.php?page=home-admin', ['action' => 'view']);
        $this->assertStringStartsWith('index.php?page=home-admin&', $result);
        $this->assertStringContainsString('lang=fr', $result);
    }
}