<?php

namespace Model\Persistence;

use Database;
use Model\Entity\Date;
use PDO;

/**
 * Class DatePDO
 * Handles persistence operations for important dates using PDO.
 */
class DateRepositoryPDO
{
    /** @var PDO The database connection instance */
    private PDO $pdo;

    /**
     * DatePDO constructor.
     * Initializes the PDO connection via the Database singleton.
     */
    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    /**
     * Creates a new important date.
     *
     * @param string $id The unique identifier for the date.
     * @param string $titre The title of the important date.
     * @param string $description The description of the important date.
     * @param string $date The date value, usually in YYYY-MM-DD format.
     * @param string $type The type/category of the important date
     */
    public function create(string $id, string $titre, string $description, string $date, string $type): bool
    {
        $sql = "INSERT INTO dates_importantes (id, titre, description, date, type)
                VALUES (:id, :titre, :description, :date, :type)";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':id' => $id,
            ':titre' => $titre,
            ':description' => $description,
            ':date' => $date,
            ':type' => $type
        ]);
    }

    /**
     * Updates an important date from its ID.
     *
     * @param string $id The important date ID.
     * @param string $titre The updated title.
     * @param string $description The updated description.
     * @param string $date The updated date.
     * @param string $type The updated type/category.
     * @return bool True on success, false on failure.
     */
    public function update(string $id, string $titre, string $description, string $date, string $type): bool
    {
        $sql = "UPDATE dates_importantes
                SET titre = :titre,
                    description = :description,
                    date = :date,
                    type = :type
                WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':id' => $id,
            ':titre' => $titre,
            ':description' => $description,
            ':date' => $date,
            ':type' => $type
        ]);
    }

    /**
     * Deletes an important date from its ID.
     *
     * @param string $id The important date ID.
     * @return bool True on success, false on failure.
     */
    public function delete(string $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM dates_importantes WHERE id = :id");

        return $stmt->execute([
            ':id' => $id
        ]);
    }

    /**
     * Finds an important date by its ID.
     *
     * @param string $id The important date ID.
     * @return Date|null The important date entity or null if not found.
     */
    public function findById(string $id): ?Date
    {
        $stmt = $this->pdo->prepare("SELECT * FROM dates_importantes WHERE id = :id");
        $stmt->execute([
            ':id' => $id
        ]);

        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!is_array($data)) {
            return null;
        }

        return $this->hydrateDate($data);
    }

    /**
     * Retrieves all noted important dates for a specific date.
     *
     * @param string $date The searched date, usually in YYYY-MM-DD format.
     * @return array<int, Date> List of important dates.
     */
    public function findByDate(string $date): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM dates_importantes WHERE date = :date ORDER BY id DESC");
        $stmt->execute([
            ':date' => $date
        ]);

        return $this->fetchAndHydrateList($stmt);
    }

    /**
     * Iterates through a statement result to hydrate a list of Date entities.
     *
     * @param \PDOStatement $stmt
     * @return array<int, Date>
     */
    private function fetchAndHydrateList(\PDOStatement $stmt): array
    {
        $dates = [];

        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (is_array($data)) {
                $dates[] = $this->hydrateDate($data);
            }
        }

        return $dates;
    }

    /**
     * Transforms a database row into a Date entity.
     *
     * @param array<string, mixed> $data
     * @return Date
     */
    private function hydrateDate(array $data): Date
    {
        return new Date(
            id: is_scalar($data['id'] ?? null) ? (string) $data['id'] : '',
            titre: is_scalar($data['titre'] ?? null) ? (string) $data['titre'] : '',
            description: is_scalar($data['description'] ?? null) ? (string) $data['description'] : '',
            date: is_scalar($data['date'] ?? null) ? (string) $data['date'] : '',
            type: is_scalar($data['type'] ?? null) ? (string) $data['type'] : ''
        );
    }
}
