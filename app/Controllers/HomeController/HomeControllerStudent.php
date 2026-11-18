<?php

namespace Controllers\HomeController;

use Controllers\ControllerInterface;
use Core\View;
use Model\Entity\Partner;
use Model\Persistence\PartnerRepositoryPDO;
use Model\UseCase\GetPartnersForMapUseCase;

/**
 * Controller responsible for the student homepage and its partners map data.
 *
 * Routes:
 *   GET index.php?page=home-student                   Renders the homepage
 *   GET index.php?page=api-partners-map&site=amu|iut  Returns the partners as JSON
 */
class HomeControllerStudent implements ControllerInterface
{
    public static function support(string $page, string $method): bool
    {
        return in_array($page, ['home-student', 'api-partners-map'], true) && $method === 'GET';
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
     * Sends the requested route to the matching response.
     */
    public function control(): void
    {
        $this->startSession();

        if (($_GET['page'] ?? '') === 'api-partners-map') {
            $this->sendPartnersMapData();
            return;
        }

        $this->showHomePage();
    }

    /**
     * Main logic for the student homepage.
     * Handles authentication, language selection,
     * accessibility options, and rendering the view.
     */
    private function showHomePage(): void
    {
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
            'userRole' => 'student',
            'lang' => $lang,
            't' => $t,
            'buildUrl' => $buildUrl,
        ]);
    }

    /**
     * Returns the geolocated partners of the requested site ('amu' or 'iut') as JSON.
     */
    private function sendPartnersMapData(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        // Ensure the student is authenticated
        if (empty($_SESSION['numetu'])) {
            http_response_code(401);
            echo json_encode(['error' => 'not_authenticated']);
            exit;
        }

        $site = strval($_GET['site'] ?? 'amu');

        $repository = new PartnerRepositoryPDO();
        $useCase    = new GetPartnersForMapUseCase($repository);
        $partners   = $useCase->execute($site);

        echo json_encode([
            'site'     => in_array($site, ['amu', 'iut'], true) ? $site : 'amu',
            'count'    => count($partners),
            'partners' => array_map(static function (Partner $partner): array {
                return [
                    'id'        => $partner->getId(),
                    'nom'       => $partner->getInstitution(),
                    'ville'     => $partner->getCity(),
                    'pays'      => $partner->getCountry(),
                    'continent' => $partner->getContinent(),
                    'lat'       => $partner->getLatitude(),
                    'lng'       => $partner->getLongitude(),
                    'offres'    => $partner->getOffers(),
                ];
            }, $partners),
        ]);
        exit;
    }
}