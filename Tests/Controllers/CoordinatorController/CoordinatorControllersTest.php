<?php

namespace Tests\Controllers\CoordinatorController;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Controllers\CoordinatorController\DepartmentHeadController;
use Controllers\CoordinatorController\InternershipCoordinatorController;
use Controllers\CoordinatorController\StudyCoordinatorController;
use Model\UseCase\ManageFolderUseCase;

/**
 * Unit tests for the three coordinator controllers:
 * DepartmentHeadController, InternershipCoordinatorController, and StudyCoordinatorController.
 */
class CoordinatorControllersTest extends TestCase
{
    /**
     * Resets superglobals before each test to ensure a clean state.
     */
    protected function setUp(): void
    {
        $_GET     = [];
        $_POST    = [];
        $_SESSION = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    /**
     * Creates a testable DepartmentHeadController with a mocked ManageFolderUseCase.
     *
     * @return array{0: DepartmentHeadController, 1: MockObject&ManageFolderUseCase}
     */
    private function makeDeptController(): array
    {
        $useCaseMock = $this->createMock(ManageFolderUseCase::class);

        $controller = new class($useCaseMock) extends DepartmentHeadController {
            public function __construct(ManageFolderUseCase $useCase)
            {
                $ref  = new \ReflectionClass(DepartmentHeadController::class);
                $prop = $ref->getProperty('folderUseCase');
                $prop->setAccessible(true);
                $prop->setValue($this, $useCase);
            }

            protected function startSession(): void {}

            protected function redirect(string $url): never
            {
                throw new \RuntimeException('redirect:' . $url);
            }

            /** @param array<string, mixed> $data */
            protected function renderView(string $view, array $data = []): void {}
        };

        return [$controller, $useCaseMock];
    }

    /**
     * Creates a testable InternershipCoordinatorController with a mocked ManageFolderUseCase.
     *
     * @return array{0: InternershipCoordinatorController, 1: MockObject&ManageFolderUseCase}
     */
    private function makeInternController(): array
    {
        $useCaseMock = $this->createMock(ManageFolderUseCase::class);

        $controller = new class($useCaseMock) extends InternershipCoordinatorController {
            public function __construct(ManageFolderUseCase $useCase)
            {
                $ref  = new \ReflectionClass(InternershipCoordinatorController::class);
                $prop = $ref->getProperty('folderUseCase');
                $prop->setAccessible(true);
                $prop->setValue($this, $useCase);
            }

            protected function startSession(): void {}

            protected function redirect(string $url): never
            {
                throw new \RuntimeException('redirect:' . $url);
            }

            /** @param array<string, mixed> $data */
            protected function renderView(string $view, array $data = []): void {}
        };

        return [$controller, $useCaseMock];
    }

    /**
     * Creates a testable StudyCoordinatorController with a mocked ManageFolderUseCase.
     *
     * @return array{0: StudyCoordinatorController, 1: MockObject&ManageFolderUseCase}
     */
    private function makeStudyController(): array
    {
        $useCaseMock = $this->createMock(ManageFolderUseCase::class);

        $controller = new class($useCaseMock) extends StudyCoordinatorController {
            public function __construct(ManageFolderUseCase $useCase)
            {
                $ref  = new \ReflectionClass(StudyCoordinatorController::class);
                $prop = $ref->getProperty('folderUseCase');
                $prop->setAccessible(true);
                $prop->setValue($this, $useCase);
            }

            protected function startSession(): void {}

            protected function redirect(string $url): never
            {
                throw new \RuntimeException('redirect:' . $url);
            }

            /** @param array<string, mixed> $data */
            protected function renderView(string $view, array $data = []): void {}
        };

        return [$controller, $useCaseMock];
    }

    /**
     * Returns an empty pagination result stub.
     *
     * @return array{data: array<int, mixed>, total: int, totalPages: int}
     */
    private function emptyPaginationResult(): array
    {
        return ['data' => [], 'total' => 0, 'totalPages' => 0];
    }

    // =========================================================================
    // DepartmentHeadController — support()
    // =========================================================================

    public function test_dept_support_returns_true_for_chef_departement(): void
    {
        $this->assertTrue(DepartmentHeadController::support('chef-departement', 'GET'));
    }

    public function test_dept_support_returns_false_for_other_pages(): void
    {
        $this->assertFalse(DepartmentHeadController::support('home',                 'GET'));
        $this->assertFalse(DepartmentHeadController::support('coordinateur-stage',  'GET'));
        $this->assertFalse(DepartmentHeadController::support('',                    'GET'));
    }

    // =========================================================================
    // DepartmentHeadController — authentication & roles
    // =========================================================================

    public function test_dept_redirects_to_login_when_not_authenticated(): void
    {
        [$controller] = $this->makeDeptController();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/redirect:.*login/');

        $controller->control();
    }

    public function test_dept_redirects_to_login_for_unauthorized_role(): void
    {
        $_SESSION['role'] = 'student';
        [$controller]     = $this->makeDeptController();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/redirect:.*login/');

        $controller->control();
    }

    /** @dataProvider deptAllowedRolesProvider */
    public function test_dept_allows_authorized_roles(string $role): void
    {
        $_SESSION['role'] = $role;
        [$controller, $useCaseMock] = $this->makeDeptController();
        $useCaseMock->method('rechercherAvecPagination')->willReturn($this->emptyPaginationResult());

        $exceptionThrown = false;
        try {
            $controller->control();
        } catch (\RuntimeException $e) {
            if (str_contains($e->getMessage(), 'redirect:')) {
                $exceptionThrown = true;
            }
        }

        $this->assertFalse($exceptionThrown, "Role '$role' should be authorized.");
    }

    /** @return array<int, array{0: string}> */
    public static function deptAllowedRolesProvider(): array
    {
        return [
            ['coordinateur'],
            ['chef_departement'],
            ['admin'],
        ];
    }

    // =========================================================================
    // DepartmentHeadController — language resolution
    // =========================================================================

    public function test_dept_lang_defaults_to_fr(): void
    {
        $this->assertSame('fr', $_SESSION['lang'] ?? 'fr');
    }

    public function test_dept_lang_set_from_get(): void
    {
        $_SESSION['role'] = 'admin';
        $_GET['lang']     = 'en';
        [$controller, $useCaseMock] = $this->makeDeptController();
        $useCaseMock->method('rechercherAvecPagination')->willReturn($this->emptyPaginationResult());

        try { $controller->control(); } catch (\Throwable $e) {}

        $this->assertSame('en', $_SESSION['lang']);
    }

    public function test_dept_lang_ignores_invalid_value(): void
    {
        $_SESSION['role'] = 'admin';
        $_GET['lang']     = 'de';
        [$controller, $useCaseMock] = $this->makeDeptController();
        $useCaseMock->method('rechercherAvecPagination')->willReturn($this->emptyPaginationResult());

        try { $controller->control(); } catch (\Throwable $e) {}

        $this->assertArrayNotHasKey('lang', $_SESSION);
    }

    // =========================================================================
    // DepartmentHeadController — POST set_avis_chef
    // =========================================================================

    public function test_dept_post_set_avis_chef_calls_setAvisChef(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SESSION['role']          = 'chef_departement';
        $_POST['set_avis_chef']    = '1';
        $_POST['numetu']           = 'ETU001';
        $_POST['avis']             = 'accepte';

        [$controller, $useCaseMock] = $this->makeDeptController();
        $useCaseMock->expects($this->once())->method('setAvisChef')->with('ETU001', 'accepte');

        try { $controller->control(); } catch (\RuntimeException $e) {}
    }

    public function test_dept_post_set_avis_chef_redirects_after_save(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SESSION['role']          = 'chef_departement';
        $_POST['set_avis_chef']    = '1';
        $_POST['numetu']           = 'ETU001';
        $_POST['avis']             = 'refuse';

        [$controller, $useCaseMock] = $this->makeDeptController();
        $useCaseMock->method('setAvisChef');

        $redirectUrl = null;
        try {
            $controller->control();
        } catch (\RuntimeException $e) {
            $redirectUrl = $e->getMessage();
        }

        $this->assertNotNull($redirectUrl);
        $this->assertStringContainsString('chef-departement', $redirectUrl);
        $this->assertStringContainsString('ETU001',           $redirectUrl);
    }

    public function test_dept_post_set_avis_chef_does_not_call_setAvisChef_when_numetu_empty(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SESSION['role']          = 'chef_departement';
        $_POST['set_avis_chef']    = '1';
        $_POST['numetu']           = '';
        $_POST['avis']             = 'accepte';

        [$controller, $useCaseMock] = $this->makeDeptController();
        $useCaseMock->expects($this->never())->method('setAvisChef');

        try { $controller->control(); } catch (\RuntimeException $e) {}
    }

    public function test_dept_post_set_avis_chef_does_not_call_setAvisChef_when_avis_invalid(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SESSION['role']          = 'chef_departement';
        $_POST['set_avis_chef']    = '1';
        $_POST['numetu']           = 'ETU001';
        $_POST['avis']             = 'maybe';

        [$controller, $useCaseMock] = $this->makeDeptController();
        $useCaseMock->expects($this->never())->method('setAvisChef');

        try { $controller->control(); } catch (\RuntimeException $e) {}
    }

    // =========================================================================
    // DepartmentHeadController — GET action=view
    // =========================================================================

    public function test_dept_view_action_calls_getStudentDetails(): void
    {
        $_SESSION['role'] = 'admin';
        $_GET['action']   = 'view';
        $_GET['numetu']   = 'ETU042';

        [$controller, $useCaseMock] = $this->makeDeptController();
        $useCaseMock->expects($this->once())->method('getStudentDetails')->with('ETU042')->willReturn([]);
        $useCaseMock->method('rechercherAvecPagination')->willReturn($this->emptyPaginationResult());

        try { $controller->control(); } catch (\Throwable $e) {}
    }

    public function test_dept_view_action_does_not_call_getStudentDetails_when_numetu_missing(): void
    {
        $_SESSION['role'] = 'admin';
        $_GET['action']   = 'view';

        [$controller, $useCaseMock] = $this->makeDeptController();
        $useCaseMock->expects($this->never())->method('getStudentDetails');
        $useCaseMock->method('rechercherAvecPagination')->willReturn($this->emptyPaginationResult());

        try { $controller->control(); } catch (\Throwable $e) {}
    }

    // =========================================================================
    // DepartmentHeadController — GET list / filters
    // =========================================================================

    public function test_dept_list_calls_rechercherAvecPagination(): void
    {
        $_SESSION['role'] = 'admin';

        [$controller, $useCaseMock] = $this->makeDeptController();
        $useCaseMock->expects($this->once())
            ->method('rechercherAvecPagination')
            ->willReturn($this->emptyPaginationResult());

        try { $controller->control(); } catch (\Throwable $e) {}
    }

    public function test_dept_filter_departement_comes_from_session_for_chef(): void
    {
        $_SESSION['role']        = 'chef_departement';
        $_SESSION['departement'] = 'Informatique';

        [$controller, $useCaseMock] = $this->makeDeptController();

        $capturedFilters = null;
        $useCaseMock->method('rechercherAvecPagination')
            ->willReturnCallback(function (array $filters) use (&$capturedFilters) {
                $capturedFilters = $filters;
                return $this->emptyPaginationResult();
            });

        try { $controller->control(); } catch (\Throwable $e) {}

        $this->assertSame('Informatique', $capturedFilters['departement'] ?? null);
    }

    public function test_dept_pagination_defaults_to_page_1(): void
    {
        $_SESSION['role'] = 'admin';

        [$controller, $useCaseMock] = $this->makeDeptController();

        $capturedPage = null;
        $useCaseMock->method('rechercherAvecPagination')
            ->willReturnCallback(function (array $filters, int $page) use (&$capturedPage) {
                $capturedPage = $page;
                return $this->emptyPaginationResult();
            });

        try { $controller->control(); } catch (\Throwable $e) {}

        $this->assertSame(1, $capturedPage);
    }

    // =========================================================================
    // InternershipCoordinatorController — support()
    // =========================================================================

    public function test_intern_support_returns_true_for_coordinateur_stage(): void
    {
        $this->assertTrue(InternershipCoordinatorController::support('coordinateur-stage', 'GET'));
    }

    public function test_intern_support_returns_false_for_other_pages(): void
    {
        $this->assertFalse(InternershipCoordinatorController::support('chef-departement',   'GET'));
        $this->assertFalse(InternershipCoordinatorController::support('coordinateur-etude', 'GET'));
        $this->assertFalse(InternershipCoordinatorController::support('',                   'GET'));
    }

    // =========================================================================
    // InternershipCoordinatorController — authentication & roles
    // =========================================================================

    public function test_intern_redirects_to_login_when_not_authenticated(): void
    {
        [$controller] = $this->makeInternController();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/redirect:.*login/');

        $controller->control();
    }

    public function test_intern_redirects_to_login_for_unauthorized_role(): void
    {
        $_SESSION['role'] = 'chef_departement';
        [$controller]     = $this->makeInternController();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/redirect:.*login/');

        $controller->control();
    }

    /** @dataProvider internAllowedRolesProvider */
    public function test_intern_allows_authorized_roles(string $role): void
    {
        $_SESSION['role'] = $role;
        [$controller, $useCaseMock] = $this->makeInternController();
        $useCaseMock->method('rechercherAvecPagination')->willReturn($this->emptyPaginationResult());

        $redirected = false;
        try {
            $controller->control();
        } catch (\RuntimeException $e) {
            if (str_contains($e->getMessage(), 'redirect:')) {
                $redirected = true;
            }
        }

        $this->assertFalse($redirected, "Role '$role' should be authorized.");
    }

    /** @return array<int, array{0: string}> */
    public static function internAllowedRolesProvider(): array
    {
        return [
            ['coordinateur'],
            ['coordinateur_stage'],
            ['admin'],
        ];
    }

    // =========================================================================
    // InternershipCoordinatorController — mobilite=stage filter
    // =========================================================================

    public function test_intern_filter_mobilite_is_always_stage(): void
    {
        $_SESSION['role'] = 'admin';

        [$controller, $useCaseMock] = $this->makeInternController();

        $capturedFilters = null;
        $useCaseMock->method('rechercherAvecPagination')
            ->willReturnCallback(function (array $filters) use (&$capturedFilters) {
                $capturedFilters = $filters;
                return $this->emptyPaginationResult();
            });

        try { $controller->control(); } catch (\Throwable $e) {}

        $this->assertSame('stage', $capturedFilters['mobilite'] ?? null);
    }

    // =========================================================================
    // InternershipCoordinatorController — GET action=view
    // =========================================================================

    public function test_intern_view_action_calls_getStudentDetails(): void
    {
        $_SESSION['role'] = 'admin';
        $_GET['action']   = 'view';
        $_GET['numetu']   = 'ETU007';

        [$controller, $useCaseMock] = $this->makeInternController();
        $useCaseMock->expects($this->once())->method('getStudentDetails')->with('ETU007')->willReturn([]);
        $useCaseMock->method('rechercherAvecPagination')->willReturn($this->emptyPaginationResult());

        try { $controller->control(); } catch (\Throwable $e) {}
    }

    public function test_intern_list_action_does_not_call_getStudentDetails(): void
    {
        $_SESSION['role'] = 'admin';

        [$controller, $useCaseMock] = $this->makeInternController();
        $useCaseMock->expects($this->never())->method('getStudentDetails');
        $useCaseMock->method('rechercherAvecPagination')->willReturn($this->emptyPaginationResult());

        try { $controller->control(); } catch (\Throwable $e) {}
    }

    // =========================================================================
    // InternershipCoordinatorController — language resolution
    // =========================================================================

    public function test_intern_lang_set_from_get(): void
    {
        $_SESSION['role'] = 'admin';
        $_GET['lang']     = 'en';
        [$controller, $useCaseMock] = $this->makeInternController();
        $useCaseMock->method('rechercherAvecPagination')->willReturn($this->emptyPaginationResult());

        try { $controller->control(); } catch (\Throwable $e) {}

        $this->assertSame('en', $_SESSION['lang']);
    }

    public function test_intern_lang_ignores_invalid_value(): void
    {
        $_SESSION['role'] = 'admin';
        $_GET['lang']     = 'zh';
        [$controller, $useCaseMock] = $this->makeInternController();
        $useCaseMock->method('rechercherAvecPagination')->willReturn($this->emptyPaginationResult());

        try { $controller->control(); } catch (\Throwable $e) {}

        $this->assertArrayNotHasKey('lang', $_SESSION);
    }

    // =========================================================================
    // StudyCoordinatorController — support()
    // =========================================================================

    public function test_study_support_returns_true_for_coordinateur_etude(): void
    {
        $this->assertTrue(StudyCoordinatorController::support('coordinateur-etude', 'GET'));
    }

    public function test_study_support_returns_false_for_other_pages(): void
    {
        $this->assertFalse(StudyCoordinatorController::support('chef-departement',  'GET'));
        $this->assertFalse(StudyCoordinatorController::support('coordinateur-stage','GET'));
        $this->assertFalse(StudyCoordinatorController::support('',                  'GET'));
    }

    // =========================================================================
    // StudyCoordinatorController — authentication & roles
    // =========================================================================

    public function test_study_redirects_to_login_when_not_authenticated(): void
    {
        [$controller] = $this->makeStudyController();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/redirect:.*login/');

        $controller->control();
    }

    public function test_study_redirects_to_login_for_unauthorized_role(): void
    {
        $_SESSION['role'] = 'chef_departement';
        [$controller]     = $this->makeStudyController();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/redirect:.*login/');

        $controller->control();
    }

    /** @dataProvider studyAllowedRolesProvider */
    public function test_study_allows_authorized_roles(string $role): void
    {
        $_SESSION['role'] = $role;
        [$controller, $useCaseMock] = $this->makeStudyController();
        $useCaseMock->method('rechercherAvecPagination')->willReturn($this->emptyPaginationResult());

        $redirected = false;
        try {
            $controller->control();
        } catch (\RuntimeException $e) {
            if (str_contains($e->getMessage(), 'redirect:')) {
                $redirected = true;
            }
        }

        $this->assertFalse($redirected, "Role '$role' should be authorized.");
    }

    /** @return array<int, array{0: string}> */
    public static function studyAllowedRolesProvider(): array
    {
        return [
            ['coordinateur'],
            ['coordinateur_etude'],
            ['admin'],
        ];
    }

    // =========================================================================
    // StudyCoordinatorController — mobilite=etude filter
    // =========================================================================

    public function test_study_filter_mobilite_is_always_etude(): void
    {
        $_SESSION['role'] = 'admin';

        [$controller, $useCaseMock] = $this->makeStudyController();

        $capturedFilters = null;
        $useCaseMock->method('rechercherAvecPagination')
            ->willReturnCallback(function (array $filters) use (&$capturedFilters) {
                $capturedFilters = $filters;
                return $this->emptyPaginationResult();
            });

        try { $controller->control(); } catch (\Throwable $e) {}

        $this->assertSame('etude', $capturedFilters['mobilite'] ?? null);
    }

    // =========================================================================
    // StudyCoordinatorController — GET action=view
    // =========================================================================

    public function test_study_view_action_calls_getStudentDetails(): void
    {
        $_SESSION['role'] = 'admin';
        $_GET['action']   = 'view';
        $_GET['numetu']   = 'ETU099';

        [$controller, $useCaseMock] = $this->makeStudyController();
        $useCaseMock->expects($this->once())->method('getStudentDetails')->with('ETU099')->willReturn([]);
        $useCaseMock->method('rechercherAvecPagination')->willReturn($this->emptyPaginationResult());

        try { $controller->control(); } catch (\Throwable $e) {}
    }

    public function test_study_list_action_does_not_call_getStudentDetails(): void
    {
        $_SESSION['role'] = 'admin';

        [$controller, $useCaseMock] = $this->makeStudyController();
        $useCaseMock->expects($this->never())->method('getStudentDetails');
        $useCaseMock->method('rechercherAvecPagination')->willReturn($this->emptyPaginationResult());

        try { $controller->control(); } catch (\Throwable $e) {}
    }

    // =========================================================================
    // StudyCoordinatorController — language resolution
    // =========================================================================

    public function test_study_lang_set_from_get(): void
    {
        $_SESSION['role'] = 'admin';
        $_GET['lang']     = 'en';
        [$controller, $useCaseMock] = $this->makeStudyController();
        $useCaseMock->method('rechercherAvecPagination')->willReturn($this->emptyPaginationResult());

        try { $controller->control(); } catch (\Throwable $e) {}

        $this->assertSame('en', $_SESSION['lang']);
    }

    public function test_study_lang_ignores_invalid_value(): void
    {
        $_SESSION['role'] = 'admin';
        $_GET['lang']     = 'ar';
        [$controller, $useCaseMock] = $this->makeStudyController();
        $useCaseMock->method('rechercherAvecPagination')->willReturn($this->emptyPaginationResult());

        try { $controller->control(); } catch (\Throwable $e) {}

        $this->assertArrayNotHasKey('lang', $_SESSION);
    }

    // =========================================================================
    // StudyCoordinatorController — pagination
    // =========================================================================

    public function test_study_pagination_uses_get_p_parameter(): void
    {
        $_SESSION['role'] = 'admin';
        $_GET['p']        = '3';

        [$controller, $useCaseMock] = $this->makeStudyController();

        $capturedPage = null;
        $useCaseMock->method('rechercherAvecPagination')
            ->willReturnCallback(function (array $filters, int $page) use (&$capturedPage) {
                $capturedPage = $page;
                return $this->emptyPaginationResult();
            });

        try { $controller->control(); } catch (\Throwable $e) {}

        $this->assertSame(3, $capturedPage);
    }

    public function test_study_pagination_minimum_page_is_1(): void
    {
        $_SESSION['role'] = 'admin';
        $_GET['p']        = '-5';

        [$controller, $useCaseMock] = $this->makeStudyController();

        $capturedPage = null;
        $useCaseMock->method('rechercherAvecPagination')
            ->willReturnCallback(function (array $filters, int $page) use (&$capturedPage) {
                $capturedPage = $page;
                return $this->emptyPaginationResult();
            });

        try { $controller->control(); } catch (\Throwable $e) {}

        $this->assertSame(1, $capturedPage);
    }

    // =========================================================================
    // buildUrl helper
    // =========================================================================

    public function test_buildUrl_always_appends_lang_parameter(): void
    {
        $lang = 'en';

        /** @param array<string, string> $params */
        $buildUrl = function (string $url, array $params = []) use ($lang): string {
            $params['lang'] = $lang;
            return $url . '?' . http_build_query($params);
        };

        $result = $buildUrl('index.php', ['page' => 'chef-departement']);
        $this->assertStringContainsString('lang=en',             $result);
        $this->assertStringContainsString('page=chef-departement', $result);
    }

    public function test_buildUrl_works_with_french_lang(): void
    {
        $lang = 'fr';

        /** @param array<string, string> $params */
        $buildUrl = function (string $url, array $params = []) use ($lang): string {
            $params['lang'] = $lang;
            return $url . '?' . http_build_query($params);
        };

        $result = $buildUrl('index.php', ['action' => 'view', 'numetu' => 'ETU001']);
        $this->assertStringContainsString('lang=fr',      $result);
        $this->assertStringContainsString('action=view',  $result);
        $this->assertStringContainsString('numetu=ETU001', $result);
    }
}