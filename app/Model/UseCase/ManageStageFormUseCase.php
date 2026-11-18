<?php

namespace Model\UseCase;

use Model\Persistence\StageFormRepositoryPDO;

/**
 * ManageStageFormUseCase
 *
 * Business logic for the end-of-internship form:
 * submission and admin consultation of responses.
 */
class ManageStageFormUseCase
{
    private StageFormRepositoryPDO $repository;

    public function __construct(?StageFormRepositoryPDO $repository = null)
    {
        $this->repository = $repository ?? new StageFormRepositoryPDO();
    }

    /**
     * Saves a student's form response.
     *
     * @param array<string, string> $data
     */
    public function saveResponse(string $numetu, array $data): void
    {
        $this->repository->save($numetu, $data);
    }

    /**
     * Returns the existing student response
     *
     * @return array<string, mixed>|null
     */
    public function getExistingResponse(string $numetu): ?array
    {
        return $this->repository->findByNumEtu($numetu);
    }

    /**
     * Returns all submitted responses for admin display.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAllResponses(): array
    {
        return $this->repository->findAll();
    }
}