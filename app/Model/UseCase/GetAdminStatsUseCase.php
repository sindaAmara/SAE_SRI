<?php

namespace Model\UseCase;

use Model\Entity\AdminStats;
use Model\Entity\CountryStats;
use Model\Entity\DepartmentStats;
use Model\Repository\FolderRepositoryInterface;

/**
 * GetAdminStatsUseCase
 *
 * Use case responsible for assembling the full set of statistics
 * displayed on the administration dashboard.
 *
 * Queries the folder repository with optional mobility-type and department
 * filters, then combines folder completion, gender, student mobility,
 * continent, country, and Europe/non-Europe breakdowns into a single
 * AdminStats value object.
 */
class GetAdminStatsUseCase
{
    /** @var FolderRepositoryInterface Repository used to retrieve folder and student statistics */
    private FolderRepositoryInterface $repository;

    /**
     * @param FolderRepositoryInterface $repository Repository used to retrieve folder and student statistics
     */
    public function __construct(FolderRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Builds and returns the aggregated administration statistics.
     *
     * Applies an optional mobility-type filter ('etude' or 'stage'; any other
     * value is treated as no filter) and an optional department filter.
     * Fetches folder stats, gender stats, incoming/outgoing mobility counts,
     * continent breakdowns, Europe vs. non-Europe country counts, top 5 countries,
     * and top 5 departments from the repository, then wraps everything in an
     * AdminStats instance.
     *
     * @param string|null $mobilite    Mobility type filter: null = all | 'etude' | 'stage'
     * @param string|null $departement Department code to filter by, or null for all departments
     * @return AdminStats The fully assembled administration statistics
     */
    public function execute(?string $mobilite = null, ?string $departement = null): AdminStats
    {
        $mobiliteFilter = in_array($mobilite, ['etude', 'stage'], true) ? $mobilite : null;
        $departementFilter = !empty($departement) ? $departement : null;

        // Passer les deux filtres à toutes les méthodes du repository
        $dossierStats = $this->repository->getDossierStats($mobiliteFilter, $departementFilter);
        $genderStats  = $this->repository->getGenderStats($mobiliteFilter, $departementFilter);
        $mobility     = $this->repository->getIncomingOutgoingStats($mobiliteFilter, $departementFilter);
        $continents   = $this->repository->getContinentStats($mobiliteFilter, $departementFilter);
        $europeStats  = $this->repository->getEuropeVsNonEuropeStats($mobiliteFilter, $departementFilter);

        $topCountries = array_map(
            fn($row) => new CountryStats($row['name'], $row['count']),
            $this->repository->getTopCountries(5, $mobiliteFilter, $departementFilter)
        );

        $departments = array_map(
            fn($row) => new DepartmentStats($row['name'], $row['count']),
            $this->repository->getDepartmentStats(5, $mobiliteFilter, $departementFilter)
        );

        return new AdminStats(
            dossierStats:            $dossierStats,
            topCountries:            $topCountries,
            genderStats:             $genderStats,
            departments:             $departments,
            incomingStudents:        $mobility['incoming'],
            outgoingStudents:        $mobility['outgoing'],
            zoneStats:               $continents,
            europeCountriesCount:    $europeStats['europe_countries'],
            nonEuropeCountriesCount: $europeStats['non_europe_countries'],
        );
    }
}