<?php

namespace Controllers\HomeController;

use Controllers\ControllerInterface;
use Service\SuperAdminService;
use Model\Persistence\UserRepositoryPDO;
use Core\View;

/**
 * Controller responsible for the Super Admin dashboard.
 * Handles management of users, departments, and sites.
 * Ensures authentication, language selection, accessibility,
 * and executes actions like creating/deleting accounts and adding departments/sites.
 */
class SuperAdminController implements ControllerInterface
{
    /**
     * Checks if this controller supports the given page.
     */
    public static function support(string $page, string $method): bool
    {
        return $page === 'super-admin';
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
     * Instantiates the SuperAdmin service.
     * Can be overridden for testing.
     */
    protected function makeService(): SuperAdminService
    {
        return new SuperAdminService(new UserRepositoryPDO());
    }

    /**
     * Main controller logic for the Super Admin dashboard.
     * Handles authentication, language and accessibility options,
     * account creation/deletion, and department/site management.
     */
    public function control(): void
    {
        $this->startSession();

        // Ensure the user is a Super Admin
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'super_admin') {
            $this->redirect('index.php?page=login');
        }

        // Handle language selection
        if (isset($_GET['lang']) && in_array($_GET['lang'], ['fr', 'en'], true)) {
            $_SESSION['lang'] = $_GET['lang'];
        }
        $lang = $_SESSION['lang'] ?? 'fr';

        // Handle accessibility option (tritanopia mode)
        if (isset($_GET['tritanopia'])) {
            $_SESSION['tritanopia'] = $_GET['tritanopia'] === '1';
        }
        $isTritanopia = $_SESSION['tritanopia'] ?? false;

        $service = $this->makeService();
        $success = null;
        $error   = null;

        $departments = $service->getAvailableDepartments();
        $sites       = $service->getAvailableSites();

        // ── Handle adding a department ─────────────────────────────
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_department') {
            $newDept = strtoupper(trim((string) ($_POST['new_department'] ?? '')));
            if ($newDept !== '' && !in_array($newDept, $departments, true)) {
                $service->addDepartment($newDept);
                $departments[] = $newDept;
                sort($departments);
                $success = $lang === 'fr' ? "Département « $newDept » ajouté." : "Department « $newDept » added.";
            } else {
                $error = $lang === 'fr' ? 'Département invalide ou déjà existant.' : 'Invalid or already existing department.';
            }
        }

        // ── Handle adding a site ───────────────────────────────────
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_site') {
            $newSite = trim((string) ($_POST['new_site'] ?? ''));
            if ($newSite !== '' && !in_array($newSite, $sites, true)) {
                $service->addSite($newSite);
                $sites[] = $newSite;
                sort($sites);
                $success = $lang === 'fr' ? "Site « $newSite » ajouté." : "Site « $newSite » added.";
            } else {
                $error = $lang === 'fr' ? 'Site invalide ou déjà existant.' : 'Invalid or already existing site.';
            }
        }

        // ── Handle account deletion ───────────────────────────────
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
            $loginToDelete = trim((string) ($_POST['login'] ?? ''));
            if ($loginToDelete !== '') {
                $result  = $service->deleteAccount($loginToDelete);
                $success = $result
                    ? ($lang === 'fr' ? 'Compte supprimé avec succès.' : 'Account deleted successfully.')
                    : ($lang === 'fr' ? 'Erreur lors de la suppression.' : 'Error while deleting account.');
            }
        }

        // ── Handle account creation ───────────────────────────────
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
            $login       = trim((string) ($_POST['login']       ?? ''));
            $password    = trim((string) ($_POST['password']    ?? ''));
            $role        = trim((string) ($_POST['role']        ?? ''));
            $departement = trim((string) ($_POST['departement'] ?? ''));
            $site        = trim((string) ($_POST['site']        ?? ''));
            $nom         = trim((string) ($_POST['nom']         ?? ''));
            $prenom      = trim((string) ($_POST['prenom']      ?? ''));

            $coordRoles = ['coordinateur', 'coordinateur_etude', 'coordinateur_stage', 'chef_departement'];
            $allRoles   = array_merge(['admin'], $coordRoles);
            $isCoord    = in_array($role, $coordRoles, true);

            if ($login === '' || $password === '' || !in_array($role, $allRoles, true)) {
                $error = $lang === 'fr'
                    ? 'Veuillez remplir tous les champs correctement.'
                    : 'Please fill in all fields correctly.';

            } elseif (!preg_match('/^(?=.*[A-Z])(?=.*[^a-zA-Z0-9]).{12,}$/', $password)) {
                $error = $lang === 'fr'
                    ? 'Le mot de passe doit contenir au moins 12 caractères, dont une majuscule et un caractère spécial.'
                    : 'Password must contain at least 12 characters, including one uppercase letter and one special character.';

            } elseif ($isCoord && $departement === '') {
                $error = $lang === 'fr'
                    ? 'Veuillez sélectionner un département pour le coordinateur.'
                    : 'Please select a department for the coordinator.';

            } elseif ($role === 'admin' && $site === '') {
                $error = $lang === 'fr'
                    ? 'Veuillez sélectionner un site pour le secrétaire.'
                    : 'Please select a site for the secretary.';

            } elseif (!filter_var($login, FILTER_VALIDATE_EMAIL)) {
                $error = $lang === 'fr'
                    ? 'Le login doit être une adresse email valide.'
                    : 'Login must be a valid email address.';

            } else {
                try {
                    $service->createAccount(
                        $login,
                        $password,
                        $role,
                        $isCoord          ? $departement : null,
                        $role === 'admin' ? $site        : null,
                        $nom    !== '' ? $nom    : null,
                        $prenom !== '' ? $prenom : null
                    );
                    $success = $lang === 'fr'
                        ? "Compte créé avec succès. Un email a été envoyé à $login."
                        : "Account created successfully. An email was sent to $login.";
                } catch (\RuntimeException $e) {
                    $error = $e->getMessage();
                }
            }
        }

        // Translation helper
        $t = function (array $translations) use ($lang): string {
            return $lang === 'en' ? ($translations['en'] ?? '') : ($translations['fr'] ?? '');
        };

        // URL builder helper
        $buildUrl = function (string $path, array $params = []) use ($lang): string {
            $params['lang'] = $lang;
            $separator = (strpos($path, '?') === false) ? '?' : '&';
            return $path . $separator . http_build_query($params);
        };

        $accounts = $service->getAllAccounts();

        // Render the Super Admin dashboard
        $this->renderView('HomePage/super_admin', [
            'lang'        => $lang,
            't'           => $t,
            'buildUrl'    => $buildUrl,
            'accounts'    => $accounts,
            'success'     => $success,
            'error'       => $error,
            'tritanopia'  => $isTritanopia,
            'departments' => $departments,
            'sites'       => $sites,
        ]);
    }
}