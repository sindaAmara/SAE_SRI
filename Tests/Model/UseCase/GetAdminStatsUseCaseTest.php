<?php

namespace Tests\Model\UseCase;

use Model\Entity\AdminStats;
use Model\Entity\FolderStats;
use Model\Entity\GenderStats;
use Model\Repository\FolderRepositoryInterface;
use Model\UseCase\GetAdminStatsUseCase;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit tests for {@see GetAdminStatsUseCase}.
 *
 * Coverage areas:
 * - execute() always returns an {@see AdminStats} instance
 * - Mobility filter ("etude", "stage") is forwarded to every repository method
 * - Invalid mobility values are normalised to null before forwarding
 * - Department filter is forwarded to every repository method
 * - Empty department string is normalised to null before forwarding
 * - All seven repository stat methods are called exactly once per execute()
 * - getTopCountries() and getDepartmentStats() are called with limit 5
 * - Country and department rows are mapped to the correct entity objects
 * - Combined filter (mobilite + departement) is forwarded correctly
 *
 * All tests use a mocked {@see FolderRepositoryInterface} so no database
 * access occurs.
 *
 * Run with:
 *   ./vendor/bin/phpunit Tests/Model/UseCase/GetAdminStatsUseCaseTest.php
 */
class GetAdminStatsUseCaseTest extends TestCase
{
    /**
     * Mocked repository injected into the use case under test.
     *
     * @var FolderRepositoryInterface&MockObject
     */
    private FolderRepositoryInterface $repoMock;

    /** Use case instance shared across all tests. */
    private GetAdminStatsUseCase $useCase;

    /**
     * Creates a fresh use case backed by a mocked repository before each test.
     */
    protected function setUp(): void
    {
        $this->repoMock = $this->createMock(FolderRepositoryInterface::class);
        $this->useCase  = new GetAdminStatsUseCase($this->repoMock);
    }

    // -------------------------------------------------------------------------
    // Repository mock helper
    // -------------------------------------------------------------------------

    /**
     * Configures all seven repository stat methods to return realistic dummy
     * data filtered by the given mobility and department values.
     *
     * Use this helper in tests that need the use case to complete successfully
     * but do not care about the exact repository call arguments.
     */
    private function setupDefaultRepoMock(?string $mobilite = null, ?string $departement = null): void
    {
        $this->repoMock->method('getDossierStats')
            ->with($mobilite, $departement)
            ->willReturn(new FolderStats(10, 5));

        $this->repoMock->method('getGenderStats')
            ->with($mobilite, $departement)
            ->willReturn(new GenderStats(6, 4));

        $this->repoMock->method('getIncomingOutgoingStats')
            ->with($mobilite, $departement)
            ->willReturn(['incoming' => 3, 'outgoing' => 7]);

        $this->repoMock->method('getContinentStats')
            ->with($mobilite, $departement)
            ->willReturn([['name' => 'Europe', 'count' => 8]]);

        $this->repoMock->method('getEuropeVsNonEuropeStats')
            ->with($mobilite, $departement)
            ->willReturn(['europe_countries' => 5, 'non_europe_countries' => 3]);

        $this->repoMock->method('getTopCountries')
            ->with(5, $mobilite, $departement)
            ->willReturn([['name' => 'Allemagne', 'count' => 4]]);

        $this->repoMock->method('getDepartmentStats')
            ->with(5, $mobilite, $departement)
            ->willReturn([['name' => 'INFO', 'count' => 6]]);
    }

    // =========================================================
    // Return type — AdminStats
    // =========================================================

    /**
     * @test
     * execute() must always return an AdminStats instance so the view layer
     * can rely on its typed accessors.
     */
    public function testExecuteReturnsAdminStats(): void
    {
        $this->setupDefaultRepoMock();

        $result = $this->useCase->execute();

        $this->assertInstanceOf(AdminStats::class, $result);
    }

    /**
     * @test
     * Calling execute() with no arguments must still return an AdminStats
     * instance (null filters are the default / "show all" case).
     */
    public function testExecuteWithNoFiltersPassesNullToAllMethods(): void
    {
        $this->setupDefaultRepoMock(null, null);

        $result = $this->useCase->execute();

        $this->assertInstanceOf(AdminStats::class, $result);
    }

