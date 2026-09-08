<?php

namespace Controllers\CoordinatorController;

use Controllers\ControllerInterface;
use Model\UseCase\ManageFolderUseCase;
use Core\View;

/**
 * Controller responsible for the internship coordinator interface.
 * Allows authorized users to view and filter student internship folders
 * with pagination and access to detailed student information.
 */
class InternershipCoordinatorController implements ControllerInterface
{
    private ManageFolderUseCase $folderUseCase;

    public function __construct()
    {
        $this->folderUseCase = new ManageFolderUseCase();
    }

    public static function support(string $page, string $method): bool
    {
        return $page === 'coordinateur-stage';
    }

    /**
     * Starts the session if it is not already active.
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
     * Main controller logic for the internship coordinator page.
     * Handles authentication, filters, pagination,
     * and the display of student internship folders.
     */
    public function control(): void
    {
        $this->startSession();

        // Roles allowed to access this controller
        $allowedRoles = ['coordinateur', 'coordinateur_stage', 'admin'];
        if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowedRoles, true)) {
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
        $rolesAvecDepartement = ['chef_departement', 'coordinateur', 'coordinateur_stage', 'coordinateur_etude'];
        if (in_array($role, $rolesAvecDepartement, true) && !empty($_SESSION['departement'])) {
            $userDepartement = trim((string) $_SESSION['departement']);
        }

        // Translation helper
        $t = function (array $translations) use ($lang): string {
            return $translations[$lang] ?? $translations['fr'] ?? '';
        };

        // Helper to build URLs including the language parameter
        $buildUrl = function (string $url, array $params = []) use ($lang): string {
            $params['lang'] = $lang;
            return $url . '?' . http_build_query($params);
        };

        $isLoggedIn = isset($_SESSION['user_id']);

        $studentData = null;

        // Display details of a specific student folder
        if ($action === 'view' && !empty($_GET['numetu'])) {
            $studentData = $this->folderUseCase->getStudentDetails($_GET['numetu']);
        }

        // Filters applied to search internship folders
        $filters = [
            'type'        => $_GET['type']    ?? 'all',
            'zone'        => $_GET['zone']    ?? 'all',
            'complet'     => $_GET['complet'] ?? 'all',
            'search'      => $_GET['search']  ?? '',
            'mobilite'    => 'stage',
            'departement' => $userDepartement ?? ($_GET['departement'] ?? 'all'),
        ];

        // Pagination configuration
        $currentPage = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
        $perPage     = 10;

        // Retrieve paginated search results
        $result = $this->folderUseCase->rechercherAvecPagination($filters, $currentPage, $perPage);

        // Flash message management
        $message = $_SESSION['message'] ?? '';
        unset($_SESSION['message']);

        // Render the internship coordinator view
        $this->renderView('Coordinator/internship_coordinator', [
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