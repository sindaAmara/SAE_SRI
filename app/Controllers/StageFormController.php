<?php

namespace Controllers;

use Core\View;
use Model\UseCase\ManageFolderUseCase;
use Model\UseCase\ManageStageFormUseCase;

/**
 * Handles the end-of-internship form: display and submission (student),
 * and consultation of responses (admin).
 */
class StageFormController implements ControllerInterface
{
    private ManageFolderUseCase $folderUseCase;
    private ManageStageFormUseCase $formUseCase;

    public function __construct(
        ?ManageFolderUseCase $folderUseCase = null,
        ?ManageStageFormUseCase $formUseCase = null
    ) {
        $this->folderUseCase = $folderUseCase ?? new ManageFolderUseCase();
        $this->formUseCase   = $formUseCase   ?? new ManageStageFormUseCase();
    }

    /**
     * Returns true if this controller handles the given page.
     * 'stage-form'       -> student: GET (display) / POST (submit)
     * 'stage-form-admin' -> admin: GET (list responses)
     */
    public static function support(string $page, string $method): bool
    {
        if ($page === 'stage-form' && in_array($method, ['GET', 'POST'], true)) {
            return true;
        }

        if ($page === 'stage-form-admin' && $method === 'GET') {
            return true;
        }

        return false;
    }

    public function control(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $page = $_GET['page'] ?? '';

        switch ($page) {
            case 'stage-form':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $this->storeForm();
                } else {
                    $this->showForm();
                }
                break;

            case 'stage-form-admin':
                $this->showResponsesAdmin();
                break;

            default:
                http_response_code(404);
                echo "Page not found";
                break;
        }
    }

    /**
     * Displays the end-of-internship form for the currently logged-in student.
     */
    private function showForm(): void
    {
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
            header('Location: index.php?page=login');
            exit;
        }

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
        if ($status !== 'accepte') {
            header('Location: index.php?page=dashboard-student');
            exit;
        }

        $lang = is_string($_GET['lang'] ?? null) ? $_GET['lang'] : 'fr';

        $t = function (array $frEn) use ($lang): string {
            return ($lang === 'en') ? $frEn['en'] : $frEn['fr'];
        };

        $existing = $this->formUseCase->getExistingResponse($numetu);

        View::render('Dashboard/form_student', [
            'lang'      => $lang,
            't'         => $t,
            'folder'    => $folder,
            'errors'    => [],
            'old'       => $existing ?? [],
        ]);
    }

    /**
     * Validates and stores the submitted form.
     */
    private function storeForm(): void
    {
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
            header('Location: index.php?page=login');
            exit;
        }


        $numetu = $_SESSION['numetu'] ?? null;
        if ($numetu === null) {
            header('Location: index.php?page=login');
            exit;
        }

        $data = [
            'host_institution' => trim(strval($_POST['host_institution'] ?? '')),
            'start_date'       => trim(strval($_POST['start_date'] ?? '')),
            'end_date'         => trim(strval($_POST['end_date'] ?? '')),
            'evaluation'       => trim(strval($_POST['evaluation'] ?? '')),
        ];

        $errors = [];
        foreach (['host_institution', 'start_date', 'end_date'] as $field) {
            if ($data[$field] === '') {
                $errors[$field] = 'required';
            }
        }

        if (!empty($errors)) {
            $lang = is_string($_GET['lang'] ?? null) ? $_GET['lang'] : 'fr';
            $t = function (array $frEn) use ($lang): string {
                return ($lang === 'en') ? $frEn['en'] : $frEn['fr'];
            };

            $folder = $this->folderUseCase->getStudentDetails($numetu);

            View::render('Dashboard/form_student', [
                'lang'      => $lang,
                't'         => $t,
                'folder'    => is_array($folder) ? $folder : [],
                'errors'    => $errors,
                'old'       => $data,
            ]);
            return;
        }

        $this->formUseCase->saveResponse($numetu, $data);

        header('Location: index.php?page=dashboard-student&submitted=1');
        exit;
    }

    /**
     * Displays all submitted responses to admins.
     */
    private function showResponsesAdmin(): void
    {
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            header('Location: index.php?page=login');
            exit;
        }

        $lang = is_string($_GET['lang'] ?? null) ? $_GET['lang'] : 'fr';

        $t = function (array $frEn) use ($lang): string {
            return ($lang === 'en') ? $frEn['en'] : $frEn['fr'];
        };

        $responses = $this->formUseCase->getAllResponses();

        View::render('Dashboard/form_admin', [
            'lang'      => $lang,
            't'         => $t,
            'responses' => is_array($responses) ? $responses : [],
        ]);
    }
}