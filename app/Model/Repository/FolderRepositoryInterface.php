<?php

namespace Model\Repository;

use Model\Entity\FolderStats;
use Model\Entity\GenderStats;

/**
 * Interface FolderRepositoryInterface
 * * Defines the contract for managing student application folders (dossiers),
 * including CRUD operations, status management, statistics, and document validation.
 */
interface FolderRepositoryInterface
{
    // ---------------------------------------------------------------
    // CRUD
    // ---------------------------------------------------------------

    /**
     * Retrieves all dossiers from the storage.
     * @return array<int, array<string, mixed>> List of all dossiers.
     */
    public function findAll(): array;

    /**
     * Finds a specific dossier by the student's unique number.
     * @param string $numEtu Student number.
     * @return array<string, mixed>|null Dossier data as an associative array, or null if not found.
     */
    public function findByNumEtu(string $numEtu): ?array;

    /**
     * Creates a new dossier record.
     * @param array<string, mixed> $data Associative array of dossier properties.
     * @return bool True on success, false on failure.
     */
    public function create(array $data): bool;

    /**
     * Updates an existing dossier record.
     * @param string $numEtu Student number identifying the dossier.
     * @param array<string, mixed> $data Associative array of properties to update.
     * @return bool True on success, false on failure.
     */
    public function update(string $numEtu, array $data): bool;

    // ---------------------------------------------------------------
    // Status
    // ---------------------------------------------------------------

    /** * Toggles the folder status via a direct flip logic.
     * @param string $numEtu Student number.
     * @return bool True on success.
     */
    public function toggleStatus(string $numEtu): bool;

    /** * Toggles the completion status using a select-then-update approach.
     * Kept for backward compatibility.
     * @param string $numEtu Student number.
     * @return bool True on success.
     */
    public function toggleCompleteStatus(string $numEtu): bool;

    /**
     * Explicitly sets the workflow status of a folder.
     * @param string $numEtu Student number.
     * @param string $status The new status string (e.g., 'instruction', 'accepte').
     * @return bool True on success.
     */
    public function setStatus(string $numEtu, string $status): bool;

    /**
     * Cycles the folder through the predefined workflow statuses.
     * @param string $numEtu Student number.
     * @return bool True on success.
     */
    public function cycleStatus(string $numEtu): bool;

    /**
     * Saves the department head's opinion on a specific folder.
     * @param string $numEtu Student number.
     * @param string|null $avis The opinion ('accepte', 'refuse', or null to reset).
     * @return bool True on success.
     */
    public function setAvisChef(string $numEtu, ?string $avis): bool;

    // ---------------------------------------------------------------
    // Pagination & search
    // ---------------------------------------------------------------

    /**
     * Performs a filtered search with pagination support.
     * @param array<string, mixed> $filters Search criteria (e.g., search term, type, zone).
     * @param int $page Current page number.
     * @param int $perPage Number of items per page.
     * @return array{data: array<int, array<string, mixed>>, total: int, totalPages: int}
     */
    public function searchWithPagination(array $filters, int $page, int $perPage): array;

    // ---------------------------------------------------------------
    // Import
    // ---------------------------------------------------------------

    /** * Performs a batch "upsert" (insert or update) for multiple dossier records.
     * @param array<int, array<string, mixed>> $dossiers List of dossier data arrays.
     * @return int The number of records successfully processed.
     */
    public function upsertMultiple(array $dossiers): int;

    // ---------------------------------------------------------------
    // Statistics
    // ---------------------------------------------------------------

    /**
     * Calculates folder statistics with optional filtering by mobility or department.
     * @param string|null $mobilite Mobility type filter.
     * @param string|null $departement Department code filter.
     * @return FolderStats Entity containing total and completed counts.
     */
    public function getDossierStats(?string $mobilite = null, ?string $departement = null): FolderStats;

    /**
     * Retrieves overall global folder statistics.
     * @return FolderStats
     */
    public function getGlobalStats(): FolderStats;

    /**
     * Retrieves gender-based statistics for folders.
     * @param string|null $mobilite Mobility type filter.
     * @param string|null $departement Department code filter.
     * @return GenderStats Entity containing male and female counts.
     */
    public function getGenderStats(?string $mobilite = null, ?string $departement = null): GenderStats;

    /**
     * Gets the top destination countries based on dossier volume.
     * @param int $limit Maximum number of countries to return.
     * @param string|null $mobilite Mobility type filter.
     * @param string|null $departement Department code filter.
     * @return array<int, array{name: string, count: int}>
     */
    public function getTopCountries(int $limit, ?string $mobilite = null, ?string $departement = null): array;

    /**
     * Gets volume statistics grouped by department.
     * @param int $limit Maximum number of departments to return.
     * @param string|null $mobilite Mobility type filter.
     * @param string|null $departement Department code filter.
     * @return array<int, array{name: string, count: int}>
     */
    public function getDepartmentStats(int $limit, ?string $mobilite = null, ?string $departement = null): array;

    /**
     * Compares incoming vs outgoing student counts.
     * @param string|null $mobilite Mobility type filter.
     * @param string|null $departement Department code filter.
     * @return array{incoming: int, outgoing: int}
     */
    public function getIncomingOutgoingStats(?string $mobilite = null, ?string $departement = null): array;

    /**
     * Gets volume statistics grouped by continent.
     * @param string|null $mobilite Mobility type filter.
     * @param string|null $departement Department code filter.
     * @return array<int, array{name: string, count: int}>
     */
    public function getContinentStats(?string $mobilite = null, ?string $departement = null): array;

    /**
     * Compares the number of distinct countries in Europe vs Non-Europe regions.
     * @param string|null $mobilite Mobility type filter.
     * @param string|null $departement Department code filter.
     * @return array{europe_countries: int, non_europe_countries: int}
     */
    public function getEuropeVsNonEuropeStats(?string $mobilite = null, ?string $departement = null): array;

    /**
     * Gets volume statistics grouped by geographic zone.
     * @param string|null $mobilite Mobility type filter.
     * @return array<int, array{name: string, count: int}>
     */
    public function getZoneStats(?string $mobilite = null): array;

    // ---------------------------------------------------------------
    // Document validation
    // ---------------------------------------------------------------

    /**
     * Analyzes which required documents are missing or present for a given student.
     * @param string $numetu Student number.
     * @return array{manquants: array<int, string>, presents: array<int, string>, statuts: array<string, string>}
     */
    public function analyserDocuments(string $numetu): array;

    /**
     * Saves document validation results and associated administrative metadata.
     * @param string $numetu Student number.
     * @param array<string, string> $statutsDocuments Validation status for each document.
     * @param string|null $dateLimite Deadline for document correction.
     * @param string|null $commentaire Admin comments or feedback.
     * @return bool True on success.
     */
    public function enregistrerValidation(
        string $numetu,
        array $statutsDocuments,
        ?string $dateLimite = null,
        ?string $commentaire = null
    ): bool;

    /**
     * Returns folders that are currently marked as incomplete.
     *
     * @return array<int, array<string, mixed>> List of incomplete dossiers.
     */
    public function findIncompleteFolders(): array;
}