    // =========================================================
    // Mobility filter
    // =========================================================

    /**
     * @test
     * When mobilite="etude" is passed, the use case must forward it to every
     * repository method so the stats are scoped to study-abroad folders.
     */
    public function testExecuteWithEtudeFilterPassesMobiliteToRepo(): void
    {
        $this->setupDefaultRepoMock('etude', null);

        $result = $this->useCase->execute('etude');

        $this->assertInstanceOf(AdminStats::class, $result);
    }

    /**
     * @test
     * When mobilite="stage" is passed, the use case must forward it to every
     * repository method so the stats are scoped to internship folders.
     */
    public function testExecuteWithStageFilterPassesMobiliteToRepo(): void
    {
        $this->setupDefaultRepoMock('stage', null);

        $result = $this->useCase->execute('stage');

        $this->assertInstanceOf(AdminStats::class, $result);
    }

    /**
     * @test
     * An unrecognised mobility value must be normalised to null before being
     * forwarded to the repository so the repository is never queried with an
     * invalid filter value.
     */
    public function testExecuteWithInvalidMobilitePassesNullToRepo(): void
    {
        // An invalid mobilite value must be replaced with null.
        $this->setupDefaultRepoMock(null, null);

        $result = $this->useCase->execute('invalid');

        $this->assertInstanceOf(AdminStats::class, $result);
    }

    // =========================================================
    // Department filter
    // =========================================================

    /**
     * @test
     * When a non-empty department code is supplied, the use case must forward
     * it to every repository method so the stats are scoped to that department.
     */
    public function testExecuteWithDepartementFilterPassesDepartementToRepo(): void
    {
        $this->setupDefaultRepoMock(null, 'INFO');

        $result = $this->useCase->execute(null, 'INFO');

        $this->assertInstanceOf(AdminStats::class, $result);
    }

    /**
     * @test
     * An empty department string must be normalised to null before forwarding
     * so the repository receives a proper null instead of an empty string.
     */
    public function testExecuteWithEmptyDepartementPassesNullToRepo(): void
    {
        // Empty string → null (no department filter applied).
        $this->setupDefaultRepoMock(null, null);

        $result = $this->useCase->execute(null, '');

        $this->assertInstanceOf(AdminStats::class, $result);
    }

    // =========================================================
    // Repository call coverage
    // =========================================================

    /**
     * @test
     * A single call to execute() must invoke all seven repository stat methods
     * exactly once — no method may be skipped or called multiple times.
     */
    public function testExecuteCallsAllRepositoryMethods(): void
    {
        $this->repoMock->expects($this->once())->method('getDossierStats')
            ->willReturn(new FolderStats(0, 0));
        $this->repoMock->expects($this->once())->method('getGenderStats')
            ->willReturn(new GenderStats(0, 0));
        $this->repoMock->expects($this->once())->method('getIncomingOutgoingStats')
            ->willReturn(['incoming' => 0, 'outgoing' => 0]);
        $this->repoMock->expects($this->once())->method('getContinentStats')
            ->willReturn([]);
        $this->repoMock->expects($this->once())->method('getEuropeVsNonEuropeStats')
            ->willReturn(['europe_countries' => 0, 'non_europe_countries' => 0]);
        $this->repoMock->expects($this->once())->method('getTopCountries')
            ->willReturn([]);
        $this->repoMock->expects($this->once())->method('getDepartmentStats')
            ->willReturn([]);

        $this->useCase->execute();
    }

    /**
     * @test
     * execute() must request exactly the top 5 countries (limit = 5) so the
     * dashboard chart always shows a consistent number of entries.
     */
    public function testExecuteRequestsTop5Countries(): void
    {
        $this->repoMock->method('getDossierStats')->willReturn(new FolderStats(0, 0));
        $this->repoMock->method('getGenderStats')->willReturn(new GenderStats(0, 0));
        $this->repoMock->method('getIncomingOutgoingStats')->willReturn(['incoming' => 0, 'outgoing' => 0]);
        $this->repoMock->method('getContinentStats')->willReturn([]);
        $this->repoMock->method('getEuropeVsNonEuropeStats')->willReturn(['europe_countries' => 0, 'non_europe_countries' => 0]);
        $this->repoMock->method('getDepartmentStats')->willReturn([]);

        $this->repoMock->expects($this->once())
            ->method('getTopCountries')
            ->with(5) // first argument must be the limit
            ->willReturn([]);

        $this->useCase->execute();
    }

