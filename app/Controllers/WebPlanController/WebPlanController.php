<?php

namespace Controllers\WebPlanController;

use Controllers\ControllerInterface;

/**
 * Controller for site map redirection.
 * Redirects users to the appropriate version of the site map
 * based on their role: admin or student.
 */
class WebPlanController implements ControllerInterface
{
    /**
     * Checks if this controller supports the given page and method.
     */
    public static function support(string $page, string $method): bool
    {
        return $page === 'web_plan' && $method === 'GET';
    }

    /**
     * Main controller logic.
     * Starts the session if needed and redirects users
     * to the correct site map page depending on their role.
     */
    public function control(): void
    {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Determine language, default to French
        $lang = $_GET['lang'] ?? $_SESSION['lang'] ?? 'fr';

        // Redirect based on user role
        if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
            // Admin users -> admin site map
            header('Location: index.php?page=web_plan-admin&lang=' . urlencode($lang));
            exit;
        } elseif (isset($_SESSION['numetu'])) {
            // Student users -> student site map
            header('Location: index.php?page=web_plan-student&lang=' . urlencode($lang));
            exit;
        } else {
            // Not logged in -> redirect to login page
            header('Location: index.php?page=login');
            exit;
        }
    }
}