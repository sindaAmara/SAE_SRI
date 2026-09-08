<?php

namespace Model\Entity;

use PHPUnit\Framework\TestCase;

/**
 * Class AdminStatsTest
 *
 * Unit tests for the AdminStats entity.
 *
 * It verifies that:
 * - All getters return the expected values.
 * - The toArray() method returns a properly structured array.
 */
class AdminStatsTest extends TestCase
{
    /**
     * Test that the getters of AdminStats return the expected objects and values.
     */
    public function testGetters(): void
    {
        // Mock dependencies
        $dossierStats = $this->createMock(FolderStats::class);
        $genderStats  = $this->createMock(GenderStats::class);
        $country      = $this->createMock(CountryStats::class);
        $department   = $this->createMock(DepartmentStats::class);

        $topCountries = [$country];
        $departments  = [$department];

        // Create AdminStats instance
        $adminStats = new AdminStats(
            $dossierStats,
            $topCountries,
            $genderStats,
            $departments,
            10, // incoming students
            5,  // outgoing students
            [['name' => 'Europe', 'count' => 3]], // zone stats
            8,  // Europe countries count
            2   // Non-Europe countries count
        );

        // Assert that getters return expected objects
        $this->assertSame($dossierStats, $adminStats->getDossierStats());
        $this->assertSame($topCountries, $adminStats->getTopCountries());
        $this->assertSame($genderStats,  $adminStats->getGenderStats());
        $this->assertSame($departments,  $adminStats->getDepartments());

        // Assert that getters return expected scalar values
        $this->assertEquals(10, $adminStats->getIncomingStudents());
        $this->assertEquals(5,  $adminStats->getOutgoingStudents());

        $this->assertEquals([['name' => 'Europe', 'count' => 3]], $adminStats->getZoneStats());

        $this->assertEquals(8, $adminStats->getEuropeCountriesCount());
        $this->assertEquals(2, $adminStats->getNonEuropeCountriesCount());
    }

    /**
     * Test that the toArray() method returns an array with correct structure and values.
     */
    public function testToArray(): void
    {
        // Mock FolderStats
        $dossierStats = $this->createMock(FolderStats::class);
        $dossierStats->method('getCompleted')->willReturn(7);
        $dossierStats->method('getTotal')->willReturn(10);

        // Mock CountryStats
        $country = $this->createMock(CountryStats::class);
        $country->method('toArray')->willReturn(['country' => 'France']);

        // Mock GenderStats
        $genderStats = $this->createMock(GenderStats::class);
        $genderStats->method('toArray')->willReturn(['male' => 5, 'female' => 5]);

        // Mock DepartmentStats
        $department = $this->createMock(DepartmentStats::class);
        $department->method('toArray')->willReturn(['department' => 'Info']);

        // Create AdminStats instance
        $adminStats = new AdminStats(
            $dossierStats,
            [$country],
            $genderStats,
            [$department],
            4,  // incoming students
            6,  // outgoing students
            [['name' => 'Europe', 'count' => 3]], // zone stats
            8,  // Europe countries count
            2   // Non-Europe countries count
        );

        // Convert to array
        $result = $adminStats->toArray();

        // Assert Folder stats
        $this->assertEquals(7,  $result['complete_folders']);
        $this->assertEquals(3,  $result['incomplete_folders']);
        $this->assertEquals(10, $result['total_folders']);

        // Assert Top countries
        $this->assertEquals([['country' => 'France']], $result['top_countries']);

        // Assert Gender stats
        $this->assertEquals(['male' => 5, 'female' => 5], $result['gender']);

        // Assert Departments
        $this->assertEquals([['department' => 'Info']], $result['departments']);

        // Assert Student counts
        $this->assertEquals(4, $result['incoming_students']);
        $this->assertEquals(6, $result['outgoing_students']);

        // Assert Zone stats
        $this->assertEquals([['name' => 'Europe', 'count' => 3]], $result['top_continents']);

        // Assert country counts
        $this->assertEquals(8,  $result['europe_countries_count']);
        $this->assertEquals(2,  $result['non_europe_countries_count']);
        $this->assertEquals(10, $result['total_countries_count']);
    }
}