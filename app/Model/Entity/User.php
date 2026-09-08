<?php

namespace Model\Entity;

/**
 * User
 *
 * Represents an application user, which can be either a student or an administrator.
 * Stores authentication credentials (email, hashed password), the user's role,
 * and optional student-specific data (student number, department).
 */
class User
{
    /** @var int|null Database identifier, null before persistence */
    private ?int $id;

    /** @var string Email address used for authentication */
    private string $email;

    /** @var string|null Student number (numéro étudiant), null for non-student users */
    private ?string $numetu;

    /** @var string Hashed password */
    private string $password;

    /** @var string Role of the user (e.g. 'admin', 'student') */
    private string $role;

    /** @var string|null Department the user belongs to, null if not applicable */
    private ?string $departement = null;

    /**
     * @param int|null    $id       Database identifier (null for new users)
     * @param string      $email    Email address used for authentication
     * @param string|null $numetu   Student number (null for non-student users)
     * @param string      $password Hashed password
     * @param string      $role     Role of the user (e.g. 'admin', 'student')
     */
    public function __construct(?int $id, string $email, ?string $numetu, string $password, string $role)
    {
        $this->id       = $id;
        $this->email    = $email;
        $this->numetu   = $numetu;
        $this->password = $password;
        $this->role     = $role;
    }

    /**
     * Returns the database identifier of the user.
     *
     * @return int|null
     */
    public function getId(): ?int { return $this->id; }

    /**
     * Returns the email address of the user.
     *
     * @return string
     */
    public function getEmail(): string { return $this->email; }

    /**
     * Returns the student number, or null if the user is not a student.
     *
     * @return string|null
     */
    public function getNumetu(): ?string { return $this->numetu; }

    /**
     * Returns the hashed password of the user.
     *
     * @return string
     */
    public function getPassword(): string { return $this->password; }

    /**
     * Returns the role of the user.
     *
     * @return string
     */
    public function getRole(): string { return $this->role; }

    /**
     * Returns the department the user belongs to, or null if not set.
     *
     * @return string|null
     */
    public function getDepartement(): ?string { return $this->departement; }

    /**
     * Sets the database identifier of the user.
     *
     * @param int|null $id
     * @return void
     */
    public function setId(?int $id): void { $this->id = $id; }

    /**
     * Sets the email address of the user.
     *
     * @param string $email
     * @return void
     */
    public function setEmail(string $email): void { $this->email = $email; }

    /**
     * Sets the student number of the user.
     *
     * @param string|null $numetu
     * @return void
     */
    public function setNumetu(?string $numetu): void { $this->numetu = $numetu; }

    /**
     * Sets the hashed password of the user.
     *
     * @param string $password
     * @return void
     */
    public function setPassword(string $password): void { $this->password = $password; }

    /**
     * Sets the role of the user.
     *
     * @param string $role
     * @return void
     */
    public function setRole(string $role): void { $this->role = $role; }

    /**
     * Sets the department the user belongs to.
     *
     * @param string|null $departement
     * @return void
     */
    public function setDepartement(?string $departement): void { $this->departement = $departement; }
}