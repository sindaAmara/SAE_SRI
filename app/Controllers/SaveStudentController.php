<?php

// phpcs:disable Generic.Files.LineLength

namespace Controllers;

use Model\UseCase\ManageFolderUseCase;

/**
 * Controller responsible for creating a new student folder via POST requests.
 * Handles validation, file uploads, and communicates success/error messages via session.
 */
class SaveStudentController
{
    /**
     * Determines if this controller supports the given page and HTTP method.
     */
    public static function support(string $page, string $method): bool
    {
        return $page === 'save_student' && $method === 'POST';
    }

    /**
     * Main control method that handles the POST request to create a student folder.
     */
    public function control(): void
    {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $lang = $_GET['lang'] ?? 'fr';

        // Collect form input data
        $data = [
            'NumEtu'         => (string)($_POST['numetu']     ?? ''),
            'Nom'            => (string)($_POST['nom']        ?? ''),
            'Prenom'         => (string)($_POST['prenom']     ?? ''),
            'EmailPersonnel' => (string)($_POST['email_perso'] ?? ''),
            'Telephone'      => (string)($_POST['telephone']  ?? ''),
            'Type'           => (string)($_POST['type']       ?? ''),
        ];

        // Validate required fields
        $errors = [];
        if ($data['NumEtu']         === '') $errors[] = $lang === 'fr' ? 'Le numéro étudiant est requis' : 'Student ID is required';
        if ($data['Nom']            === '') $errors[] = $lang === 'fr' ? 'Le nom est requis'             : 'Last name is required';
        if ($data['Prenom']         === '') $errors[] = $lang === 'fr' ? 'Le prénom est requis'          : 'First name is required';
        if ($data['EmailPersonnel'] === '') $errors[] = $lang === 'fr' ? "L'email est requis"            : 'Email is required';
        if ($data['Telephone']      === '') $errors[] = $lang === 'fr' ? 'Le téléphone est requis'       : 'Phone is required';

        // Redirect if there are validation errors
        if (!empty($errors)) {
            $_SESSION['message'] = implode(', ', $errors);
            header('Location: index.php?page=folders&action=create&lang=' . $lang);
            exit;
        }

        $useCase  = new ManageFolderUseCase();
        $existing = $useCase->getByNumetu($data['NumEtu']);

        // Check if student already exists
        if ($existing !== null) {
            $_SESSION['message'] = $lang === 'fr'
                ? 'Un étudiant avec ce numéro existe déjà'
                : 'A student with this ID already exists';
            header('Location: index.php?page=folders&action=create&lang=' . $lang);
            exit;
        }

        // Merge uploaded files into $data for creation
        if (isset($_FILES['photo']) && is_array($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $content = file_get_contents((string)$_FILES['photo']['tmp_name']);
            if ($content !== false) $data['photo'] = $content;
        }

        if (isset($_FILES['cv']) && is_array($_FILES['cv']) && $_FILES['cv']['error'] === UPLOAD_ERR_OK) {
            $content = file_get_contents((string)$_FILES['cv']['tmp_name']);
            if ($content !== false) $data['cv'] = $content;
        }

        // Create the student folder
        $success = $useCase->creerDossier($data);

        // Set success or error message
        $_SESSION['message'] = $success
            ? ($lang === 'fr' ? 'Folder créé avec succès'           : 'Folder created successfully')
            : ($lang === 'fr' ? 'Erreur lors de la création du dossier' : 'Error creating folder');

        // Redirect back to folders page
        header('Location: index.php?page=folders&lang=' . $lang);
        exit;
    }
}