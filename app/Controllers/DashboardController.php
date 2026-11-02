<?php

namespace Controllers;

use Model\UseCase\ManageFolderUseCase;
use Core\View;

/**
 * Handles the admin and student dashboard pages.
 *
 * The admin dashboard displays all folders with filtering support.
 * The student dashboard shows the current folder status and progress.
 */
class DashboardController implements ControllerInterface
{
    private ManageFolderUseCase $folderUseCase;

    /**
     * @param ManageFolderUseCase|null $folderUseCase Injected use case (defaults to a new instance)
     */
    public function __construct(?ManageFolderUseCase $folderUseCase = null)
    {
        $this->folderUseCase = $folderUseCase ?? new ManageFolderUseCase();
    }

    /**
     * Returns true if this controller handles the given page (GET only).
     */
    public static function support(string $page, string $method): bool
    {
        return in_array($page, ['dashboard-student'], true) && $method === 'GET';
    }

    /**
     * Main entry point. Starts the session and dispatches to the correct dashboard.
     */
    public function control(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $page = $_GET['page'] ?? parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $page = is_string($page) ? trim($page, '/') : '';

        switch ($page) {
            case 'dashboard-student': $this->showStudentDashboard(); break;
            default:
                http_response_code(404);
                echo "Page not found";
                break;
        }
    }

    /**
     * Renders the student dashboard showing the current folder status and a progress bar.
     * Maps "accepte" and "refuse" to the same visual step since both are terminal states.
     */
    private function showStudentDashboard(): void
    {
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
            header('Location: index.php?page=login');
            exit;
        }

        $lang = is_string($_GET['lang'] ?? null) ? $_GET['lang'] : 'fr';

        if (!isset($_SESSION['numetu'])) {
            header('Location: index.php?page=login');
            exit;
        }

        $numetu = $_SESSION['numetu'];
        $folder = $this->folderUseCase->getStudentDetails($numetu);

        if (!is_array($folder)) {
            $folder = [];
        }

        $status = strval($folder['status'] ?? 'depot');

        // Both "accepte" and "refuse" map to the last visual step
        $steps             = ['depot', 'instruction', 'accepte'];
        $statusForProgress = in_array($status, ['accepte', 'refuse'], true) ? 'accepte' : $status;
        $currentStepIndex  = array_search($statusForProgress, $steps, true);
        if ($currentStepIndex === false) $currentStepIndex = 0;

        $totalSteps         = count($steps);
        $currentStepInt     = (int)$currentStepIndex;
        $progressPercentage = ($currentStepInt / ($totalSteps - 1)) * 100;

        // Avoid a fully empty bar at the "depot" step for visual clarity
        if ($progressPercentage == 0) $progressPercentage = 8;

        $progressStyle = "width: {$progressPercentage}%;";

        $t = function (array $frEn) use ($lang): string {
            return ($lang === 'en') ? $frEn['en'] : $frEn['fr'];
        };

        $buildUrl = function (string $path, array $params = []) use ($lang): string {
            $params['lang'] = $lang;
            $separator = (strpos($path, '?') === false) ? '?' : '&';
            return $path . $separator . http_build_query($params);
        };

        View::render('Dashboard/dashboard_student', [
            'folder'        => $folder,
            'lang'          => $lang,
            'status'        => $status,
            'progressStyle' => $progressStyle,
            't'             => $t,
            'buildUrl'      => $buildUrl,
        ]);
    }
}