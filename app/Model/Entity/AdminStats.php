<?php

namespace Model\Entity;

/**
 * AdminStats
 *
 * Aggregates all statistical data displayed on the administration dashboard.
 * Combines folder completion metrics, country and gender breakdowns,
 * department distribution, student mobility counts, and geographical zone data.
 */
class AdminStats
{
    /** @var FolderStats Folder completion statistics */
    private FolderStats $dossierStats;

    /** @var array<int, CountryStats> Top countries by student count */
    private array $topCountries;

    /** @var GenderStats Gender distribution statistics */
    private GenderStats $genderStats;

    /** @var array<int, DepartmentStats> Statistics broken down by department */
    private array $departments;

    /** @var int Total number of incoming (inbound) students */
    private int $incomingStudents;

    /** @var int Total number of outgoing (outbound) students */
    private int $outgoingStudents;

    /** @var array<int, array{name: string, count: int}> Student counts grouped by geographical zone */
    private array $zoneStats;

    /** @var int Number of distinct European countries represented */
    private int $europeCountriesCount;

    /** @var int Number of distinct non-European countries represented */
    private int $nonEuropeCountriesCount;

    /**
     * @param FolderStats                                    $dossierStats            Folder completion statistics
     * @param array<int, CountryStats>                       $topCountries            Top countries by student count
     * @param GenderStats                                    $genderStats             Gender distribution statistics
     * @param array<int, DepartmentStats>                    $departments             Statistics per department
     * @param int                                            $incomingStudents        Number of incoming students
     * @param int                                            $outgoingStudents        Number of outgoing students
     * @param array<int, array{name: string, count: int}>    $zoneStats               Student counts per geographical zone
     * @param int                                            $europeCountriesCount    Number of European countries represented
     * @param int                                            $nonEuropeCountriesCount Number of non-European countries represented
     */
    public function __construct(
        FolderStats $dossierStats,
        array       $topCountries,
        GenderStats $genderStats,
        array       $departments,
        int         $incomingStudents = 0,
        int         $outgoingStudents = 0,
        array       $zoneStats = [],
        int         $europeCountriesCount = 0,
        int         $nonEuropeCountriesCount = 0,
    ) {
        $this->dossierStats            = $dossierStats;
        $this->topCountries            = $topCountries;
        $this->genderStats             = $genderStats;
        $this->departments             = $departments;
        $this->incomingStudents        = $incomingStudents;
        $this->outgoingStudents        = $outgoingStudents;
        $this->zoneStats               = $zoneStats;
        $this->europeCountriesCount    = $europeCountriesCount;
        $this->nonEuropeCountriesCount = $nonEuropeCountriesCount;
    }

    /**
     * Returns the folder completion statistics.
     *
     * @return FolderStats
     */
    public function getDossierStats(): FolderStats
    {
        return $this->dossierStats;
    }

    /**
     * Returns the top countries ranked by student count.
     *
     * @return array<int, CountryStats>
     */
    public function getTopCountries(): array
    {
        return $this->topCountries;
    }

    /**
     * Returns the gender distribution statistics.
     *
     * @return GenderStats
     */
    public function getGenderStats(): GenderStats
    {
        return $this->genderStats;
    }

    /**
     * Returns the statistics broken down by department.
     *
     * @return array<int, DepartmentStats>
     */
    public function getDepartments(): array
    {
        return $this->departments;
    }

    /**
     * Returns the total number of incoming (inbound) students.
     *
     * @return int
     */
    public function getIncomingStudents(): int
    {
        return $this->incomingStudents;
    }

    /**
     * Returns the total number of outgoing (outbound) students.
     *
     * @return int
     */
    public function getOutgoingStudents(): int
    {
        return $this->outgoingStudents;
    }

    /**
     * Returns student counts grouped by geographical zone.
     *
     * @return array<int, array{name: string, count: int}>
     */
    public function getZoneStats(): array
    {
        return $this->zoneStats;
    }

    /**
     * Returns the number of distinct European countries represented.
     *
     * @return int
     */
    public function getEuropeCountriesCount(): int
    {
        return $this->europeCountriesCount;
    }

    /**
     * Returns the number of distinct non-European countries represented.
     *
     * @return int
     */
    public function getNonEuropeCountriesCount(): int
    {
        return $this->nonEuropeCountriesCount;
    }

    /**
     * Returns all statistics as a flat associative array.
     *
     * Includes folder counts, country and gender data, department breakdown,
     * student mobility totals, continent rankings, and country counts by zone.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'complete_folders'           => $this->dossierStats->getCompleted(),
            'incomplete_folders'         => $this->dossierStats->getTotal() - $this->dossierStats->getCompleted(),
            'total_folders'              => $this->dossierStats->getTotal(),
            'top_countries'              => array_map(fn($c) => $c->toArray(), $this->topCountries),
            'gender'                     => $this->genderStats->toArray(),
            'departments'                => array_map(fn($d) => $d->toArray(), $this->departments),
            'incoming_students'          => $this->incomingStudents,
            'outgoing_students'          => $this->outgoingStudents,
            'top_continents'             => $this->zoneStats,
            'europe_countries_count'     => $this->europeCountriesCount,
            'non_europe_countries_count' => $this->nonEuropeCountriesCount,
            'total_countries_count'      => $this->europeCountriesCount + $this->nonEuropeCountriesCount,
        ];
    }
}