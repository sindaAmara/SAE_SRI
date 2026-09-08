<?php

namespace Model\Persistence;

use Model\Repository\UserRepositoryInterface;
use Model\Entity\User;
use Database;
use PDO;
use PDOException;

/**
 * Class UserRepositoryPDO
 * * Handles user authentication, registration, and management for both Admins and Students.
 */
class UserRepositoryPDO implements UserRepositoryInterface
{
    /** @var PDO Database connection */
    private PDO $pdo;

    /**
     * UserRepositoryPDO constructor.
     */
    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    /**
     * Finds a user (admin or student) by their email.
     * @param string $email
     * @return User|null
     */
    public function findByEmail(string $email): ?User
    {
        $stmt = $this->pdo->prepare("SELECT * FROM admins WHERE email = :email");
        $stmt->execute([':email' => $email]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if (is_array($data)) {
            return $this->mapToUser($data, is_string($data['role'] ?? null) ? $data['role'] : 'admin');
        }

        $stmt = $this->pdo->prepare("SELECT * FROM etudiants WHERE email = :email");
        $stmt->execute([':email' => $email]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if (is_array($data)) {
            return $this->mapToUser($data, 'student');
        }

        return null;
    }

    /**
     * Finds a student user by their student number.
     * @param string $numetu
     * @return User|null
     */
    public function findByStudentNumber(string $numetu): ?User
    {
        $stmt = $this->pdo->prepare("SELECT *, 'student' as role FROM etudiants WHERE numetu = :numetu");
        $stmt->execute(['numetu' => $numetu]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return is_array($data) ? $this->mapToUser($data, 'student') : null;
    }

    /**
     * Saves a user entity to the appropriate table.
     * @param User $user
     * @return bool
     */
    public function save(User $user): bool
    {
        try {
            if ($user->getRole() === 'admin') {
                $stmt = $this->pdo->prepare("INSERT INTO admins (email, password, role) VALUES (:email, :password, :role)");
                return $stmt->execute(['email' => $user->getEmail(), 'password' => $user->getPassword(), 'role' => $user->getRole()]);
            } else {
                $stmt = $this->pdo->prepare("INSERT INTO etudiants (email, password, numetu) VALUES (:email, :password, :numetu)");
                return $stmt->execute(['email' => $user->getEmail(), 'password' => $user->getPassword(), 'numetu' => $user->getNumetu()]);
            }
        } catch (PDOException $e) {
            error_log("save Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Updates user password across all relevant tables.
     * @param string $email
     * @param string $hashedPassword
     * @return bool
     */
    public function updatePassword(string $email, string $hashedPassword): bool
    {
        try {
            $stmt = $this->pdo->prepare("UPDATE admins SET password = :password WHERE email = :email");
            $stmt->execute(['password' => $hashedPassword, 'email' => $email]);
            if ($stmt->rowCount() > 0) return true;

            $stmt = $this->pdo->prepare("UPDATE etudiants SET password = :password WHERE email = :email");
            return $stmt->execute(['password' => $hashedPassword, 'email' => $email]);
        } catch (PDOException $e) {
            error_log("updatePassword Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Updates password and resets the force_change_password flag.
     * @param string $identifier Email or student number.
     * @param string $hashedPassword
     * @return bool
     */
    public function updatePasswordAndUnlock(string $identifier, string $hashedPassword): bool
    {
        try {
            $stmt1 = $this->pdo->prepare("UPDATE admins SET password = :password, force_change_password = 0 WHERE email = :id");
            $stmt1->execute([':password' => $hashedPassword, ':id' => $identifier]);
            
            $stmt2 = $this->pdo->prepare("UPDATE etudiants SET password = :password, force_change_password = 0 WHERE numetu = :id OR email = :id");
            $stmt2->execute([':password' => $hashedPassword, ':id' => $identifier]);

            return true;
        } catch (PDOException $e) {
            error_log("updatePasswordAndUnlock Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Authenticates a user and retrieves session metadata.
     * @param string $identifier
     * @param string $password
     * @return array{success: bool, role?: string, numetu?: string|null, departement?: string|null, nom?: string|null, prenom?: string|null, force_change_password?: bool}
     */
    public function login(string $identifier, string $password): array
    {
        $user = filter_var($identifier, FILTER_VALIDATE_EMAIL)
            ? $this->findByEmail($identifier)
            : $this->findByStudentNumber($identifier);

        if ($user && password_verify($password, $user->getPassword())) {

            if ($user->getRole() !== 'student') {
                try {
                    $upd = $this->pdo->prepare(
                        "UPDATE admins SET last_login = NOW() WHERE email = :email"
                    );
                    $upd->execute([':email' => $identifier]);
                } catch (PDOException $e) {}
            }

            $forceChange = false;
            try {
                if ($user->getRole() === 'student') {
                    $stmtCheck = $this->pdo->prepare("SELECT force_change_password FROM etudiants WHERE numetu = :id LIMIT 1");
                    $stmtCheck->execute([':id' => $user->getNumetu()]);
                } else {
                    $stmtCheck = $this->pdo->prepare("SELECT force_change_password FROM admins WHERE email = :id LIMIT 1");
                    $stmtCheck->execute([':id' => $identifier]);
                }

                $forceVal = $stmtCheck->fetchColumn();
                if ($forceVal !== false && (int) $forceVal === 1) {
                    $forceChange = true;
                }
            } catch (PDOException $e) {
                error_log("Force Change Check Error: " . $e->getMessage());
            }

            return [
                'success'               => true,
                'role'                  => $user->getRole(),
                'numetu'                => $user->getNumetu(),
                'departement'           => $user->getDepartement(),
                'nom'                   => method_exists($user, 'getNom') ? $user->getNom() : null,
                'prenom'                => method_exists($user, 'getPrenom') ? $user->getPrenom() : null,
                'force_change_password' => $forceChange,
            ];
        }

        return ['success' => false];
    }

    /**
     * Registers a new user.
     * @param User $user
     * @return bool
     */
    public function register(User $user): bool
    {
        $existing = $user->getNumetu()
            ? $this->findByStudentNumber($user->getNumetu())
            : $this->findByEmail($user->getEmail());
        if ($existing) return false;
        $user->setPassword(password_hash($user->getPassword(), PASSWORD_DEFAULT));
        return $this->save($user);
    }

    /**
     * Resets a user password with a random string.
     * @param string $email
     * @return bool
     */
    public function resetPassword(string $email): bool
    {
        $user = $this->findByEmail($email);
        if (!$user) return false;
        $newPassword = bin2hex(random_bytes(4));
        $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
        $this->updatePassword($email, $hashed);
        return true;
    }

    /**
     * Maps database array data to a User entity.
     * @param array<string, mixed> $data
     * @param string $role
     * @return User
     */
    private function mapToUser(array $data, string $role): User
    {
        $id          = (isset($data['id'])          && is_numeric($data['id']))         ? (int)$data['id']              : null;
        $email       = (isset($data['email'])       && is_scalar($data['email']))       ? (string)$data['email']        : '';
        $numetu      = (isset($data['numetu'])      && is_scalar($data['numetu']))      ? (string)$data['numetu']       : null;
        $password    = (isset($data['password'])    && is_scalar($data['password']))    ? (string)$data['password']     : '';
        $departement = (isset($data['departement']) && is_scalar($data['departement'])) ? (string)$data['departement'] : null;

        $user = new User($id, $email, $numetu, $password, $role);
        $user->setDepartement($departement);
        return $user;
    }

    /**
     * Find admin data by email.
     * @param string $email
     * @return array<string, mixed>|null
     */
    public function findByLogin(string $email): ?array
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM admins WHERE email = :email LIMIT 1");
            $stmt->execute([':email' => $email]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return is_array($result) ? $result : null;
        } catch (PDOException $e) {
            error_log("findByLogin Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Checks if an admin login exists.
     */
    public function loginExists(string $email): bool
    {
        return $this->findByLogin($email) !== null;
    }

    /**
     * Creates a new admin user.
     * @param string $email
     * @param string $hashedPassword
     * @param string $role
     * @param string|null $departement
     * @param string|null $site
     * @param string|null $nom
     * @param string|null $prenom
     * @return bool
     */
    public function create(
        string $email,
        string $hashedPassword,
        string $role,
        ?string $departement = null,
        ?string $site = null,
        ?string $nom = null,
        ?string $prenom = null
    ): bool {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO admins (email, password, role, departement, site, nom, prenom, created_at)
                VALUES (:email, :password, :role, :departement, :site, :nom, :prenom, NOW())
            ");
            return $stmt->execute([
                ':email'       => $email,
                ':password'    => $hashedPassword,
                ':role'        => $role,
                ':departement' => $departement,
                ':site'        => $site,
                ':nom'         => $nom,
                ':prenom'      => $prenom,
            ]);
        } catch (PDOException $e) {
            error_log("create Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Deletes an admin user by email (preventing super_admin deletion).
     * @param string $email
     * @return bool
     */
    public function deleteByLogin(string $email): bool
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM admins WHERE email = :email AND role != 'super_admin'");
            return $stmt->execute([':email' => $email]);
        } catch (PDOException $e) {
            error_log("deleteByLogin Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Retrieves all non-super-admin staff users.
     * @return array<int, array{login: string, role: string, departement: string|null, site: string|null, nom: string|null, prenom: string|null, created_at: string}>
     */
    public function findAll(): array
    {
        try {
            $stmt = $this->pdo->query("
                SELECT email AS login, role, departement, site, nom, prenom, created_at
                FROM admins WHERE role != 'super_admin' ORDER BY created_at DESC
            ");
            if ($stmt === false) return [];
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return array_map(fn($r) => [
                'login'       => is_string($r['login']       ?? null) ? $r['login']       : '',
                'role'        => is_string($r['role']        ?? null) ? $r['role']        : '',
                'departement' => is_string($r['departement'] ?? null) ? $r['departement'] : null,
                'site'        => is_string($r['site']        ?? null) ? $r['site']        : null,
                'nom'         => is_string($r['nom']         ?? null) ? $r['nom']         : null,
                'prenom'      => is_string($r['prenom']      ?? null) ? $r['prenom']      : null,
                'created_at'  => is_string($r['created_at'] ?? null) ? $r['created_at']  : '',
            ], $results);
        } catch (PDOException $e) {
            error_log("findAll Error: " . $e->getMessage());
            return [];
        }
    }

    /** * @deprecated Use findAll() 
     * @return array<int, array{login: string, role: string, departement: string|null, site: string|null, nom: string|null, prenom: string|null, created_at: string}>
     */
    public function getAllNonSuperAdmin(): array { return $this->findAll(); }

    /** * @deprecated Use findAll() 
     * @return array<int, array{login: string, role: string, departement: string|null, site: string|null, nom: string|null, prenom: string|null, created_at: string}>
     */
    public function getAllAdmins(): array { return $this->findAll(); }

    /** @deprecated Use create() */
    public function createAdmin(string $email, string $hashedPassword, string $role): bool { return $this->create($email, $hashedPassword, $role); }

    /** @deprecated Use deleteByLogin() */
    public function deleteAdminByEmail(string $email): bool { return $this->deleteByLogin($email); }

    /** @deprecated Use create() */
    public function createUser(string $login, string $hashedPassword, string $role): bool { return $this->create($login, $hashedPassword, $role); }

    /**
     * Gets a list of unique departments from dossiers and custom settings.
     * @return array<int, string>
     */
    public function getDistinctDepartments(): array
    {
        $depts = [];
        try {
            $stmt = $this->pdo->query("SELECT DISTINCT CodeDepartement FROM dossiers WHERE CodeDepartement IS NOT NULL AND CodeDepartement != '' ORDER BY CodeDepartement ASC");
            if ($stmt) {
                $rows  = $stmt->fetchAll(PDO::FETCH_COLUMN);
                $depts = array_values(array_filter($rows, 'is_string'));
            }
        } catch (PDOException $e) { error_log("getDistinctDepartments Error: " . $e->getMessage()); }

        try {
            $stmt = $this->pdo->query("SELECT code FROM custom_departments ORDER BY code ASC");
            if ($stmt) {
                $custom = $stmt->fetchAll(PDO::FETCH_COLUMN);
                if (is_array($custom)) $depts = array_values(array_unique(array_merge($depts, $custom)));
            }
        } catch (PDOException $e) {}

        sort($depts);
        return $depts;
    }

    /**
     * Manually adds a department to the custom list.
     */
    public function addCustomDepartment(string $code): void
    {
        try {
            $this->pdo->exec("CREATE TABLE IF NOT EXISTS custom_departments (id INT AUTO_INCREMENT PRIMARY KEY, code VARCHAR(50) NOT NULL UNIQUE)");
            $stmt = $this->pdo->prepare("INSERT IGNORE INTO custom_departments (code) VALUES (:code)");
            $stmt->execute([':code' => $code]);
        } catch (PDOException $e) { error_log("addCustomDepartment Error: " . $e->getMessage()); }
    }

    /**
     * Retrieves all available sites (geographic locations).
     * @return array<int, string>
     */
    public function getDistinctSites(): array
    {
        $defaults = ['Site Gaston Berger'];
        $custom   = [];
        try {
            $stmt = $this->pdo->query("SELECT name FROM custom_sites ORDER BY name ASC");
            if ($stmt) {
                $rows   = $stmt->fetchAll(PDO::FETCH_COLUMN);
                $custom = array_values(array_filter($rows, 'is_string'));
            }
        } catch (PDOException $e) {}

        $all = array_values(array_unique(array_merge($defaults, $custom)));
        sort($all);
        return $all;
    }

    /**
     * Manually adds a site to the custom list.
     */
    public function addCustomSite(string $name): void
    {
        try {
            $this->pdo->exec("CREATE TABLE IF NOT EXISTS custom_sites (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL UNIQUE)");
            $stmt = $this->pdo->prepare("INSERT IGNORE INTO custom_sites (name) VALUES (:name)");
            $stmt->execute([':name' => $name]);
        } catch (PDOException $e) { error_log("addCustomSite Error: " . $e->getMessage()); }
    }
}