    /**
     * @test
     * execute() must request exactly the top 5 departments (limit = 5) so the
     * dashboard chart always shows a consistent number of entries.
     */
    public function testExecuteRequestsTop5Departments(): void
    {
        $this->repoMock->method('getDossierStats')->willReturn(new FolderStats(0, 0));
        $this->repoMock->method('getGenderStats')->willReturn(new GenderStats(0, 0));
        $this->repoMock->method('getIncomingOutgoingStats')->willReturn(['incoming' => 0, 'outgoing' => 0]);
        $this->repoMock->method('getContinentStats')->willReturn([]);
        $this->repoMock->method('getEuropeVsNonEuropeStats')->willReturn(['europe_countries' => 0, 'non_europe_countries' => 0]);
        $this->repoMock->method('getTopCountries')->willReturn([]);

        $this->repoMock->expects($this->once())
            ->method('getDepartmentStats')
            ->with(5) // first argument must be the limit
            ->willReturn([]);

        $this->useCase->execute();
    }

    // =========================================================
    // CountryStats / DepartmentStats entity mapping
    // =========================================================

    /**
     * @test
     * The raw country rows returned by the repository must be mapped to typed
     * CountryStats objects with the correct name and count values.
     */
    public function testExecuteMapsTopCountriesToCountryStatsObjects(): void
    {
        $this->repoMock->method('getDossierStats')->willReturn(new FolderStats(0, 0));
        $this->repoMock->method('getGenderStats')->willReturn(new GenderStats(0, 0));
        $this->repoMock->method('getIncomingOutgoingStats')->willReturn(['incoming' => 0, 'outgoing' => 0]);
        $this->repoMock->method('getContinentStats')->willReturn([]);
        $this->repoMock->method('getEuropeVsNonEuropeStats')->willReturn(['europe_countries' => 0, 'non_europe_countries' => 0]);
        $this->repoMock->method('getDepartmentStats')->willReturn([]);
        $this->repoMock->method('getTopCountries')->willReturn([
            ['name' => 'Espagne', 'count' => 3],
            ['name' => 'Japon',   'count' => 2],
        ]);

        $stats = $this->useCase->execute();

        $countries = $stats->getTopCountries();
        $this->assertCount(2, $countries);
        $this->assertSame('Espagne', $countries[0]->getName());
        $this->assertSame(3,         $countries[0]->getCount());
    }

    /**
     * @test
     * The raw department rows returned by the repository must be mapped to
     * typed DepartmentStats objects with the correct name and count values.
     */
    public function testExecuteMapsDepartmentsToDepartmentStatsObjects(): void
    {
        $this->repoMock->method('getDossierStats')->willReturn(new FolderStats(0, 0));
        $this->repoMock->method('getGenderStats')->willReturn(new GenderStats(0, 0));
        $this->repoMock->method('getIncomingOutgoingStats')->willReturn(['incoming' => 0, 'outgoing' => 0]);
        $this->repoMock->method('getContinentStats')->willReturn([]);
        $this->repoMock->method('getEuropeVsNonEuropeStats')->willReturn(['europe_countries' => 0, 'non_europe_countries' => 0]);
        $this->repoMock->method('getTopCountries')->willReturn([]);
        $this->repoMock->method('getDepartmentStats')->willReturn([
            ['name' => 'MATH', 'count' => 9],
        ]);

        $stats = $this->useCase->execute();

        $depts = $stats->getDepartments();
        $this->assertCount(1, $depts);
        $this->assertSame('MATH', $depts[0]->getName());
        $this->assertSame(9,      $depts[0]->getCount());
    }

    // =========================================================
    // Combined filters
    // =========================================================

    /**
     * @test
     * When both mobilite and departement filters are supplied together, both
     * must be forwarded to every repository method simultaneously.
     */
    public function testExecuteWithBothFilters(): void
    {
        $this->setupDefaultRepoMock('stage', 'MATH');

        $result = $this->useCase->execute('stage', 'MATH');

        $this->assertInstanceOf(AdminStats::class, $result);
    }
}