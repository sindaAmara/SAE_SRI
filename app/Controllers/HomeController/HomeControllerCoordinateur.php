<?php

namespace Controllers\HomeController;

use Controllers\ControllerInterface;
use PDOException;
use Model\UseCase\GetAdminStatsUseCase;
use Core\View;

/**
 * Controller responsible for the coordinator dashboard.
 * It retrieves global statistics about student folders,
 * applies filters (mobility type and department),
 * and renders the coordinator homepage.
 */
class HomeControllerCoordinateur implements ControllerInterface
{
    public static function support(string $page, string $method): bool
    {
        return $page === 'home-coordinateur';
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
     * Writes a message to the PHP error log.
     */
    protected function log(string $message): void
    {
        error_log($message);
    }

    /**
     * Creates the use case instance used to retrieve statistics.
     * Can be overridden during testing.
     */
    protected function makeUseCase(): GetAdminStatsUseCase
    {
        $repo = new \Model\Persistence\FolderRepositoryPDO();
        return new GetAdminStatsUseCase($repo);
    }

    /**
     * Retrieves the list of all departments.
     *
     * @return array<int, string>
     */
    protected function fetchDepartements(): array
    {
        return (new \Model\Persistence\FolderRepositoryPDO())->getAllDepartements();
    }

    /**
     * Main controller logic for the coordinator dashboard.
     * Handles authentication, filters, statistics retrieval,
     * and rendering the coordinator homepage.
     */
    public function control(): void
    {
        $this->startSession();

        // Allowed roles that can access the coordinator dashboard
        $allowedRoles = ['coordinateur', 'coordinateur_etude', 'coordinateur_stage', 'chef_departement'];
        if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowedRoles, true)) {
            $this->redirect('index.php?page=login');
        }

        // Handle language selection
        if (isset($_GET['lang']) && in_array($_GET['lang'], ['fr', 'en'], true)) {
            $_SESSION['lang'] = $_GET['lang'];
        }
        $lang = $_SESSION['lang'] ?? 'fr';

        // Handle accessibility option (tritanopia color mode)
        if (isset($_GET['tritanopia'])) {
            $_SESSION['tritanopia'] = (strval($_GET['tritanopia']) === '1');
        }

        // Mobility filter (study or internship)
        $mobiliteFilter = null;
        if (isset($_GET['mobilite']) && in_array($_GET['mobilite'], ['etude', 'stage'], true)) {
            $mobiliteFilter = $_GET['mobilite'];
        }

        // Department filter
        $departementFilter = null;
        if (isset($_GET['departement']) && !empty($_GET['departement'])) {
            $departementFilter = strval($_GET['departement']);
        }

        // Retrieve all departments
        $allDepartements = [];
        try {
            $allDepartements = $this->fetchDepartements();
        } catch (PDOException $e) {
            $this->log('HomeControllerCoordinateur - getAllDepartements Error: ' . $e->getMessage());
        }

        $stats                = null;
        $completionPercentage = 0.0;

        try {
            $useCase = $this->makeUseCase();
            $stats   = $useCase->execute($mobiliteFilter, $departementFilter);

            // Calculate folder completion percentage
            $dossierStats         = $stats->getDossierStats();
            $completionPercentage = $dossierStats->getTotal() > 0
                ? round($dossierStats->getCompleted() / $dossierStats->getTotal() * 100, 1)
                : 0.0;
        } catch (PDOException $e) {
            $this->log('HomeControllerCoordinateur Error: ' . $e->getMessage());
        }

        // Translation helper
        $t = function (array $frEn) use ($lang): string {
            return $lang === 'en' ? $frEn['en'] : $frEn['fr'];
        };

        // Helper to build URLs including the language parameter
        $buildUrl = function (string $path, array $params = []) use ($lang): string {
            $params['lang'] = $lang;
            $separator      = str_contains($path, '?') ? '&' : '?';
            return $path . $separator . http_build_query($params);
        };

        // Render the coordinator dashboard
        $this->renderView('HomePage/home_coordinateur', [
            'isLoggedIn'           => true,
            'lang'                 => $lang,
            'userRole'             => $_SESSION['role'],
            'completionPercentage' => $completionPercentage,
            'stats'                => $stats,
            'mobiliteFilter'       => $mobiliteFilter,
            'departementFilter'    => $departementFilter,
            'allDepartements'      => $allDepartements,
            't'                    => $t,
            'buildUrl'             => $buildUrl,
        ]);
    }
}