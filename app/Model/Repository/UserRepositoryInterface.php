<?php

namespace Model\Repository;

use Model\Entity\User;

/**
 * Interface UserRepositoryInterface
 * * Defines the contract for user account management, authentication, and security.
 */
interface UserRepositoryInterface
{
    /**
     * Finds a user by their email address.
     * * @param string $email
     * @return User|null The User entity or null if not found.
     */
    public function findByEmail(string $email): ?User;

    /**
     * Finds a student user by their student number.
     * * @param string $numetu Student number.
     * @return User|null The User entity or null if not found.
     */
    public function findByStudentNumber(string $numetu): ?User;

    /**
     * Persists a User entity to the storage.
     * * @param User $user
     * @return bool True on success.
     */
    public function save(User $user): bool;

    /**
     * Updates the password for a specific user.
     * * @param string $email The user's email.
     * @param string $hashedPassword The new pre-hashed password.
     * @return bool True on success.
     */
    public function updatePassword(string $email, string $hashedPassword): bool;

    /**
     * Handles the login logic for a user.
     * * @param string $identifier Email or student number.
     * @param string $password The plain-text password to verify.
     * @return array<string, mixed> Authentication results including success status and user metadata.
     */
    public function login(string $identifier, string $password): array;

    /**
     * Registers a new user account.
     * * @param User $user
     * @return bool True on success.
     */
    public function register(User $user): bool;

    /**
     * Resets a user's password.
     * * @param string $email
     * @return bool True if the reset was successful.
     */
    public function resetPassword(string $email): bool;
}