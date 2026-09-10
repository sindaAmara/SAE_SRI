<?php

namespace Service;

use Mailjet\Client;
use Mailjet\Resources;
use Model\Persistence\UserRepositoryPDO;

/**
 * Service for administrative tasks reserved for SuperAdmin.
 * Handles account creation, custom site/department management, and welcome emails.
 */
class SuperAdminService
{
    /** @var UserRepositoryPDO The user repository */
    private UserRepositoryPDO $userRepo;

    /**
     * @param UserRepositoryPDO $userRepo
     */
    public function __construct(UserRepositoryPDO $userRepo)
    {
        $this->userRepo = $userRepo;
    }

    /**
     * @return array<int, string> List of distinct department codes.
     */
    public function getAvailableDepartments(): array
    {
        return $this->userRepo->getDistinctDepartments();
    }

    /**
     * Adds a custom department to the list.
     * @param string $code
     */
    public function addDepartment(string $code): void
    {
        $this->userRepo->addCustomDepartment($code);
    }

    /**
     * @return array<int, string> List of available site names.
     */
    public function getAvailableSites(): array
    {
        return $this->userRepo->getDistinctSites();
    }

    /**
     * Adds a custom site to the list.
     * @param string $name
     */
    public function addSite(string $name): void
    {
        $this->userRepo->addCustomSite($name);
    }

    /**
     * Retrieves all admin/staff accounts.
     * @return array<int, array{login: string, role: string, departement: string|null, site: string|null, nom: string|null, prenom: string|null, created_at: string}>
     */
    public function getAllAccounts(): array
    {
        return $this->userRepo->findAll();
    }

    /**
     * Creates a staff account and sends a welcome email.
     * * @param string $email
     * @param string $password
     * @param string $role
     * @param string|null $departement
     * @param string|null $site
     * @param string|null $nom
     * @param string|null $prenom
     * @throws \RuntimeException if the account already exists.
     */
    public function createAccount(
        string $email,
        string $password,
        string $role,
        ?string $departement = null,
        ?string $site = null,
        ?string $nom = null,
        ?string $prenom = null
    ): void {
        if ($this->userRepo->findByLogin($email)) {
            throw new \RuntimeException("Ce compte existe déjà.");
        }
        $hashed = password_hash($password, PASSWORD_BCRYPT);
        $this->userRepo->create($email, $hashed, $role, $departement, $site, $nom, $prenom);
        $this->sendWelcomeEmail($email, $password, $role, $departement, $site, $nom, $prenom);
    }

    /**
     * @param string $email
     * @return bool
     */
    public function deleteAccount(string $email): bool
    {
        return $this->userRepo->deleteByLogin($email);
    }

    /**
     * Sends the welcome email using the native PHP mail() function.
     * * @param string $email
     * @param string $password Plaintext password for initial login.
     * @param string $role
     * @param string|null $departement
     * @param string|null $site
     * @param string|null $nom
     * @param string|null $prenom
     */
    private function sendWelcomeEmail(
        string $email,
        string $password,
        string $role,
        ?string $departement,
        ?string $site,
        ?string $nom = null,
        ?string $prenom = null
    ): bool {
        try {
            $apiKey    = $_ENV['MAILJET_API_KEY']    ?? getenv('MAILJET_API_KEY')    ?: '';
            $apiSecret = $_ENV['MAILJET_SECRET_KEY'] ?? getenv('MAILJET_SECRET_KEY') ?: '';

            if (empty($apiKey) || empty($apiSecret)) {
                error_log("❌ Mailjet credentials are not configured. Check your .env file for MAILJET_API_KEY and MAILJET_SECRET_KEY.");
                throw new \RuntimeException('Mailjet API credentials are not configured.');
            }

            $mj = new Client($apiKey, $apiSecret, true, ['version' => 'v3.1']);
            $mj->addRequestOption('verify', false);
            $mj->addRequestOption('timeout', 10);
            $mj->addRequestOption('connect_timeout', 10);

            $fullName = trim(($prenom ?? '') . ' ' . ($nom ?? ''));
            $greeting = $fullName !== '' ? "Bonjour $fullName," : "Bonjour,";

            $extra = '';
            if ($departement) {
                $extra .= " (Département : $departement)";
            }
            if ($site) {
                $extra .= " (Site : $site)";
            }

            $subject = "Votre accès à la plateforme AMU Relations Internationales";
            $body    = "$greeting\n\nVotre compte a été créé.\n"
                . "Login : $email\nMot de passe : $password\nRôle : $role$extra\n\n"
                . "Connectez-vous sur : https://votre-site.fr\n\nCordialement,\nL'équipe AMU";

            $body = [
                'Messages' => [
                    [
                        'From' => [
                            'Email' => 'relance-iut-amu@ri-amu.app',
                            'Name'  => 'IUT Aix - Gestion Dossiers'
                        ],
                        'To' => [
                            [
                                'Email' => $email,
                                'Name'  => $fullName ?: ''
                            ]
                        ],
                        'Subject'  => $subject,
                        'HTMLPart' => $body,
                        'TextPart' => strip_tags($body)
                    ]
                ]
            ];

            $response = $mj->post(Resources::$Email, ['body' => $body]);

            if ($response->success()) {
                error_log("✅ Email successfully sent to {$email} via Mailjet");
                return true;
            } else {
                error_log("❌ Mailjet error: " . json_encode($response->getData()));
                return false;
            }
        } catch (\Exception $e) {
            error_log("❌ Mailjet exception for {$email}: " . $e->getMessage());
            return false;
        }
    }
}
