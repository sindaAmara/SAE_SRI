<?php

namespace Controllers\CoordinatorController;

use Controllers\ControllerInterface;
use Model\UseCase\ManageFolderUseCase;
use Core\View;

/**
 * Controller responsible for the study coordinator interface.
 * Allows authorized users to browse and filter student study mobility
 * folders with pagination and access to detailed student information.
 */
class StudyCoordinatorController implements ControllerInterface
{
    private ManageFolderUseCase $folderUseCase;

    public function __construct()
    {
        $this->folderUseCase = new ManageFolderUseCase();
    }

    public static function support(string $page, string $method): bool
    {
        return $page === 'coordinateur-etude';
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
     * Main controller logic for the study coordinator page.
     * Handles authentication, filters, pagination,
     * and the display of student study mobility folders.
     */
    public function control(): void
    {
        $this->startSession();

        // Roles allowed to access this controller
        $allowedRoles = ['coordinateur', 'coordinateur_etude', 'admin'];
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
        // CDC §7.2 : un profil Département ne voit que son département. Sans département sur le compte,
        // on n'affiche rien (avant : le filtre de l'URL, donc potentiellement tous les départements).
        if (in_array($role, $rolesAvecDepartement, true) && $userDepartement === null) {
            $userDepartement = '__aucun_departement__';
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
            // CDC §7.2 : pas d'accès aux dossiers d'un autre département
            if (
                is_array($studentData) && $userDepartement !== null
                && strcasecmp(trim(strval($studentData['CodeDepartement'] ?? '')), $userDepartement) !== 0
            ) {
                $_SESSION['message'] = $lang === 'en'
                    ? 'This folder belongs to another department.'
                    : "Ce dossier appartient à un autre département.";
                $this->redirect('index.php?page=coordinateur-etude&lang=' . $lang); // retour à la liste, avec le message
            }
        }

        // Filters applied to search study mobility folders
        $filters = [
            'type'        => $_GET['type']    ?? 'all',
            'zone'        => $_GET['zone']    ?? 'all',
            'complet'     => $_GET['complet'] ?? 'all',
            'search'      => $_GET['search']  ?? '',
            'mobilite'    => 'etude',
            'departement' => $userDepartement ?? ($_GET['departement'] ?? 'all'),
            'etape'       => $_GET['etape'] ?? 'all', // cases « Mes dossiers » de l'accueil
        ];

        // Pagination configuration
        $currentPage = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
        $perPage     = 10;

        // Retrieve paginated results
        $result = $this->folderUseCase->rechercherAvecPagination($filters, $currentPage, $perPage);

        // Flash message handling
        $message = $_SESSION['message'] ?? '';
        unset($_SESSION['message']);

        // Render the study coordinator view
        $this->renderView('Coordinator/study_coordinator', [
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
