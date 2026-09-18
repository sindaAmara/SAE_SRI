<?php

namespace Model\Persistence;

use Database;
use PDO;

/**
 * StageFormRepositoryPDO
 *
 * Handles persistence of end-of-internship form responses.
 */
class StageFormRepositoryPDO
{
    private PDO $conn;

    public function __construct()
    {
        $this->conn = Database::getInstance()->getConnection();
    }

    /**
     * Inserts a new form response for a given student.
     *
     * @param string $numetu Student number (foreign key to the student/folder)
     * @param array<string, string> $data Validated form data
     */
    public function save(string $numetu, array $data): void
    {
        $stmt = $this->conn->prepare(
            'INSERT INTO stage_form_responses
                (numetu, host_institution, start_date, end_date, evaluation, submitted_at)
             VALUES
                (:numetu, :host_institution, :start_date, :end_date, :evaluation, NOW())'
        );

        $stmt->execute([
            'numetu'           => $numetu,
            'host_institution' => $data['host_institution'],
            'start_date'       => $data['start_date'],
            'end_date'         => $data['end_date'],
            'evaluation'       => $data['evaluation'] !== '' ? $data['evaluation'] : null,
        ]);
    }

    /**
     * Returns whether a student has already submitted a response.
     */
    public function hasSubmitted(string $numetu): bool
    {
        $stmt = $this->conn->prepare(
            'SELECT COUNT(*) FROM stage_form_responses WHERE numetu = :numetu'
        );
        $stmt->execute(['numetu' => $numetu]);

        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Returns all submitted responses joined with basic student identity,
     * ordered by most recent submission first.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findAll(): array
    {
        $stmt = $this->conn->query(
            'SELECT
                r.id,
                r.numetu,
                e.nom AS nom,
                e.prenom AS prenom,
                r.host_institution,
                r.start_date,
                r.end_date,
                r.evaluation,
                r.submitted_at
             FROM stage_form_responses r
             LEFT JOIN etudiants e ON e.NumEtu = r.numetu
             ORDER BY r.submitted_at DESC'
        );

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return is_array($rows) ? $rows : [];
    }
}