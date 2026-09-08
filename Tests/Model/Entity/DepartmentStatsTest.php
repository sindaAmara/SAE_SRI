<?php

namespace Tests\Model\Entity;

use PHPUnit\Framework\TestCase;
use Model\Entity\DepartmentStats;

/**
 * Class DepartmentStatsTest
 *
 * Unit tests for the DepartmentStats entity.
 *
 * Tests:
 * - Constructor and getters
 * - Conversion to array
 */
class DepartmentStatsTest extends TestCase
{
    /**
     * Test that the constructor correctly sets properties
     * and that the getters return the expected values.
     */
    public function testConstructorAndGetters(): void
    {
        $departmentStats = new DepartmentStats("Informatique", 15);

        $this->assertEquals("Informatique", $departmentStats->getName());
        $this->assertEquals(15, $departmentStats->getCount());
    }

    /**
     * Test that the toArray() method returns the expected array structure.
     */
    public function testToArray(): void
    {
        $departmentStats = new DepartmentStats("Réseaux", 8);

        $expected = [
            'name'  => 'Réseaux',
            'count' => 8,
        ];

        $this->assertEquals($expected, $departmentStats->toArray());
    }
}