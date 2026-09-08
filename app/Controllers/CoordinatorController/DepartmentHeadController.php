<?php

namespace Controllers\CoordinatorController;

use Controllers\ControllerInterface;
use Model\UseCase\ManageFolderUseCase;
use Core\View;

class DepartmentHeadController implements ControllerInterface
{
    private ManageFolderUseCase $folderUseCase;

    public function __construct()
    {
        $this->folderUseCase = new ManageFolderUseCase();
    }

    public static function support(string $page, string $method): bool
    {
        return $page === 'chef-departement';
    }

    /**
     * Starts the session if it is not already started.
     */
    protected function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Redirects the user to a specific URL.
     * Protected to allow mocking during testing.
     */
    protected function redirect(string $url): never
    {
        header('Location: ' . $url);
        exit;
    }

    /**
     * Renders a view.
     *
     * @param array<string, mixed> $data Data passed to the view
     */
    protected function renderView(string $view, array $data = []): void
    {
        View::render($view, $data);
    }

    /**
     * Main controller logic for the department head page.
     * Handles authentication, filters, pagination, and
     * department head decisions on student folders.
     */
    public function control(): void
    {
        $this->startSession();

        // Allowed roles that can access this controller
        $allowedRoles = ['coordinateur', 'chef_departement', 'admin'];

        if (
            empty($_SESSION['role']) ||
            !in_array($_SESSION['role'], $allowedRoles, true)
        ) {
            $this->redirect('index.php?page=login');
        }

        // Handle language selection
        if (isset($_GET['lang']) && in_array($_GET['lang'], ['fr', 'en'], true)) {
            $_SESSION['lang'] = $_GET['lang'];
        }

        $lang   = $_SESSION['lang'] ?? 'fr';
        $action = $_GET['action']   ?? 'list';
        $role   = $_SESSION['role'];

        // Determine the department associated with the logged-in user
        $userDepartement = null;
        $rolesAvecDepartement = ['chef_departement', 'coordinateur', 'coordinateur_etude'];
        if (in_array($role, $rolesAvecDepartement, true) && !empty($_SESSION['departement'])) {
            $userDepartement = trim((string) $_SESSION['departement']);
        }

        // Translation helper
        $t = function (array $translations) use ($lang): string {
            return $translations[$lang] ?? $translations['fr'] ?? '';
        };

        // Helper to build URLs with language parameter
        $buildUrl = function (string $url, array $params = []) use ($lang): string {
            $params['lang'] = $lang;
            return $url . '?' . http_build_query($params);
        };

        $isLoggedIn = isset($_SESSION['user_id']);

        // ── POST handling: department head decision (approval / rejection) ──
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_avis_chef'])) {
            $numetu = trim(strval($_POST['numetu'] ?? ''));
            $avis   = trim(strval($_POST['avis']   ?? ''));

            if ($numetu !== '' && in_array($avis, ['accepte', 'refuse'], true)) {
                $this->folderUseCase->setAvisChef($numetu, $avis);
            }

            $this->redirect(
                'index.php?page=chef-departement&action=view&numetu=' .
                urlencode($numetu) .
                '&lang=' . urlencode($lang)
            );
        }
        // ─────────────────────────────────────────────────────────────

        $studentData = null;

        // Display details of a specific student folder
        if ($action === 'view' && !empty($_GET['numetu'])) {
            $studentData = $this->folderUseCase->getStudentDetails($_GET['numetu']);
        }

        // Filters used to search student folders
        $filters = [
            'type'        => $_GET['type']    ?? 'all',
            'zone'        => $_GET['zone']    ?? 'all',
            'complet'     => $_GET['complet'] ?? 'all',
            'search'      => $_GET['search']  ?? '',
            'mobilite'    => 'all',
            'departement' => $userDepartement ?? ($_GET['departement'] ?? 'all'),
        ];

        // Pagination parameters
        $currentPage = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
        $perPage     = 10;

        // Retrieve paginated results
        $result = $this->folderUseCase->rechercherAvecPagination($filters, $currentPage, $perPage);

        // Flash message handling
        $message = $_SESSION['message'] ?? '';
        unset($_SESSION['message']);

        // Render the department head interface
        $this->renderView('Coordinator/department_head', [
            'action'          => $action,
            'filters'         => $filters,
            'page'            => $currentPage,
            'message'         => $message,
            'lang'            => $lang,
            'userRole'        => $role,
            'userDepartement' => $userDepartement,
            'studentData'     => $studentData,
            'paginatedData'   => $result['data'],
            'totalCount'      => $result['total'],
            'totalPages'      => $result['totalPages'],
            'isLoggedIn'      => $isLoggedIn,
            't'               => $t,
            'buildUrl'        => $buildUrl,
        ]);
    }
}