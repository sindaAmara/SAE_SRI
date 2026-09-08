<?php

namespace Model\Entity;

/**
 * FolderStats
 *
 * Holds aggregated completion statistics for a set of student folders.
 * Provides the total folder count, the number of completed folders,
 * and a computed completion percentage.
 */
class FolderStats
{
    /** @var int Total number of folders */
    private int $total;

    /** @var int Number of completed folders */
    private int $completed;

    /**
     * @param int $total     Total number of folders
     * @param int $completed Number of completed folders
     */
    public function __construct(int $total, int $completed)
    {
        $this->total = $total;
        $this->completed = $completed;
    }

    /**
     * Returns the percentage of folders that have been completed.
     *
     * Returns 0 if there are no folders to avoid division by zero.
     *
     * @return float Completion percentage between 0 and 100
     */
    public function getCompletionPercentage(): float
    {
        if ($this->total === 0) {
            return 0;
        }

        return ($this->completed / $this->total) * 100;
    }

    /**
     * Returns the total number of folders.
     *
     * @return int
     */
    public function getTotal(): int
    {
        return $this->total;
    }

    /**
     * Returns the number of completed folders.
     *
     * @return int
     */
    public function getCompleted(): int
    {
        return $this->completed;
    }
}