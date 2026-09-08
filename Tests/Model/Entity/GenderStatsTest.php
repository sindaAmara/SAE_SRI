<?php

namespace Tests\Model\Entity;

use PHPUnit\Framework\TestCase;
use Model\Entity\GenderStats;

/**
 * Class GenderStatsTest
 *
 * Unit tests for the GenderStats entity.
 *
 * Tests:
 * - Constructor and getters
 * - Total count calculation
 * - Male and female percentages
 * - toArray method output
 */
class GenderStatsTest extends TestCase
{
    /**
     * Test that the constructor correctly sets male and female counts,
     * and that the getters return the expected values.
     */
    public function testConstructorAndGetters(): void
    {
        $stats = new GenderStats(6, 4);

        $this->assertEquals(6, $stats->getMale());
        $this->assertEquals(4, $stats->getFemale());
    }

    /**
     * Test that getTotal returns the sum of male and female counts.
     */
    public function testGetTotal(): void
    {
        $stats = new GenderStats(6, 4);

        $this->assertEquals(10, $stats->getTotal());
    }

    /**
     * Test that getMalePercentage returns the correct percentage.
     */
    public function testMalePercentage(): void
    {
        $stats = new GenderStats(6, 4);

        $this->assertEquals(60.0, $stats->getMalePercentage());
    }

    /**
     * Test that getFemalePercentage returns the correct percentage.
     */
    public function testFemalePercentage(): void
    {
        $stats = new GenderStats(6, 4);

        $this->assertEquals(40.0, $stats->getFemalePercentage());
    }

    /**
     * Test that percentages return 0 when total count is zero.
     */
    public function testPercentagesWithZeroTotal(): void
    {
        $stats = new GenderStats(0, 0);

        $this->assertEquals(0, $stats->getMalePercentage());
        $this->assertEquals(0, $stats->getFemalePercentage());
    }

    /**
     * Test that toArray returns the correct associative array.
     */
    public function testToArray(): void
    {
        $stats = new GenderStats(3, 7);

        $expected = [
            'male'   => 3,
            'female' => 7,
        ];

        $this->assertEquals($expected, $stats->toArray());
    }
}