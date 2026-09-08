<?php

declare(strict_types=1);

namespace Controllers\FolderController;

use Controllers\ControllerInterface;
use Model\UseCase\ManageFolderUseCase;
use Core\View;

/**
 * Student controller for managing the student's own folder.
 *
 * Handles displaying, creating, and updating a student's mobility folder,
 * as well as sending email notifications on document deposit.
 */
class FoldersControllerStudent implements ControllerInterface
{
    private ManageFolderUseCase $folderUseCase;

    public function __construct()
    {
        $this->folderUseCase = new ManageFolderUseCase();
    }

    /**
     * Returns true if this controller handles the given page.
     */
    public static function support(string $page, string $method): bool
    {
        return in_array($page, ['folders-student', 'update_my_folder', 'create_folder'], true);
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
     * Main entry point. Ensures the student is logged in, then dispatches
     * to the appropriate action based on the HTTP method and page parameter.
     */
    public function control(): void
    {
        $this->startSession();

        if (empty($_SESSION['numetu'])) {
            $this->redirect('index.php?page=login&error=not_logged_in');
        }

        $numetu = (string)$_SESSION['numetu'];
        $lang   = isset($_GET['lang']) && is_string($_GET['lang']) ? $_GET['lang'] : 'fr';
        $page   = isset($_GET['page']) && is_string($_GET['page']) ? $_GET['page'] : '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($page === 'update_my_folder') { $this->handleUpdateFolder($numetu, $lang); return; }
            if ($page === 'create_folder')    { $this->handleCreateFolder($numetu, $lang); return; }
        }

        $this->displayFolderPage($numetu, $lang);
    }

    /**
     * Loads the student's folder and renders the student folder view.
     */
    private function displayFolderPage(string $numetu, string $lang): void
    {
        $studentData = $this->folderUseCase->getStudentDetails($numetu);
        $message     = isset($_SESSION['message']) && is_string($_SESSION['message']) ? $_SESSION['message'] : '';
        unset($_SESSION['message']);

        $data = is_array($studentData) ? $studentData : [];

        $this->renderView('Folder/folders_student', [
            'dossier'   => $data,
            'studentId' => $numetu,
            'message'   => $message,
            'lang'      => $lang,
        ]);
    }

