<?php

namespace Controllers\HomeController;

use Controllers\ControllerInterface;
use Model\UseCase\ManageFolderUseCase;
use PDOException;
use Model\UseCase\GetAdminStatsUseCase;
use Core\View;

/**
 * Controller responsible for the admin home dashboard.
 * It retrieves global statistics about student folders,
 * applies filters (mobility type and department),
 * and renders the admin dashboard view.
 */
class HomeControllerAdmin implements ControllerInterface
{
    public static function support(string $page, string $method): bool
    {
        return $page === 'home-admin' && $method === 'GET';
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
     * Protected to allow mocking during tests.
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
     * Creates the use case instance.
     * Can be overridden during testing.
     */
    protected function makeUseCase(): GetAdminStatsUseCase
    {
        return new GetAdminStatsUseCase(new \Model\Persistence\FolderRepositoryPDO());
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
     * Main controller logic for the admin dashboard.
     * Handles authentication, filters, statistics retrieval,
     * and rendering the admin homepage.
     */
    public function control(): void
    {
        $this->startSession();

        // Ensure the user is an administrator
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            $this->redirect('index.php?page=login');
        }

        // Handle language selection
        if (isset($_GET['lang'])) {
            $langParam = strval($_GET['lang']);
            if (in_array($langParam, ['fr', 'en'], true)) {
                $_SESSION['lang'] = $langParam;
            }
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
            $this->log("Error fetching departments: " . $e->getMessage());
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
            $this->log("HomeControllerAdmin Error: " . $e->getMessage());
        }

        // Translation helper
        $t = function (array $frEn) use ($lang): string {
            return $lang === 'en' ? $frEn['en'] : $frEn['fr'];
        };

        // Helper to build URLs including the language parameter
        $buildUrl = function (string $path, array $params = []) use ($lang): string {
            $params['lang'] = $lang;
            $separator = str_contains($path, '?') ? '&' : '?';
            return $path . $separator . http_build_query($params);
        };

        // Render the admin dashboard
        $this->renderView('HomePage/home_admin', [
            'isLoggedIn'           => true,
            'userRole'             => $_SESSION['role'] ?? null,
            'lang'                 => $lang,
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