<?php

namespace Model\UseCase;

use Model\Repository\UserRepositoryInterface;

/**
 * LoginUserUseCase
 *
 * Use case responsible for authenticating a user.
 *
 * Determines whether the identifier is an email address or a student number
 * and delegates authentication to the appropriate repository:
 * admins authenticate via email, students via their student number.
 */
class LoginUserUseCase
{
    /** @var UserRepositoryInterface Repository used to authenticate admin users */
    private UserRepositoryInterface $adminRepo;

    /** @var UserRepositoryInterface Repository used to authenticate student users */
    private UserRepositoryInterface $studentRepo;

    /**
     * @param UserRepositoryInterface $adminRepo   Repository used to authenticate admin users
     * @param UserRepositoryInterface $studentRepo Repository used to authenticate student users
     */
    public function __construct(UserRepositoryInterface $adminRepo, UserRepositoryInterface $studentRepo)
    {
        $this->adminRepo = $adminRepo;
        $this->studentRepo = $studentRepo;
    }

    /**
     * Authenticates a user based on their identifier and password.
     *
     * If the identifier is a valid email address, authentication is delegated
     * to the admin repository; otherwise it is delegated to the student repository.
     *
     * @param string $identifier Email address (admin) or student number (student)
     * @param string $password   Plain-text password to verify
     * @return array<string, mixed> Authentication result data returned by the repository
     */
    public function execute(string $identifier, string $password): array
    {
        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            return $this->adminRepo->login($identifier, $password);
        } else {
            return $this->studentRepo->login($identifier, $password);
        }
    }
}