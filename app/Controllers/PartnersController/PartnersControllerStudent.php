<?php

namespace Controllers\PartnersController;

use Controllers\ControllerInterface;
use Core\View;

/**
 * Controller for displaying partner universities to students.
 * Handles language selection, accessibility, and partner type (AMU/IUT).
 */
class PartnersControllerStudent implements ControllerInterface
{
    /**
     * Checks if this controller supports the given page.
     */
    public static function support(string $page, string $method): bool
    {
        return $page === 'partners-student';
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
     * Renders a view with provided data.
     *
     * @param array<string, mixed> $data Data passed to the view
     */
    protected function renderView(string $view, array $data = []): void
    {
        View::render($view, $data);
    }

    /**
     * Main controller logic for the Partners Student page.
     * Handles authentication, language selection, accessibility,
     * partner type selection, and renders the view.
     */
    public function control(): void
    {
        $this->startSession();

        // Ensure the student is logged in
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

        // Handle accessibility option (tritanopia mode)
        if (isset($_GET['tritanopia'])) {
            $_SESSION['tritanopia'] = (strval($_GET['tritanopia']) === '1');
        }

        // Determine partner type (AMU or IUT)
        $partner = isset($_GET['partner']) && $_GET['partner'] === 'iut' ? 'iut' : 'amu';

        // Set page title based on partner and language
        $titre = match(true) {
            $partner === 'amu' && $lang === 'fr' => 'Universités Destinations AMU',
            $partner === 'amu' && $lang === 'en' => 'AMU Destinations Universities',
            $partner === 'iut' && $lang === 'fr' => 'Universités Destinations IUT',
            $partner === 'iut' && $lang === 'en' => 'IUT Destinations Universities',
            default                              => 'Universités Destinations AMU',
        };

        // Translation helper
        $t = function (array $frEn) use ($lang): string {
            return $lang === 'en' ? $frEn['en'] : $frEn['fr'];
        };

        // URL builder helper
        $buildUrl = function (string $path, array $params = []) use ($lang): string {
            $params['lang'] = $lang;
            $separator = (strpos($path, '?') === false) ? '?' : '&';
            return $path . $separator . http_build_query($params);
        };

        // Render the partners view
        $this->renderView('Partners/partners_student', [
            'titre'    => $titre,
            'lang'     => $lang,
            'partner'  => $partner,
            't'        => $t,
            'buildUrl' => $buildUrl,
            'userRole' => 'student',
        ]);
    }
}