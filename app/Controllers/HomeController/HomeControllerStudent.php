<?php

namespace Controllers\HomeController;

use Controllers\ControllerInterface;
use Core\View;

/**
 * Controller responsible for the student homepage.
 * Ensures the student is authenticated, manages language
 * and accessibility options, and renders the student dashboard.
 */
class HomeControllerStudent implements ControllerInterface
{
    public static function support(string $page, string $method): bool
    {
        return $page === 'home-student' && $method === 'GET';
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
     * Main controller logic for the student homepage.
     * Handles authentication, language selection,
     * accessibility options, and rendering the view.
     */
    public function control(): void
    {
        $this->startSession();

        // Ensure the student is authenticated
        if (empty($_SESSION['numetu'])) {
            $this->redirect('index.php?page=login&error=not_logged_in');
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

        // Check if the student is logged in
        $isStudentLoggedIn = isset($_SESSION['numetu']);

        // Translation helper
        $t = function (array $frEn) use ($lang): string {
            return $lang === 'en' ? $frEn['en'] : $frEn['fr'];
        };

        // Helper to build URLs including the language parameter
        $buildUrl = function (string $path, array $params = []) use ($lang): string {
            $params['lang'] = $lang;
            $separator = (strpos($path, '?') === false) ? '?' : '&';
            return $path . $separator . http_build_query($params);
        };

        // Render the student homepage
        $this->renderView('HomePage/home_student', [
            'isLoggedIn' => $isStudentLoggedIn,
            'lang'       => $lang,
            't'          => $t,
            'buildUrl'   => $buildUrl,
        ]);
    }
}