<?php

namespace Controllers;

use Core\View;
use Model\UseCase\ManageDateUseCase;

class DatesController
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
        return $page === 'dates-admin' && $method === 'GET' || $method === 'POST';
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
     * Main entry point. Handles routing, authentication, and dispatches
     * to the appropriate action based on GET/POST parameters.
     */
    public function control(): void
    {
        $this->startSession();

        $page = $_GET['page'] ?? $_POST['page'] ?? 'dates';
        $action = $_GET['action'] ?? $_POST['action'] ?? 'list';
        $lang = $_GET['lang'] ?? $_POST['action'] ?? 'fr';

        $allowedRoles = ['admin'];

        if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowedRoles, true)) {
            $this->redirect('index.php?page=login');
        }

        // Handle POST actions
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {

            // View dates for a day
            if ($action === 'view') {
                $date = $_GET['date'] ?? null;
                $dates = $this->dateUseCase->showDatesFromDay($date);
            }
            $this->renderView('dates', ['lang' => $lang, 'dates' => $dates ?? []]);
        }

        elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($action === 'add') {
                $id = $_POST['id'] ?? null;
                if (!empty($id)) {
                    $date = $_POST['date'] ?? null;
                    $titre = $_POST['titre'] ?? null;
                    $description = $_POST['description'] ?? null;
                    $type = $_POST['type'] ?? null;
                    $this->dateUseCase->createDate($id, $titre, $description, $date, $type);
                    $_SESSION['message'] = $lang === 'fr' ? 'Date créée' : 'Date created';
                    $this->redirect('index.php?page=dates-admin&action=view&date=' . $date);
                }
            } // Add a new date
            elseif ($action === 'put') {
                $id = $_POST['id'] ?? null;
                if (!empty($id)) {
                    $date = $_POST['date'] ?? null;
                    $titre = $_POST['titre'] ?? null;
                    $description = $_POST['description'] ?? null;
                    $type = $_POST['type'] ?? null;
                    $this->dateUseCase->updateDate($id, $titre, $description, $date, $type);
                    $_SESSION['message'] = $lang === 'fr' ? 'Date modifiée' : 'Date modified';
                    $this->redirect('index.php?page=dates-admin&action=view&date=' . $date);
                }
            } // Delete a date
            elseif ($action === 'delete') {
                $id = $_POST['id'] ?? null;
                if (!empty($id)) {
                    $date = $this->dateUseCase->getDateFromId($id);
                    $this->dateUseCase->deleteDate($id);
                    $_SESSION['message'] = $lang === 'fr' ? 'Date créée' : 'Date created';
                    $this->redirect('index.php?page=dates-admin&action=view&date=' . $date);
                }
            }
        }
    }
}