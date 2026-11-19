<?php

namespace Controllers;

use Core\View;
use Model\UseCase\ManageDateUseCase;

class DatesController implements ControllerInterface
{
    private ManageDateUseCase $dateUseCase;

    public function __construct()
    {
        $this->dateUseCase = new ManageDateUseCase();
    }

    /**
     * Returns true if this controller handles the given page and HTTP method.
     */
    public static function support(string $page, string $method): bool
    {
        return $page === 'dates-admin' && in_array($method, ['GET', 'POST'], true);
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
     * Reads a request parameter (GET first, then POST) as a string.
     *
     * @param string $key     Parameter name
     * @param string $default Value returned when the parameter is missing or not a string
     */
    protected function input(string $key, string $default = ''): string
    {
        $value = $_GET[$key] ?? $_POST[$key] ?? $default;
        return is_scalar($value) ? trim((string) $value) : $default;
    }

    /**
     * Main entry point. Handles routing, authentication, and dispatches
     * to the appropriate action based on GET/POST parameters.
     */
    public function control(): void
    {
        $this->startSession();

        $action = $this->input('action', 'list');
        $lang = $this->input('lang', 'fr');

        $allowedRoles = ['admin'];

        if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowedRoles, true)) {
            $this->redirect('index.php?page=login');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            // View dates for a day
            if ($action === 'view') {
                $date = $this->input('date');
                $dates = $date !== '' ? $this->dateUseCase->showDatesFromDay($date) : [];
            }
            $this->renderView('dates', ['lang' => $lang, 'dates' => $dates ?? []]);
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($action === 'add') {
                // Add a new date
                $id = $this->input('id');
                if ($id !== '') {
                    $date = $this->input('date');
                    $titre = $this->input('titre');
                    $description = $this->input('description');
                    $type = $this->input('type');
                    $this->dateUseCase->createDate($id, $titre, $description, $date, $type);
                    $_SESSION['message'] = $lang === 'fr' ? 'Date créée' : 'Date created';
                    $this->redirect('index.php?page=dates-admin&action=view&date=' . urlencode($date));
                }
            } elseif ($action === 'put') {
                // Update an existing date
                $id = $this->input('id');
                if ($id !== '') {
                    $date = $this->input('date');
                    $titre = $this->input('titre');
                    $description = $this->input('description');
                    $type = $this->input('type');
                    $this->dateUseCase->updateDate($id, $titre, $description, $date, $type);
                    $_SESSION['message'] = $lang === 'fr' ? 'Date modifiée' : 'Date modified';
                    $this->redirect('index.php?page=dates-admin&action=view&date=' . urlencode($date));
                }
            } elseif ($action === 'delete') {
                // Delete a date
                $id = $this->input('id');
                if ($id !== '') {
                    $date = $this->dateUseCase->getDateFromId($id) ?? '';
                    $this->dateUseCase->deleteDate($id);
                    $_SESSION['message'] = $lang === 'fr' ? 'Date supprimée' : 'Date deleted';
                    $this->redirect('index.php?page=dates-admin&action=view&date=' . urlencode($date));
                }
            }
        }
    }
}
