<?php

namespace Controllers;

use Model\Persistence\UserRepositoryPDO;
use Core\View;

/**
 * Handles all authentication-related pages:
 * login, registration, password reset, forced password change, and legal notices.
 */
class AuthController implements ControllerInterface
{
    private UserRepositoryPDO $userRepository;

    public function __construct()
    {
        $this->userRepository = new UserRepositoryPDO();

        if (!defined('ROOT_PATH')) {
            define('ROOT_PATH', dirname(__DIR__, 3));
        }
    }

    /**
     * Returns true if this controller handles the given page.
     */
    public static function support(string $page, string $method): bool
    {
        return in_array($page, [
            'login',
            'register',
            'reset-password',
            'force-reset-password',
            'mentions-legales',
            'forgot_password',
        ]);
    }

    /**
     * Main entry point. Starts the session and dispatches to the correct handler
     * based on the current page parameter.
     */
    public function control(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $page = $_GET['page'] ?? 'login';

        switch ($page) {
            case 'login':               $this->handleLogin();              break;
            case 'register':            $this->handleRegister();           break;
            case 'reset-password':      $this->handleResetPassword();      break;
            case 'force-reset-password':$this->handleForceResetPassword(); break;
            case 'mentions-legales':    $this->handleMentionsLegales();    break;
            case 'forgot_password':     $this->handleForgotPassword();     break;
        }
    }

    /**
     * Handles the login form submission.
     * On success, stores role and identity in session and redirects to the
     * appropriate home page. Forces a password change if the flag is set.
     */
    private function handleLogin(): void
    {
        $message = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $identifier = $_POST['identifier'] ?? '';
            $password   = $_POST['password']   ?? '';

            $result = $this->userRepository->login(
                is_string($identifier) ? $identifier : '',
                is_string($password)   ? $password   : ''
            );

            if ($result['success'] && isset($result['role'])) {
                $role = $result['role'];

                $_SESSION['role']            = $role;
                $_SESSION['user_identifier'] = $identifier;

                if ($role === 'student' && isset($result['numetu'])) {
                    $_SESSION['numetu'] = $result['numetu'];
                }
                if (isset($result['departement'])) {
                    $_SESSION['departement'] = $result['departement'];
                }
                if (isset($result['nom'])) {
                    $_SESSION['admin_nom']    = $result['nom'];
                }
                if (isset($result['prenom'])) {
                    $_SESSION['admin_prenom'] = $result['prenom'];
                }

                // Redirect to forced password change before going anywhere else
                if (!empty($result['force_change_password'])) {
                    header('Location: index.php?page=force-reset-password');
                    exit;
                }

                $destination = match($role) {
                    'super_admin'                                                  => 'index.php?page=super-admin',
                    'admin'                                                        => 'index.php?page=home-admin',
                    'coordinateur_etude', 'coordinateur_stage',
                    'chef_departement',   'coordinateur'                           => 'index.php?page=home-coordinateur',
                    default                                                        => 'index.php?page=home-student',
                };

                header('Location: ' . $destination);
                exit;
            }

            $message = 'Identifiants incorrects';
        }

