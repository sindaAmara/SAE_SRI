<?php

namespace Model\Repository;

/**
 * Interface RelanceRepositoryInterface
 * * Defines the contract for tracking student reminder emails (relances).
 */
interface RelanceRepositoryInterface
{
    /**
     * Checks if a reminder was recently sent to a specific student.
     * * @param string $numEtu Student number.
     * @param int $days The number of days to look back for recent activity.
     * @return bool True if a reminder was sent within the specified timeframe.
     */
    public function wasRecentlySent(string $numEtu, int $days): bool;

    /**
     * Saves a record of a sent reminder.
     * * @param string $numEtu Student number.
     * @param string $message The content or summary of the reminder message.
     * @return void
     */
    public function save(string $numEtu, string $message): void;
}