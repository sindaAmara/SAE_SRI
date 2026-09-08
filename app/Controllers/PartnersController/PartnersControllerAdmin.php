<?php

namespace Controllers\PartnersController;

use Controllers\ControllerInterface;
use Model\UseCase\AddPartnerUseCase;
use Model\Persistence\PartnerRepositoryPDO;
use Model\Entity\Partner;
use Core\View;
use PDOException;

/**
 * Admin controller for managing partner universities.
 *
 * Handles displaying the partners page and processing new partner submissions.
 */
class PartnersControllerAdmin implements ControllerInterface
{
    /**
     * Returns true if this controller handles the partners-admin page.
     */
    public static function support(string $page, string $method): bool
    {
        return $page === 'partners-admin';
    }

    /**
     * Starts the PHP session if not already active.
     */
    protected function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Redirects the user to the given URL and exits.
     */
    protected function redirect(string $url): never
    {
        header('Location: ' . $url);
        exit;
    }

    /**
     * Renders a view template with the given data.
     *
     * @param array<string, mixed> $data Variables passed to the view
     */
    protected function renderView(string $view, array $data = []): void
    {
        View::render($view, $data);
    }

    /**
     * Main entry point. Enforces admin authentication, resolves language and
     * accessibility settings, handles partner creation on POST, then renders the view.
     */
    public function control(): void
    {
        $this->startSession();

        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            $this->redirect('index.php?page=login');
        }

        // Persist language preference in session
        if (isset($_GET['lang'])) {
            $langParam = strval($_GET['lang']);
            if (in_array($langParam, ['fr', 'en'], true)) {
                $_SESSION['lang'] = $langParam;
            }
        }
        $lang = $_SESSION['lang'] ?? 'fr';

        // Persist tritanopia (colour-blindness) accessibility preference in session
        if (isset($_GET['tritanopia'])) {
            $_SESSION['tritanopia'] = (strval($_GET['tritanopia']) === '1');
        }

        $errorMessage = '';
        $success      = isset($_GET['success']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $continent   = trim($_POST['continent']   ?? '');
            $country     = trim($_POST['country']     ?? '');
            $city        = trim($_POST['city']        ?? '');
            $institution = trim($_POST['institution'] ?? '');
            $type        = trim($_POST['type']        ?? '');
            $validTypes  = ['amu', 'iut'];

            if ($continent && $country && $city && $institution && in_array($type, $validTypes, true)) {
                try {
                    $repository = new PartnerRepositoryPDO();
                    $useCase    = new AddPartnerUseCase($repository);
                    $partner    = new Partner($continent, $country, $city, $institution, $type);
                    $useCase->execute($partner);
                    $this->redirect('index.php?page=partners-admin&success=1&lang=' . $lang);
                } catch (PDOException $e) {
                    error_log("Partner insertion error: " . $e->getMessage());
                    $errorMessage = $e->getMessage();
                }
            } else {
                $errorMessage = $lang === 'fr'
                    ? 'Tous les champs sont requis.'
                    : 'All fields are required.';
            }
        }

        $titre = $lang === 'en' ? 'Destination Universities' : 'Universités Destinations';

        $t = function (array $frEn) use ($lang): string {
            return $lang === 'en' ? $frEn['en'] : $frEn['fr'];
        };

        $buildUrl = function (string $path, array $params = []) use ($lang): string {
            $params['lang'] = $lang;
            $separator = (strpos($path, '?') === false) ? '?' : '&';
            return $path . $separator . http_build_query($params);
        };

        $this->renderView('Partners/partners_admin', [
            'titre'        => $titre,
            'lang'         => $lang,
            'errorMessage' => $errorMessage,
            'success'      => $success,
            't'            => $t,
            'buildUrl'     => $buildUrl,
        ]);
    }
}