        View::render('login', [
            'message'      => $message,
            'isLogin'      => true,
            'isReset'      => false,
            'isTokenReset' => false,
            'token'        => '',
        ]);
    }

    /**
     * Handles the "forgot password" page.
     * Sends a reset link via email if the address is known.
     * Always shows a neutral success message to avoid user enumeration.
     */
    private function handleForgotPassword(): void
    {
        $message     = '';
        $messageType = 'info';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim(strval($_POST['email'] ?? ''));

            if ($email === '') {
                $message     = 'Veuillez saisir votre adresse email.';
                $messageType = 'error';
            } else {
                $this->userRepository->resetPassword($email);
                // Neutral message regardless of whether the email exists (anti-enumeration)
                $message     = 'Si cette adresse est connue, un lien de réinitialisation a été envoyé.';
                $messageType = 'success';
            }
        }

        View::render('forgot_password', [
            'message'     => $message,
            'messageType' => $messageType,
        ]);
    }

    /**
     * Handles the forced password change screen shown after first login.
     * Requires a minimum password strength before unlocking the account.
     */
    private function handleForceResetPassword(): void
    {
        // Ensure the user has an active session before accessing this page
        if (empty($_SESSION['numetu']) && empty($_SESSION['user_identifier'])) {
            header('Location: index.php?page=login');
            exit;
        }

        $error   = '';
        $success = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $password        = $_POST['password']         ?? '';
            $passwordConfirm = $_POST['password_confirm'] ?? '';

            if ($password !== $passwordConfirm) {
                $error = "Les mots de passe ne correspondent pas.";
            } elseif (!preg_match('/^(?=.*[A-Z])(?=.*[^a-zA-Z0-9]).{12,}$/', $password)) {
                $error = "Le mot de passe doit contenir au moins 12 caractères, dont une majuscule et un caractère spécial.";
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $userIdentifier = !empty($_SESSION['numetu']) ? $_SESSION['numetu'] : $_SESSION['user_identifier'];
                $this->userRepository->updatePasswordAndUnlock($userIdentifier, $hashedPassword);

                $role        = $_SESSION['role'] ?? 'student';
                $destination = match($role) {
                    'super_admin'                                                  => 'index.php?page=super-admin',
                    'admin'                                                        => 'index.php?page=home-admin',
                    'coordinateur_etude', 'coordinateur_stage',
                    'chef_departement',   'coordinateur'                           => 'index.php?page=home-coordinateur',
                    default                                                        => 'index.php?page=home-student',
                };

                header('Location: ' . $destination);
                exit;
            }
        }

        View::render('force_reset_password', [
            'error'   => $error,
            'success' => $success,
        ]);
    }

    /**
     * Handles the registration page.
     * Currently only displays the form; actual registration logic is not yet implemented.
     */
    private function handleRegister(): void
    {
        $message = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $message = 'Inscription réussie !';
        }

        View::render('login', [
            'message'      => $message,
            'isLogin'      => false,
            'isReset'      => false,
            'isTokenReset' => false,
            'token'        => '',
        ]);
    }

    /**
     * Handles password reset, supporting two flows:
     * - Token-based reset (via email link): validates and updates the password.
     * - Email-based request: sends a reset email via the repository.
     */
    private function handleResetPassword(): void
    {
        $isTritanopia = !empty($_SESSION['tritanopia']);

        $message = '';
        $error   = '';
        $success = '';

        $isTokenReset = isset($_GET['token']);
        $token        = $_GET['token'] ?? '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($isTokenReset) {
                // Token flow: validate and apply the new password
                $password        = $_POST['password']         ?? '';
                $passwordConfirm = $_POST['password_confirm'] ?? '';

                if ($password !== $passwordConfirm) {
                    $error = "Les mots de passe ne correspondent pas.";
                } elseif (!preg_match('/^(?=.*[A-Z])(?=.*[^a-zA-Z0-9]).{12,}$/', $password)) {
                    $error = "Le mot de passe doit contenir au moins 12 caractères, dont une majuscule et un caractère spécial.";
                } else {
                    $success = 'Mot de passe réinitialisé avec succès !';
                }
            } else {
                // Email flow: request a reset link
                $email  = $_POST['email'] ?? '';
                $result = $this->userRepository->resetPassword(is_string($email) ? $email : '');

                $message = $result ? 'Email de réinitialisation envoyé !' : 'Email non trouvé';
            }
        }

        if ($isTokenReset) {
            View::render('reset_password', [
                'token'        => $token,
                'error'        => $error,
                'success'      => $success,
                'isTritanopia' => $isTritanopia,
            ]);
        } else {
            View::render('login', [
                'message'      => $message,
                'isLogin'      => false,
                'isReset'      => true,
                'isTokenReset' => false,
                'token'        => '',
            ]);
        }
    }

    /**
     * Renders the legal notices page with the current language context.
     */
    private function handleMentionsLegales(): void
    {
        $lang = $_GET['lang'] ?? $_SESSION['lang'] ?? 'fr';

        $t = function(array $frEn) use ($lang) {
            return $frEn[$lang] ?? $frEn['fr'] ?? '';
        };

        View::render('mentions_legales', [
            'lang'     => $lang,
            't'        => $t,
            'userRole' => $_SESSION['role'] ?? 'guest',  // ← ajout
        ]);
}
}