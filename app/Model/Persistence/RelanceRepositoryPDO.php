<?php

namespace Model\Persistence;

use Model\Repository\RelanceRepositoryInterface;
use Database;
use PDO;

/**
 * Class RelanceRepositoryPDO
 * * Tracks reminder emails (relances) sent to students.
 */
class RelanceRepositoryPDO implements RelanceRepositoryInterface
{
    /** @var PDO Database connection */
    private PDO $pdo;

    /**
     * RelanceRepositoryPDO constructor.
     */
    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    /**
     * Checks if a reminder was recently sent to a specific student within a given timeframe.
     *
     * @param string $numEtu Student number.
     * @param int $days Number of days to look back.
     * @return bool True if a reminder exists within the interval.
     */
    public function wasRecentlySent(string $numEtu, int $days): bool
    {
        $sql = "
            SELECT 1 FROM relances 
            WHERE dossier_id = :id 
            AND date_relance >= (NOW() - INTERVAL :days DAY)
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $numEtu);
        $stmt->bindValue(':days', $days, PDO::PARAM_INT);
        $stmt->execute();

        return (bool)$stmt->fetchColumn();
    }

    /**
     * Logs a new reminder in the database.
     *
     * @param string $numEtu Student number.
     * @param string $message The content of the reminder email.
     * @return void
     */
    public function save(string $numEtu, string $message): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO relances (dossier_id, message, envoye_par)
            VALUES (:id, :message, NULL)
        ");

        $stmt->execute([
            'id' => $numEtu,
            'message' => $message
        ]);
    }
}