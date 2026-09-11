<?php

namespace Controllers\WebPlanController;

use Controllers\ControllerInterface;
use Core\View;

/**
 * Admin controller for the site map page.
 *
 * Renders a list of all admin navigation links with translation support.
 */
class WebPlanControllerAdmin implements ControllerInterface
{
    /**
     * Returns true if this controller handles the web_plan-admin page (GET only).
     */
    public static function support(string $page, string $method): bool
    {
        return $page === 'web_plan-admin' && $method === 'GET';
    }

    /**
     * Main entry point. Enforces admin authentication, builds translation helpers,
     * and renders the site map view.
     */
    public function control(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            header('Location: index.php?page=login');
            exit;
        }

        $lang = $_SESSION['lang'] ?? 'fr';

        /** Translates a keyed array using the current language, falling back to French. */
        $t = function (array $translations) use ($lang) {
            return $translations[$lang] ?? $translations['fr'];
        };

        /** Builds a full URL for a given page with the current language appended. */
        $buildUrl = function (string $path, array $params = []) use ($lang) {
            $params['lang'] = $lang;
            $url = 'index.php?page=' . $path;
            foreach ($params as $key => $value) {
                $url .= '&' . urlencode($key) . '=' . urlencode($value);
            }
            return $url;
        };

        /** Translates a French navigation label to English when needed. */
        $translateLabel = function (string $label) use ($lang) {
            $translations = [
                'Accueil'          => 'Home',
                'Tableau de bord'  => 'Dashboard',
                'Partenaires'      => 'Partners',
                'Dossiers'         => 'Folders',
                'Plan du site'     => 'Site Map',
            ];
            return $lang === 'en' ? ($translations[$label] ?? $label) : $label;
        };

        // Admin navigation links shown on the site map
        $links = [
            ['url' => 'home-admin',      'label' => 'Accueil'],
            ['url' => 'dashboard-admin', 'label' => 'Tableau de bord'],
            ['url' => 'partners-admin',  'label' => 'Partenaires'],
            ['url' => 'folders-admin',   'label' => 'Dossiers'],
            ['url' => 'messages-admin',  'label' => 'Messages'],
        ];

        View::render('WebPlan/web_plan_admin', [
            'lang'           => $lang,
            't'              => $t,
            'buildUrl' => $buildUrl,
            'userRole' => 'admin',
            'links'          => $links,
            'translateLabel' => $translateLabel,
        ]);
    }
}