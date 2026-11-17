<?php

namespace Controllers\WebPlanController;

use Controllers\ControllerInterface;
use Core\View;

/**
 * Controller for the student site map.
 * Handles session, language selection, translation, and renders the student links.
 */
class WebPlanControllerStudent implements ControllerInterface
{
    /**
     * Checks if this controller supports the given page and method.
     */
    public static function support(string $page, string $method): bool
    {
        return $page === 'web_plan-student' && $method === 'GET';
    }

    /**
     * Main controller logic for the student site map.
     * Starts session, checks student authentication, sets language,
     * and prepares link list and translations for the view.
     */
    public function control(): void
    {
        // Start session if not started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Ensure the student is logged in
        if (!isset($_SESSION['numetu'])) {
            header('Location: index.php?page=login');
            exit;
        }

        // Set language, default to French
        $lang = $_SESSION['lang'] ?? 'fr';

        // Translation helper function
        $t = function(array $translations) use ($lang): string {
            return $translations[$lang] ?? $translations['fr'];
        };

        // URL builder helper
        $buildUrl = function(string $path, array $params = []) use ($lang): string {
            $params['lang'] = $lang;
            $url = 'index.php?page=' . $path;
            foreach ($params as $key => $value) {
                $url .= '&' . urlencode($key) . '=' . urlencode($value);
            }
            return $url;
        };

        // Label translation helper
        $translateLabel = function(string $label) use ($lang): string {
            $translations = [
                'Accueil' => 'Home',
                'Mon Tableau de bord' => 'My Dashboard',
                'Partenaires' => 'Partners',
                'Mon Dossier' => 'My Profile',
                'Contact' => 'Contact',
                'Plan du site' => 'Site Map',
            ];
            return $lang === 'en' ? ($translations[$label] ?? $label) : $label;
        };

        // List of student links
        $links = [
            ['url' => 'home-student', 'label' => 'Accueil'],
            ['url' => 'dashboard-student', 'label' => 'Mon Tableau de bord'],
            ['url' => 'partners-student', 'label' => 'Partenaires'],
            ['url' => 'folders-student', 'label' => 'Mon Dossier'],
            ['url' => 'contact-student', 'label' => 'Contact'],
        ];

        // Render the student site map view
        View::render('WebPlan/web_plan_student', [
            'lang'           => $lang,
            't'              => $t,
            'buildUrl' => $buildUrl,
            'userRole' => 'student',
            'links'          => $links,
            'translateLabel' => $translateLabel,
        ]);
    }
}