<?php

namespace Model\UseCase;

use Model\Entity\Date;
use Model\Persistence\DateRepositoryPDO;

/**
 * ManageDateUseCase
 *
 * Business operations on important dates (deadlines shown to the admin).
 */
class ManageDateUseCase
{
    /** @var DateRepositoryPDO Repository of important dates */
    private DateRepositoryPDO $dateRepo;

    /**
     * @param DateRepositoryPDO|null $dateRepo Injected repository (defaults to a new instance)
     */
    public function __construct(?DateRepositoryPDO $dateRepo = null)
    {
        $this->dateRepo = $dateRepo ?? new DateRepositoryPDO();
    }

    /**
     * Creates an important date.
     *
     * @return bool True on success
     */
    public function createDate(string $id, string $titre, string $description, string $date, string $type): bool
    {
        return $this->dateRepo->create($id, $titre, $description, $date, $type);
    }

    /**
     * Deletes an important date.
     *
     * @return bool True on success
     */
    public function deleteDate(string $id): bool
    {
        return $this->dateRepo->delete($id);
    }

    /**
     * Updates an important date.
     *
     * @return bool True on success
     */
    public function updateDate(string $id, string $titre, string $description, string $date, string $type): bool
    {
        return $this->dateRepo->update($id, $titre, $description, $date, $type);
    }

    /**
     * Returns the important dates of a given day.
     *
     * @param string $date Day in YYYY-MM-DD format
     * @return array<int, Date>
     */
    public function showDatesFromDay(string $date): array
    {
        return $this->dateRepo->findByDate($date);
    }

    /**
     * Returns the day (YYYY-MM-DD) of an important date, or null if it does not exist.
     */
    public function getDateFromId(string $id): ?string
    {
        return $this->dateRepo->findById($id)?->getDate();
    }
}