    /**
     * Processes uploaded files from the current request.
     * Reads file contents into $data under the matching field key.
     * Returns an array of error messages for any failed uploads.
     *
     * @param  array<string, mixed> $data   Data array to populate with file contents
     * @return array<int, string>           List of error messages (empty if all succeeded)
     */
    private function handleFileUploads(array &$data, string $lang): array
    {
        $errors     = [];
        $fileFields = ['photo', 'cv', 'convention', 'lettre_motivation', 'langues_file'];

        foreach ($fileFields as $field) {
            if (isset($_FILES[$field])) {
                $error = $_FILES[$field]['error'];

                if ($error === UPLOAD_ERR_OK) {
                    $content = file_get_contents($_FILES[$field]['tmp_name']);
                    if ($content !== false) {
                        $data[$field] = $content;
                    } else {
                        $errors[] = $lang === 'fr' ? "Impossible de lire le fichier '$field'." : "Cannot read file '$field'.";
                    }
                } elseif ($error !== UPLOAD_ERR_NO_FILE) {
                    $msg = $lang === 'fr' ? "Erreur upload pour '$field' (Code: $error)" : "Upload error for '$field' (Code: $error)";
                    if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
                        $msg .= $lang === 'fr' ? " : Le fichier est trop lourd (limite dépassée)." : " : File is too large.";
                    }
                    $errors[] = $msg;
                }
            }
        }
        return $errors;
    }

    /**
     * Handles the creation of a new student folder.
     * Redirects back with an error if the student already has a folder,
     * if validation fails, or if file uploads fail.
     * Sends a deposit confirmation email for each uploaded document on success.
     */
    private function handleCreateFolder(string $numetu, string $lang): void
    {
        // Prevent duplicate folder creation
        if ($this->folderUseCase->getStudentDetails($numetu)) {
            $_SESSION['message'] = $lang === 'fr' ? "Vous avez déjà déposé un dossier." : "You have already submitted an application.";
            $this->redirect('index.php?page=folders-student&lang=' . $lang);
        }

        $data = [
            'NumEtu'             => $numetu,
            'Nom'                => $_POST['nom']         ?? '',
            'Prenom'             => $_POST['prenom']      ?? '',
            'DateNaissance'      => $_POST['naissance']   ?? null,
            'Sexe'               => $_POST['sexe']        ?? null,
            'Adresse'            => $_POST['adresse']     ?? null,
            'CodePostal'         => $_POST['cp']          ?? null,
            'Ville'              => $_POST['ville']       ?? null,
            'EmailPersonnel'     => $_POST['email_perso'] ?? '',
            'EmailAMU'           => $_POST['email_amu']   ?? null,
            'Telephone'          => $_POST['telephone']   ?? '',
            'CodeDepartement'    => $_POST['departement'] ?? null,
            'Composante'         => $_POST['composante']  ?? null,
            'Discipline'         => $_POST['discipline']  ?? null,
            'Formation'          => $_POST['formation']   ?? null,
            'Pays'               => $_POST['pays']        ?? null,
            'Type'               => $_POST['type']        ?? null,
            'Zone'               => $_POST['zone']        ?? null,
            'Campus'             => null,
            'NiveauEtude'        => null,
            'MoyenneBac'         => null,
            'MoyenneSansBac'     => null,
            'DateDebut'          => null,
            'MobiliteAnterieure' => null,
        ];

        $errors = $this->validateFolderData($data, $lang);
        if (!empty($errors)) {
            $_SESSION['message'] = implode('<br>', $errors);
            $this->redirect('index.php?page=folders-student&lang=' . $lang);
        }

        $uploadErrors = $this->handleFileUploads($data, $lang);
        if (!empty($uploadErrors)) {
            $_SESSION['message'] = implode('<br>', $uploadErrors);
            $this->redirect('index.php?page=folders-student&lang=' . $lang);
        }

        $success = $this->folderUseCase->creerDossier($data);

        // Send a deposit confirmation email for each uploaded document
        if ($success && !empty($data['EmailPersonnel'])) {
            $studentName = trim(($data['Prenom'] ?? '') . ' ' . ($data['Nom'] ?? ''));
            $documents   = ['photo', 'cv', 'convention', 'lettre_motivation'];

            foreach ($documents as $docType) {
                if (isset($data[$docType]) && !empty($data[$docType])) {
                    \Service\Email\EmailReminderService::sendDocumentDeposited(
                        $data['EmailPersonnel'],
                        $studentName,
                        $docType,
                        $numetu
                    );
                }
            }
        }

        $_SESSION['message'] = $success
            ? ($lang === 'fr' ? 'Votre demande a été déposée avec succès.' : 'Application submitted successfully.')
            : ($lang === 'fr' ? 'Erreur lors du dépôt de la demande.'      : 'Error submitting application.');

        $this->redirect('index.php?page=folders-student&lang=' . $lang);
    }

    /**
     * Handles updating an existing student folder.
     * Blocks file uploads if the submission deadline has passed.
     * Sends a deposit notification email for each newly uploaded document.
     */
    private function handleUpdateFolder(string $numetu, string $lang): void
    {
        $existingFolder = $this->folderUseCase->getStudentDetails($numetu);
        $oldPieces      = is_array($existingFolder) && isset($existingFolder['pieces']) && is_array($existingFolder['pieces'])
            ? $existingFolder['pieces']
            : [];

        $data = [
            'NumEtu'          => $numetu,
            'Nom'             => $_POST['nom']         ?? null,
            'Prenom'          => $_POST['prenom']      ?? null,
            'DateNaissance'   => $_POST['naissance']   ?? null,
            'Sexe'            => $_POST['sexe']        ?? null,
            'Adresse'         => $_POST['adresse']     ?? null,
            'CodePostal'      => $_POST['cp']          ?? null,
            'Ville'           => $_POST['ville']       ?? null,
            'EmailPersonnel'  => $_POST['email_perso'] ?? null,
            'EmailAMU'        => $_POST['email_amu']   ?? null,
            'Telephone'       => $_POST['telephone']   ?? null,
            'CodeDepartement' => $_POST['departement'] ?? null,
            'Composante'      => $_POST['composante']  ?? null,
            'Discipline'      => $_POST['discipline']  ?? null,
            'Formation'       => $_POST['formation']   ?? null,
            'Pays'            => $_POST['pays']        ?? null,
            'Type'            => $_POST['type']        ?? null,
            'Zone'            => $_POST['zone']        ?? null,
        ];

        if (empty($data['EmailPersonnel'])) {
            $_SESSION['message'] = $lang === 'fr' ? "L'email personnel est requis." : "Personal email is required.";
            $this->redirect('index.php?page=folders-student&lang=' . $lang);
        }

        // Check whether the submission deadline has passed; if so, skip file uploads
        $dossierExistant = $this->folderUseCase->getStudentDetails($numetu);
        $dateLimiteRaw   = is_array($dossierExistant) ? ($dossierExistant['DateLimite'] ?? null) : null;
        $dateLimite      = is_string($dateLimiteRaw) && $dateLimiteRaw !== '' ? $dateLimiteRaw : null;

        if (!empty($dateLimite)) {
            $dateObj    = \DateTime::createFromFormat('Y-m-d', $dateLimite);
            $aujourdhui = new \DateTime('today');
            if ($dateObj && $dateObj < $aujourdhui) {
                $success = $this->folderUseCase->updateDossier($data);
                $_SESSION['message'] = $success
                    ? ($lang === 'fr' ? 'Folder mis à jour (fichiers refusés : date limite dépassée).' : 'Folder updated (files rejected: deadline passed).')
                    : ($lang === 'fr' ? 'Erreur lors de la mise à jour.' : 'Error updating folder.');
                $this->redirect('index.php?page=folders-student&lang=' . $lang);
            }
        }

        $uploadErrors = $this->handleFileUploads($data, $lang);
        if (!empty($uploadErrors)) {
            $_SESSION['message'] = implode('<br>', $uploadErrors);
            $this->redirect('index.php?page=folders-student&lang=' . $lang);
        }

        $success = $this->folderUseCase->updateDossier($data);

        // Send a deposit notification email for each newly uploaded document
        if ($success && !empty($data['EmailPersonnel'])) {
            error_log("📧 DEBUG: Checking documents for email notification...");
            error_log("📧 DEBUG: Email = " . $data['EmailPersonnel']);

            $studentName = '';
            if (is_array($existingFolder)) {
                $prenom      = strval($existingFolder['Prenom'] ?? '');
                $nom         = strval($existingFolder['Nom']    ?? '');
                $studentName = trim($prenom . ' ' . $nom);
            }

            $documents  = ['photo', 'cv', 'convention', 'lettre_motivation', 'langues_file'];
            $emailsSent = 0;

            foreach ($documents as $docType) {
                if (isset($data[$docType]) && !empty($data[$docType])) {
                    $result = \Service\Email\EmailReminderService::sendDocumentDeposited(
                        $data['EmailPersonnel'],
                        $studentName,
                        $docType,
                        $numetu
                    );

                    if ($result) {
                        $emailsSent++;
                        // Small delay between emails to avoid rate limiting
                        usleep(500000);
                    }
                }
            }
        }

        $_SESSION['message'] = $success
            ? ($lang === 'fr' ? 'Folder mis à jour avec succès.'         : 'Folder updated successfully.')
            : ($lang === 'fr' ? 'Erreur lors de la mise à jour du dossier.' : 'Error updating folder.');

        $this->redirect('index.php?page=folders-student&lang=' . $lang);
    }

    /**
     * Validates the required fields for folder creation.
     * Returns an array of error messages, empty if all fields are valid.
     *
     * @param  array<string, mixed> $data  Folder data to validate
     * @return array<int, string>          Validation error messages
     */
    private function validateFolderData(array $data, string $lang): array
    {
        $errors = [];

        if (empty($data['Nom']) || empty($data['Prenom'])) {
            $errors[] = $lang === 'fr' ? "Nom et Prénom requis." : "Name and Firstname required.";
        }
        if (empty($data['EmailPersonnel']) || !is_string($data['EmailPersonnel']) || !filter_var($data['EmailPersonnel'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = $lang === 'fr' ? "Email personnel valide requis." : "Valid personal email required.";
        }
        if (empty($data['Telephone'])) {
            $errors[] = $lang === 'fr' ? "Téléphone requis." : "Phone number required.";
        }
        if (empty($data['Type']) || empty($data['Zone'])) {
            $errors[] = $lang === 'fr' ? "Type et Zone requis." : "Type and Zone required.";
        }

        return $errors;
    }
}