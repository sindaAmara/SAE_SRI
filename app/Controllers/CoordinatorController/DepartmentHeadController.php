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
        // CDC §7.2 : un profil Département ne voit que son département. Sans département sur le compte,
        // on n'affiche rien (avant : le filtre de l'URL, donc potentiellement tous les départements).
        if (in_array($role, $rolesAvecDepartement, true) && $userDepartement === null) {
            $userDepartement = '__aucun_departement__';
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

            // CDC §7.2 : le chef ne décide que pour les dossiers de son département
            $dossierChef = $numetu !== '' ? $this->folderUseCase->getStudentDetails($numetu) : null;
            $memeDept    = $userDepartement === null || (is_array($dossierChef)
                    && strcasecmp(trim(strval($dossierChef['CodeDepartement'] ?? '')), $userDepartement) === 0);

            if (!$memeDept) {
                $_SESSION['message'] = $lang === 'en'
                    ? 'This folder belongs to another department.'
                    : "Ce dossier appartient à un autre département.";
            } elseif ($numetu !== '' && in_array($avis, ['accepte', 'refuse'], true)) {
                $ok = $this->folderUseCase->setAvisChef($numetu, $avis);
                if (!$ok) {
                    // setAvisChef refuse tant que le coordinateur n'a pas validé (étape 4)
                    $_SESSION['message'] = $lang === 'en'
                        ? 'Not possible: the folder must first be validated by the admin office and the coordinator.'
                        : "Impossible : le dossier doit d'abord être validé par le secrétariat puis par le coordinateur.";
                }
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
            // CDC §7.2 : pas d'accès aux dossiers d'un autre département
            if (
                is_array($studentData) && $userDepartement !== null
                && strcasecmp(trim(strval($studentData['CodeDepartement'] ?? '')), $userDepartement) !== 0
            ) {
                $_SESSION['message'] = $lang === 'en'
                    ? 'This folder belongs to another department.'
                    : "Ce dossier appartient à un autre département.";
                $this->redirect('index.php?page=chef-departement&lang=' . $lang); // retour à la liste, avec le message
            }
        }

        // Filters used to search student folders
        $filters = [
            'type'        => $_GET['type']    ?? 'all',
            'zone'        => $_GET['zone']    ?? 'all',
            'complet'     => $_GET['complet'] ?? 'all',
            'search'      => $_GET['search']  ?? '',
            'mobilite'    => in_array($_GET['mobilite'] ?? '', ['etude', 'stage'], true) ? strval($_GET['mobilite']) : 'all',
            'departement' => $userDepartement ?? ($_GET['departement'] ?? 'all'),
            'etape'       => $_GET['etape'] ?? 'all', // cases « Mes dossiers » de l'accueil
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
