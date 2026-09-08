<?php

namespace Tests\Controllers\FolderController;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Controllers\FolderController\FoldersControllerAdmin;
use Controllers\FolderController\FoldersControllerStudent;
use Model\UseCase\ManageFolderUseCase;

/**
 * Unit tests for FoldersControllerAdmin and FoldersControllerStudent.
 *
 * These tests verify routing (support()), use-case delegation, session message
 * assignment, and redirect behaviour for both the admin and student folder
 * controllers. All HTTP superglobals ($_GET, $_POST, $_FILES, $_SESSION,
 * $_SERVER) are reset before every test to ensure isolation.
 *
 * Run with:
 *   ./vendor/bin/phpunit Tests/Controllers/FolderController/FoldersControllersTest.php
 */
class FoldersControllersTest extends TestCase
{
    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Resets all HTTP superglobals before each test to prevent state leakage.
     */
    protected function setUp(): void
    {
        $_GET     = [];
        $_POST    = [];
        $_FILES   = [];
        $_SESSION = ['role' => 'admin']; // default role for admin tests
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    /**
     * Returns an empty, valid search result structure.
     *
     * Used as the default return value for {@see ManageFolderUseCase::searchWithoutPagination()}
     * when the test does not care about the actual list contents.
     *
     * @return array{data: array<int, mixed>, total: int, totalPages: int}
     */
    private function emptySearchResult(): array
    {
        return ['data' => [], 'total' => 0, 'totalPages' => 1];
    }

    /**
     * Builds an anonymous subclass of {@see FoldersControllerAdmin} backed by a
     * mocked {@see ManageFolderUseCase}.
     *
     * The subclass overrides infrastructure methods so tests stay in-process:
     * - {@see startSession()} is a no-op.
     * - {@see redirect()} throws a RuntimeException whose message starts with
     *   "redirect:" so assertions can inspect the target URL.
     * - {@see renderView()} is a no-op.
     * - {@see jsonResponse()} throws a RuntimeException whose message starts
     *   with "json:" so assertions can inspect the serialised payload.
     * - {@see log()} is a no-op.
     *
     * @return array{0: FoldersControllerAdmin, 1: MockObject&ManageFolderUseCase}
     */
    private function makeAdminController(): array
    {
        $useCaseMock = $this->createMock(ManageFolderUseCase::class);

        $controller = new class($useCaseMock) extends FoldersControllerAdmin {
            public function __construct(ManageFolderUseCase $useCase)
            {
                // Inject the mock directly via reflection to bypass the real constructor.
                $ref  = new \ReflectionClass(FoldersControllerAdmin::class);
                $prop = $ref->getProperty('folderUseCase');
                $prop->setAccessible(true);
                $prop->setValue($this, $useCase);
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

            /**
             * Converts a JSON response into a catchable exception so tests can
             * assert on the serialised payload.
             *
             * @param array<string, mixed> $data
             */
            protected function jsonResponse(array $data): never
            {
                throw new \RuntimeException('json:' . json_encode($data));
            }

            /** No-op: suppresses log output during tests. */
            protected function log(string $message): void {}
        };

        return [$controller, $useCaseMock];
    }

    /**
     * Builds an anonymous subclass of {@see FoldersControllerStudent} backed by
     * a mocked {@see ManageFolderUseCase}.
     *
     * The subclass overrides infrastructure methods so tests stay in-process:
     * - {@see startSession()} is a no-op.
     * - {@see redirect()} throws a RuntimeException prefixed with "redirect:".
     * - {@see renderView()} is a no-op.
     *
     * @return array{0: FoldersControllerStudent, 1: MockObject&ManageFolderUseCase}
     */
    private function makeStudentController(): array
    {
        $useCaseMock = $this->createMock(ManageFolderUseCase::class);

        $controller = new class($useCaseMock) extends FoldersControllerStudent {
            public function __construct(ManageFolderUseCase $useCase)
            {
                // Inject the mock directly via reflection to bypass the real constructor.
                $ref  = new \ReflectionClass(FoldersControllerStudent::class);
                $prop = $ref->getProperty('folderUseCase');
                $prop->setAccessible(true);
                $prop->setValue($this, $useCase);
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

        return [$controller, $useCaseMock];
    }

    // =========================================================================
    // FoldersControllerAdmin — support()
    // =========================================================================

    /**
     * @test
     * The admin controller must handle the "folders" page on GET.
     */
    public function test_admin_support_returns_true_for_folders(): void
    {
        $this->assertTrue(FoldersControllerAdmin::support('folders', 'GET'));
    }

    /**
     * @test
     * The admin controller must handle the "folders-admin" page on GET.
     */
    public function test_admin_support_returns_true_for_folders_admin(): void
    {
        $this->assertTrue(FoldersControllerAdmin::support('folders-admin', 'GET'));
    }

    /**
     * @test
     * The admin controller must handle the "save_student" action on POST.
     */
    public function test_admin_support_returns_true_for_save_student(): void
    {
        $this->assertTrue(FoldersControllerAdmin::support('save_student', 'POST'));
    }

    /**
     * @test
     * The admin controller must handle the "toggle_complete" action on GET.
     */
    public function test_admin_support_returns_true_for_toggle_complete(): void
    {
        $this->assertTrue(FoldersControllerAdmin::support('toggle_complete', 'GET'));
    }

    /**
     * @test
     * The admin controller must handle the "update_student" action on POST.
     */
    public function test_admin_support_returns_true_for_update_student(): void
    {
        $this->assertTrue(FoldersControllerAdmin::support('update_student', 'POST'));
    }

    /**
     * @test
     * The admin controller must handle the "import_folders" action on POST.
     */
    public function test_admin_support_returns_true_for_import_folders(): void
    {
        $this->assertTrue(FoldersControllerAdmin::support('import_folders', 'POST'));
    }

    /**
     * @test
     * The admin controller must handle the "update_document_status" action on POST.
     */
    public function test_admin_support_returns_true_for_update_document_status(): void
    {
        $this->assertTrue(FoldersControllerAdmin::support('update_document_status', 'POST'));
    }

    /**
     * @test
     * The admin controller must handle the "update_global_status" action on POST.
     */
    public function test_admin_support_returns_true_for_update_global_status(): void
    {
        $this->assertTrue(FoldersControllerAdmin::support('update_global_status', 'POST'));
    }

    /**
     * @test
     * The admin controller must handle the "valider_documents" action on POST.
     */
    public function test_admin_support_returns_true_for_valider_documents(): void
    {
        $this->assertTrue(FoldersControllerAdmin::support('valider_documents', 'POST'));
    }

    /**
     * @test
     * Pages that are not owned by the admin controller must return false so
     * the router can delegate to another handler.
     */
    public function test_admin_support_returns_false_for_unknown_page(): void
    {
        $this->assertFalse(FoldersControllerAdmin::support('home', 'GET'));
        $this->assertFalse(FoldersControllerAdmin::support('', 'GET'));
        $this->assertFalse(FoldersControllerAdmin::support('login', 'POST'));
    }

    // =========================================================================
    // FoldersControllerAdmin — GET list (default)
    // =========================================================================

    /**
     * @test
     * When the admin lands on the folder list, the controller must call
     * searchWithoutPagination() exactly once to populate the view.
     */
    public function test_admin_list_calls_searchWithoutPagination(): void
    {
        $_GET['page'] = 'folders-admin';
        [$controller, $useCaseMock] = $this->makeAdminController();

        $useCaseMock->expects($this->once())
            ->method('searchWithoutPagination')
            ->willReturn($this->emptySearchResult());

        try { $controller->control(); } catch (\Throwable $e) {}
    }

    /**
     * @test
     * Filter values supplied via $_GET (type, zone, search) must be forwarded
     * verbatim to searchWithoutPagination() so the use case can apply them.
     */
    public function test_admin_list_passes_filters_from_get(): void
    {
        $_GET['page']   = 'folders-admin';
        $_GET['type']   = 'entrant';
        $_GET['zone']   = 'europe';
        $_GET['search'] = 'Dupont';

        [$controller, $useCaseMock] = $this->makeAdminController();

        $capturedFilters = null;
        $useCaseMock
            ->method('searchWithoutPagination')
            ->willReturnCallback(function (array $filters) use (&$capturedFilters) {
                $capturedFilters = $filters;
                return $this->emptySearchResult();
            });

        try { $controller->control(); } catch (\Throwable $e) {}

        $this->assertSame('entrant', $capturedFilters['type']   ?? null);
        $this->assertSame('europe',  $capturedFilters['zone']   ?? null);
        $this->assertSame('Dupont',  $capturedFilters['search'] ?? null);
    }

    // =========================================================================
    // FoldersControllerAdmin — GET action=view
    // =========================================================================

    /**
     * @test
     * When action=view is requested with a valid numetu, the controller must
     * fetch the student details exactly once using the provided identifier.
     */
    public function test_admin_view_action_calls_getStudentDetails(): void
    {
        $_GET['page']   = 'folders-admin';
        $_GET['action'] = 'view';
        $_GET['numetu'] = 'ETU001';

        [$controller, $useCaseMock] = $this->makeAdminController();

        $useCaseMock->expects($this->once())
            ->method('getStudentDetails')
            ->with('ETU001')
            ->willReturn(['NumEtu' => 'ETU001']);
        $useCaseMock->method('searchWithoutPagination')->willReturn($this->emptySearchResult());

        try { $controller->control(); } catch (\Throwable $e) {}
    }

    /**
     * @test
     * When action=view is requested without a numetu, the controller must not
     * attempt to fetch student details (no query for an empty identifier).
     */
    public function test_admin_view_action_does_not_call_getStudentDetails_when_numetu_missing(): void
    {
        $_GET['page']   = 'folders-admin';
        $_GET['action'] = 'view';

        [$controller, $useCaseMock] = $this->makeAdminController();

        $useCaseMock->expects($this->never())->method('getStudentDetails');
        $useCaseMock->method('searchWithoutPagination')->willReturn($this->emptySearchResult());

        try { $controller->control(); } catch (\Throwable $e) {}
    }

    // =========================================================================
    // FoldersControllerAdmin — toggle_complete
    // =========================================================================

    /**
     * @test
     * A valid toggle_complete request must invoke toggleCompleteStatus() with
     * the supplied student number.
     */
    public function test_admin_toggle_complete_calls_toggleCompleteStatus(): void
    {
        $_GET['page']   = 'toggle_complete';
        $_GET['numetu'] = 'ETU042';

        [$controller, $useCaseMock] = $this->makeAdminController();

        $useCaseMock->expects($this->once())
            ->method('toggleCompleteStatus')
            ->with('ETU042')
            ->willReturn(true);

        try { $controller->control(); } catch (\RuntimeException $e) {}
    }

    /**
     * @test
     * When numetu is absent from the request, the toggle action must be skipped
     * entirely to avoid toggling an unintended record.
     */
    public function test_admin_toggle_complete_does_nothing_when_numetu_missing(): void
    {
        $_GET['page'] = 'toggle_complete';

        [$controller, $useCaseMock] = $this->makeAdminController();

        $useCaseMock->expects($this->never())->method('toggleCompleteStatus');
        $useCaseMock->method('searchWithoutPagination')->willReturn($this->emptySearchResult());

        try { $controller->control(); } catch (\Throwable $e) {}
    }

    /**
     * @test
     * On success with lang=fr, the session message must be the French
     * confirmation string.
     */
    public function test_admin_toggle_complete_sets_success_message_in_french(): void
    {
        $_GET['page']   = 'toggle_complete';
        $_GET['numetu'] = 'ETU001';
        $_GET['lang']   = 'fr';

        [$controller, $useCaseMock] = $this->makeAdminController();
        $useCaseMock->method('toggleCompleteStatus')->willReturn(true);

        try { $controller->control(); } catch (\RuntimeException $e) {}

        $this->assertSame('Statut du dossier mis à jour.', $_SESSION['message'] ?? '');
    }

    /**
     * @test
     * When the use case reports failure, the session message must reflect the
     * error so the view can display appropriate feedback.
     */
    public function test_admin_toggle_complete_sets_error_message_when_failed(): void
    {
        $_GET['page']   = 'toggle_complete';
        $_GET['numetu'] = 'ETU001';
        $_GET['lang']   = 'fr';

        [$controller, $useCaseMock] = $this->makeAdminController();
        $useCaseMock->method('toggleCompleteStatus')->willReturn(false);

        try { $controller->control(); } catch (\RuntimeException $e) {}

        $this->assertSame('Erreur lors de la mise à jour.', $_SESSION['message'] ?? '');
    }

    /**
     * @test
     * On success with lang=en, the session message must be the English
     * confirmation string.
     */
    public function test_admin_toggle_complete_sets_success_message_in_english(): void
    {
        $_GET['page']   = 'toggle_complete';
        $_GET['numetu'] = 'ETU001';
        $_GET['lang']   = 'en';

        [$controller, $useCaseMock] = $this->makeAdminController();
        $useCaseMock->method('toggleCompleteStatus')->willReturn(true);

        try { $controller->control(); } catch (\RuntimeException $e) {}

        $this->assertSame('Folder status updated.', $_SESSION['message'] ?? '');
    }

    /**
     * @test
     * After a toggle, the controller must redirect back to the folders-admin
     * page and include the student number in the URL for context.
     */
    public function test_admin_toggle_complete_redirects_after_toggle(): void
    {
        $_GET['page']   = 'toggle_complete';
        $_GET['numetu'] = 'ETU001';

        [$controller, $useCaseMock] = $this->makeAdminController();
        $useCaseMock->method('toggleCompleteStatus')->willReturn(true);

        $redirectUrl = null;
        try {
            $controller->control();
        } catch (\RuntimeException $e) {
            $redirectUrl = $e->getMessage();
        }

        $this->assertNotNull($redirectUrl);
        $this->assertStringContainsString('folders-admin', $redirectUrl);
        $this->assertStringContainsString('ETU001', $redirectUrl);
    }

    // =========================================================================
    // FoldersControllerAdmin — POST update_global_status
    // =========================================================================

    /**
     * @test
     * A valid POST to update_global_status must delegate to setFolderStatus()
     * with the correct student number and status value.
     */
    public function test_admin_update_global_status_calls_setFolderStatus(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_GET['page']              = 'update_global_status';
        $_POST['numetu']           = 'ETU010';
        $_POST['status']           = 'instruction';

        [$controller, $useCaseMock] = $this->makeAdminController();

        $useCaseMock->expects($this->once())
            ->method('setFolderStatus')
            ->with('ETU010', 'instruction')
            ->willReturn(true);
        $useCaseMock->method('getStudentDetails')->willReturn(['status' => 'depot']);

        try { $controller->control(); } catch (\RuntimeException $e) {}
    }

    /**
     * @test
     * On success, the controller must emit a JSON response that contains a
     * truthy "success" key so the front-end can update the UI without a
     * full page reload.
     */
    public function test_admin_update_global_status_returns_json_success(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_GET['page']              = 'update_global_status';
        $_POST['numetu']           = 'ETU010';
        $_POST['status']           = 'instruction';

        [$controller, $useCaseMock] = $this->makeAdminController();
        $useCaseMock->method('getStudentDetails')->willReturn(['status' => 'depot']);
        $useCaseMock->method('setFolderStatus')->willReturn(true);

        $jsonMsg = null;
        try {
            $controller->control();
        } catch (\RuntimeException $e) {
            $jsonMsg = $e->getMessage();
        }

        $this->assertNotNull($jsonMsg);
        $this->assertStringContainsString('json:', $jsonMsg);
        $this->assertStringContainsString('"success"', $jsonMsg);
    }

    /**
     * @test
     * When numetu is empty, the controller must reject the request immediately
     * with a JSON error and must not call setFolderStatus().
     */
    public function test_admin_update_global_status_returns_json_error_when_numetu_empty(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_GET['page']              = 'update_global_status';
        $_POST['numetu']           = '';

        [$controller, $useCaseMock] = $this->makeAdminController();
        $useCaseMock->expects($this->never())->method('setFolderStatus');

        $jsonMsg = null;
        try {
            $controller->control();
        } catch (\RuntimeException $e) {
            $jsonMsg = $e->getMessage();
        }

        $this->assertStringContainsString('false', $jsonMsg ?? '');
    }

    // =========================================================================
    // FoldersControllerAdmin — POST save_student
    // =========================================================================

    /**
     * @test
     * When valid data is submitted and no existing record is found for the
     * given numetu, the controller must call creerDossier() exactly once.
     */
    public function test_admin_save_student_calls_creerDossier_with_valid_data(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_GET['page']              = 'save_student';
        $_POST['numetu']           = 'ETU099';
        $_POST['nom']              = 'Martin';
        $_POST['prenom']           = 'Alice';

        [$controller, $useCaseMock] = $this->makeAdminController();
        $useCaseMock->method('getByNumetu')->willReturn(null);
        $useCaseMock->expects($this->once())->method('creerDossier')->willReturn(true);

        try { $controller->control(); } catch (\RuntimeException $e) {}
    }

    /**
     * @test
     * When a folder already exists for the given numetu, creerDossier() must
     * not be called and the user must be redirected to the admin list.
     */
    public function test_admin_save_student_redirects_when_numetu_already_exists(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_GET['page']              = 'save_student';
        $_POST['numetu']           = 'ETU099';
        $_POST['nom']              = 'Martin';
        $_POST['prenom']           = 'Alice';

        [$controller, $useCaseMock] = $this->makeAdminController();
        $useCaseMock->method('getByNumetu')->willReturn(['NumEtu' => 'ETU099']);
        $useCaseMock->expects($this->never())->method('creerDossier');

        $redirectUrl = null;
        try {
            $controller->control();
        } catch (\RuntimeException $e) {
            $redirectUrl = $e->getMessage();
        }

        $this->assertNotNull($redirectUrl);
        $this->assertStringContainsString('folders-admin', $redirectUrl);
    }

    /**
     * @test
     * When numetu is absent from the POST body, the form is incomplete and
     * the controller must redirect back to the creation form rather than
     * attempting to create a folder.
     */
    public function test_admin_save_student_redirects_when_numetu_is_missing(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_GET['page']              = 'save_student';
        $_POST['numetu']           = '';
        $_POST['nom']              = 'Martin';

        [$controller, $useCaseMock] = $this->makeAdminController();
        $useCaseMock->expects($this->never())->method('creerDossier');

        $redirectUrl = null;
        try {
            $controller->control();
        } catch (\RuntimeException $e) {
            $redirectUrl = $e->getMessage();
        }

        $this->assertNotNull($redirectUrl);
        $this->assertStringContainsString('create', $redirectUrl);
    }

    /**
     * @test
     * On successful creation with lang=fr, the French confirmation message
     * must be stored in the session.
     */
    public function test_admin_save_student_sets_success_message_in_french(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_GET['page']              = 'save_student';
        $_GET['lang']              = 'fr';
        $_POST['numetu']           = 'ETU099';
        $_POST['nom']              = 'Martin';
        $_POST['prenom']           = 'Alice';

        [$controller, $useCaseMock] = $this->makeAdminController();
        $useCaseMock->method('getByNumetu')->willReturn(null);
        $useCaseMock->method('creerDossier')->willReturn(true);

        try { $controller->control(); } catch (\RuntimeException $e) {}

        $this->assertSame('Folder créé avec succès', $_SESSION['message'] ?? '');
    }

    /**
     * @test
     * On successful creation with lang=en, the English confirmation message
     * must be stored in the session.
     */
    public function test_admin_save_student_sets_success_message_in_english(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_GET['page']              = 'save_student';
        $_GET['lang']              = 'en';
        $_POST['numetu']           = 'ETU099';
        $_POST['nom']              = 'Martin';
        $_POST['prenom']           = 'Alice';

        [$controller, $useCaseMock] = $this->makeAdminController();
        $useCaseMock->method('getByNumetu')->willReturn(null);
        $useCaseMock->method('creerDossier')->willReturn(true);

        try { $controller->control(); } catch (\RuntimeException $e) {}

        $this->assertSame('Folder created successfully', $_SESSION['message'] ?? '');
    }

    // =========================================================================
    // FoldersControllerAdmin — POST update_student
    // =========================================================================

    /**
     * @test
     * A valid POST to update_student must invoke updateDossier() exactly once.
     */
    public function test_admin_update_student_calls_updateDossier(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_GET['page']              = 'update_student';
        $_POST['numetu']           = 'ETU010';
        $_POST['nom']              = 'Durand';

        [$controller, $useCaseMock] = $this->makeAdminController();
        $useCaseMock->method('getStudentDetails')->willReturn(['Nom' => 'OldName']);
        $useCaseMock->expects($this->once())->method('updateDossier')->willReturn(true);

        try { $controller->control(); } catch (\RuntimeException $e) {}
    }

    /**
     * @test
     * On successful update with lang=fr, the French confirmation message must
     * be stored in the session.
     */
    public function test_admin_update_student_sets_success_message_in_french(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_GET['page']              = 'update_student';
        $_GET['lang']              = 'fr';
        $_POST['numetu']           = 'ETU010';
        $_POST['nom']              = 'Durand';

        [$controller, $useCaseMock] = $this->makeAdminController();
        $useCaseMock->method('getStudentDetails')->willReturn(['Nom' => 'OldName']);
        $useCaseMock->method('updateDossier')->willReturn(true);

        try { $controller->control(); } catch (\RuntimeException $e) {}

        $this->assertSame('Folder mis à jour', $_SESSION['message'] ?? '');
    }

    /**
     * @test
     * When updateDossier() returns false, an error message must be placed in
     * the session so the view can surface it to the user.
     */
    public function test_admin_update_student_sets_error_message_when_failed(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_GET['page']              = 'update_student';
        $_GET['lang']              = 'fr';
        $_POST['numetu']           = 'ETU010';
        $_POST['nom']              = 'Durand';

        [$controller, $useCaseMock] = $this->makeAdminController();
        $useCaseMock->method('getStudentDetails')->willReturn(['Nom' => 'OldName']);
        $useCaseMock->method('updateDossier')->willReturn(false);

        try { $controller->control(); } catch (\RuntimeException $e) {}

        $this->assertSame('Erreur lors de la mise à jour', $_SESSION['message'] ?? '');
    }

    // =========================================================================
    // FoldersControllerAdmin — POST import_folders
    // =========================================================================

    /**
     * @test
     * When no file is present in $_FILES, the import must be aborted and an
     * error message placed in the session; importFoldersFromCSV() must not
     * be called.
     */
    public function test_admin_import_folders_sets_error_when_no_file_uploaded(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_GET['page']              = 'import_folders';
        $_GET['lang']              = 'fr';

        [$controller, $useCaseMock] = $this->makeAdminController();
        $useCaseMock->expects($this->never())->method('importFoldersFromCSV');

        try { $controller->control(); } catch (\RuntimeException $e) {}

        $this->assertStringContainsString('Erreur', $_SESSION['message'] ?? '');
    }

    /**
     * @test
     * When the uploaded file has an extension other than .csv/.xlsx, the
     * controller must reject it with a "Format non supporté" error and must
     * not forward the file to the use case.
     */
    public function test_admin_import_folders_sets_error_for_unsupported_extension(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_GET['page']              = 'import_folders';
        $_GET['lang']              = 'fr';

        // Simulate a .txt file upload — not a supported import format.
        $_FILES['excel_file'] = [
            'error'    => UPLOAD_ERR_OK,
            'tmp_name' => '/tmp/test.txt',
            'name'     => 'data.txt',
        ];

        [$controller, $useCaseMock] = $this->makeAdminController();
        $useCaseMock->expects($this->never())->method('importFoldersFromCSV');

        try { $controller->control(); } catch (\RuntimeException $e) {}

        $this->assertStringContainsString('Format non supporté', $_SESSION['message'] ?? '');
    }

    // =========================================================================
    // FoldersControllerStudent — support()
    // =========================================================================

    /**
     * @test
     * The student controller must handle the "folders-student" page on GET.
     */
    public function test_student_support_returns_true_for_folders_student(): void
    {
        $this->assertTrue(FoldersControllerStudent::support('folders-student', 'GET'));
    }

    /**
     * @test
     * The student controller must handle the "update_my_folder" action on POST.
     */
    public function test_student_support_returns_true_for_update_my_folder(): void
    {
        $this->assertTrue(FoldersControllerStudent::support('update_my_folder', 'POST'));
    }

    /**
     * @test
     * The student controller must handle the "create_folder" action on POST.
     */
    public function test_student_support_returns_true_for_create_folder(): void
    {
        $this->assertTrue(FoldersControllerStudent::support('create_folder', 'POST'));
    }

    /**
     * @test
     * Admin-only pages and unknown pages must return false so the router does
     * not route them through the student controller.
     */
    public function test_student_support_returns_false_for_other_pages(): void
    {
        $this->assertFalse(FoldersControllerStudent::support('folders-admin', 'GET'));
        $this->assertFalse(FoldersControllerStudent::support('', 'GET'));
    }

    // =========================================================================
    // FoldersControllerStudent — authentication
    // =========================================================================

    /**
     * @test
     * When no numetu is present in the session (unauthenticated student), the
     * controller must redirect to the login page immediately.
     */
    public function test_student_redirects_to_login_when_numetu_missing(): void
    {
        $_SESSION = []; // no numetu, no student role

        [$controller] = $this->makeStudentController();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/redirect:.*login/');

        $controller->control();
    }

    // =========================================================================
    // FoldersControllerStudent — GET displayFolderPage
    // =========================================================================

    /**
     * @test
     * When an authenticated student lands on their folder page, the controller
     * must fetch their details using the numetu stored in the session.
     */
    public function test_student_get_calls_getStudentDetails_with_numetu(): void
    {
        $_SESSION['numetu'] = '12345';

        [$controller, $useCaseMock] = $this->makeStudentController();

        $useCaseMock->expects($this->once())
            ->method('getStudentDetails')
            ->with('12345')
            ->willReturn([]);

        try { $controller->control(); } catch (\Throwable $e) {}
    }

    // =========================================================================
    // FoldersControllerStudent — POST create_folder
    // =========================================================================

    /**
     * @test
     * When all required fields are present and no existing folder is found,
     * creerDossier() must be called exactly once to create the record.
     */
    public function test_student_create_folder_calls_creerDossier_with_valid_data(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SESSION['numetu']        = '12345';
        $_GET['page']              = 'create_folder';
        $_POST['nom']              = 'Dupont';
        $_POST['prenom']           = 'Marie';
        $_POST['email_perso']      = 'marie@example.com';
        $_POST['telephone']        = '0600000000';
        $_POST['type']             = 'entrant';
        $_POST['zone']             = 'europe';

        [$controller, $useCaseMock] = $this->makeStudentController();
        $useCaseMock->method('getStudentDetails')->willReturn(null);
        $useCaseMock->expects($this->once())->method('creerDossier')->willReturn(true);

        try { $controller->control(); } catch (\RuntimeException $e) {}
    }

    /**
     * @test
     * When the student already has a folder, creerDossier() must not be called
     * and the user must be redirected to the folders-student page.
     */
    public function test_student_create_folder_redirects_when_folder_already_exists(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SESSION['numetu']        = '12345';
        $_GET['page']              = 'create_folder';

        [$controller, $useCaseMock] = $this->makeStudentController();
        $useCaseMock->method('getStudentDetails')->willReturn(['NumEtu' => '12345']);
        $useCaseMock->expects($this->never())->method('creerDossier');

        $redirectUrl = null;
        try {
            $controller->control();
        } catch (\RuntimeException $e) {
            $redirectUrl = $e->getMessage();
        }

        $this->assertNotNull($redirectUrl);
        $this->assertStringContainsString('folders-student', $redirectUrl);
    }

    /**
     * @test
     * When the folder already exists and lang=fr, the French duplicate-folder
     * message must be stored in the session.
     */
    public function test_student_create_folder_sets_already_exists_message_in_french(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SESSION['numetu']        = '12345';
        $_GET['page']              = 'create_folder';
        $_GET['lang']              = 'fr';

        [$controller, $useCaseMock] = $this->makeStudentController();
        $useCaseMock->method('getStudentDetails')->willReturn(['NumEtu' => '12345']);

        try { $controller->control(); } catch (\RuntimeException $e) {}

        $this->assertSame('Vous avez déjà déposé un dossier.', $_SESSION['message'] ?? '');
    }

    /**
     * @test
     * When the folder already exists and lang=en, the English duplicate-folder
     * message must be stored in the session.
     */
    public function test_student_create_folder_sets_already_exists_message_in_english(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SESSION['numetu']        = '12345';
        $_GET['page']              = 'create_folder';
        $_GET['lang']              = 'en';

        [$controller, $useCaseMock] = $this->makeStudentController();
        $useCaseMock->method('getStudentDetails')->willReturn(['NumEtu' => '12345']);

        try { $controller->control(); } catch (\RuntimeException $e) {}

        $this->assertSame('You have already submitted an application.', $_SESSION['message'] ?? '');
    }

    /**
     * @test
     * When mandatory POST fields are missing, the controller must not call
     * creerDossier() to prevent creating an incomplete record.
     */
    public function test_student_create_folder_does_not_create_when_fields_missing(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SESSION['numetu']        = '12345';
        $_GET['page']              = 'create_folder';

        [$controller, $useCaseMock] = $this->makeStudentController();
        $useCaseMock->method('getStudentDetails')->willReturn(null);
        $useCaseMock->expects($this->never())->method('creerDossier');

        try { $controller->control(); } catch (\RuntimeException $e) {}
    }

    /**
     * @test
     * When the supplied email address fails validation, the controller must not
     * call creerDossier() to prevent storing an invalid contact address.
     */
    public function test_student_create_folder_does_not_create_when_email_invalid(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SESSION['numetu']        = '12345';
        $_GET['page']              = 'create_folder';
        $_POST['nom']              = 'Dupont';
        $_POST['prenom']           = 'Marie';
        $_POST['email_perso']      = 'not-an-email'; // intentionally malformed
        $_POST['telephone']        = '0600000000';
        $_POST['type']             = 'entrant';
        $_POST['zone']             = 'europe';

        [$controller, $useCaseMock] = $this->makeStudentController();
        $useCaseMock->method('getStudentDetails')->willReturn(null);
        $useCaseMock->expects($this->never())->method('creerDossier');

        try { $controller->control(); } catch (\RuntimeException $e) {}
    }

    /**
     * @test
     * On successful creation with lang=fr, the French confirmation message
     * must be stored in the session.
     */
    public function test_student_create_folder_sets_success_message_in_french(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SESSION['numetu']        = '12345';
        $_GET['page']              = 'create_folder';
        $_GET['lang']              = 'fr';
        $_POST['nom']              = 'Dupont';
        $_POST['prenom']           = 'Marie';
        $_POST['email_perso']      = 'marie@example.com';
        $_POST['telephone']        = '0600000000';
        $_POST['type']             = 'entrant';
        $_POST['zone']             = 'europe';

        [$controller, $useCaseMock] = $this->makeStudentController();
        $useCaseMock->method('getStudentDetails')->willReturn(null);
        $useCaseMock->method('creerDossier')->willReturn(true);

        try { $controller->control(); } catch (\RuntimeException $e) {}

        $this->assertSame('Votre demande a été déposée avec succès.', $_SESSION['message'] ?? '');
    }

    /**
     * @test
     * On successful creation with lang=en, the English confirmation message
     * must be stored in the session.
     */
    public function test_student_create_folder_sets_success_message_in_english(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SESSION['numetu']        = '12345';
        $_GET['page']              = 'create_folder';
        $_GET['lang']              = 'en';
        $_POST['nom']              = 'Dupont';
        $_POST['prenom']           = 'Marie';
        $_POST['email_perso']      = 'marie@example.com';
        $_POST['telephone']        = '0600000000';
        $_POST['type']             = 'entrant';
        $_POST['zone']             = 'europe';

        [$controller, $useCaseMock] = $this->makeStudentController();
        $useCaseMock->method('getStudentDetails')->willReturn(null);
        $useCaseMock->method('creerDossier')->willReturn(true);

        try { $controller->control(); } catch (\RuntimeException $e) {}

        $this->assertSame('Application submitted successfully.', $_SESSION['message'] ?? '');
    }

    /**
     * @test
     * When creerDossier() returns false, a French error message must be stored
     * in the session to inform the student that the submission failed.
     */
    public function test_student_create_folder_sets_error_message_on_failure(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SESSION['numetu']        = '12345';
        $_GET['page']              = 'create_folder';
        $_GET['lang']              = 'fr';
        $_POST['nom']              = 'Dupont';
        $_POST['prenom']           = 'Marie';
        $_POST['email_perso']      = 'marie@example.com';
        $_POST['telephone']        = '0600000000';
        $_POST['type']             = 'entrant';
        $_POST['zone']             = 'europe';

        [$controller, $useCaseMock] = $this->makeStudentController();
        $useCaseMock->method('getStudentDetails')->willReturn(null);
        $useCaseMock->method('creerDossier')->willReturn(false);

        try { $controller->control(); } catch (\RuntimeException $e) {}

        $this->assertSame('Erreur lors du dépôt de la demande.', $_SESSION['message'] ?? '');
    }

    // =========================================================================
    // FoldersControllerStudent — POST update_my_folder
    // =========================================================================

    /**
     * @test
     * A valid update_my_folder request must invoke updateDossier() exactly once.
     */
    public function test_student_update_folder_calls_updateDossier(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SESSION['numetu']        = '12345';
        $_GET['page']              = 'update_my_folder';
        $_POST['email_perso']      = 'marie@example.com';

        [$controller, $useCaseMock] = $this->makeStudentController();
        $useCaseMock->method('getStudentDetails')->willReturn([
            'Prenom'     => 'Marie',
            'Nom'        => 'Dupont',
            'DateLimite' => null,
        ]);
        $useCaseMock->expects($this->once())->method('updateDossier')->willReturn(true);

        try { $controller->control(); } catch (\RuntimeException $e) {}
    }

    /**
     * @test
     * When email_perso is missing from the POST body, updateDossier() must not
     * be called and an error message must be placed in the session.
     */
    public function test_student_update_folder_redirects_when_email_missing(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SESSION['numetu']        = '12345';
        $_GET['page']              = 'update_my_folder';
        $_GET['lang']              = 'fr';
        $_POST['email_perso']      = '';

        [$controller, $useCaseMock] = $this->makeStudentController();
        $useCaseMock->method('getStudentDetails')->willReturn([]);
        $useCaseMock->expects($this->never())->method('updateDossier');

        try { $controller->control(); } catch (\RuntimeException $e) {}

        $this->assertSame("L'email personnel est requis.", $_SESSION['message'] ?? '');
    }

    /**
     * @test
     * On successful update with lang=fr, the French confirmation message must
     * be stored in the session.
     */
    public function test_student_update_folder_sets_success_message_in_french(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SESSION['numetu']        = '12345';
        $_GET['page']              = 'update_my_folder';
        $_GET['lang']              = 'fr';
        $_POST['email_perso']      = 'marie@example.com';

        [$controller, $useCaseMock] = $this->makeStudentController();
        $useCaseMock->method('getStudentDetails')->willReturn([
            'Prenom'     => 'Marie',
            'Nom'        => 'Dupont',
            'DateLimite' => null,
        ]);
        $useCaseMock->method('updateDossier')->willReturn(true);

        try { $controller->control(); } catch (\RuntimeException $e) {}

        $this->assertSame('Folder mis à jour avec succès.', $_SESSION['message'] ?? '');
    }

    /**
     * @test
     * On successful update with lang=en, the English confirmation message must
     * be stored in the session.
     */
    public function test_student_update_folder_sets_success_message_in_english(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SESSION['numetu']        = '12345';
        $_GET['page']              = 'update_my_folder';
        $_GET['lang']              = 'en';
        $_POST['email_perso']      = 'marie@example.com';

        [$controller, $useCaseMock] = $this->makeStudentController();
        $useCaseMock->method('getStudentDetails')->willReturn([
            'Prenom'     => 'Marie',
            'Nom'        => 'Dupont',
            'DateLimite' => null,
        ]);
        $useCaseMock->method('updateDossier')->willReturn(true);

        try { $controller->control(); } catch (\RuntimeException $e) {}

        $this->assertSame('Folder updated successfully.', $_SESSION['message'] ?? '');
    }

    /**
     * @test
     * When updateDossier() returns false, a French error message must be stored
     * in the session to inform the student that the update failed.
     */
    public function test_student_update_folder_sets_error_message_on_failure(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SESSION['numetu']        = '12345';
        $_GET['page']              = 'update_my_folder';
        $_GET['lang']              = 'fr';
        $_POST['email_perso']      = 'marie@example.com';

        [$controller, $useCaseMock] = $this->makeStudentController();
        $useCaseMock->method('getStudentDetails')->willReturn([
            'Prenom'     => 'Marie',
            'Nom'        => 'Dupont',
            'DateLimite' => null,
        ]);
        $useCaseMock->method('updateDossier')->willReturn(false);

        try { $controller->control(); } catch (\RuntimeException $e) {}

        $this->assertSame('Erreur lors de la mise à jour du dossier.', $_SESSION['message'] ?? '');
    }

    /**
     * @test
     * When a DateLimite in the past is stored on the folder, file uploads must
     * be blocked and the session message must mention the exceeded deadline,
     * while updateDossier() is still called for non-file field changes.
     */
    public function test_student_update_folder_blocks_file_uploads_when_deadline_passed(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SESSION['numetu']        = '12345';
        $_GET['page']              = 'update_my_folder';
        $_GET['lang']              = 'fr';
        $_POST['email_perso']      = 'marie@example.com';

        [$controller, $useCaseMock] = $this->makeStudentController();
        $useCaseMock->method('getStudentDetails')->willReturn([
            'Prenom'     => 'Marie',
            'Nom'        => 'Dupont',
            'DateLimite' => '2000-01-01', // well in the past
        ]);
        $useCaseMock->expects($this->once())->method('updateDossier')->willReturn(true);

        try { $controller->control(); } catch (\RuntimeException $e) {}

        $this->assertStringContainsString('date limite dépassée', $_SESSION['message'] ?? '');
    }
}