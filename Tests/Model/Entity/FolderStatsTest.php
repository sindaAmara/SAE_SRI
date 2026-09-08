<?php

namespace Tests\Model\Entity;

use PHPUnit\Framework\TestCase;
use Model\Entity\FolderStats;

/**
 * Class FolderStatsTest
 *
 * Unit tests for the FolderStats entity.
 *
 * Tests:
 * - Constructor and getters
 * - Completion percentage calculation
 * - Completion percentage when total is zero
 */
class FolderStatsTest extends TestCase
{
    /**
     * Test that the constructor correctly sets total and completed counts
     * and that the getters return the expected values.
     */
    public function testConstructorAndGetters(): void
    {
        $stats = new FolderStats(10, 7);

        $this->assertEquals(10, $stats->getTotal());
        $this->assertEquals(7, $stats->getCompleted());
    }

    /**
     * Test that the completion percentage is correctly calculated.
     */
    public function testCompletionPercentage(): void
    {
        $stats = new FolderStats(10, 5);

        $this->assertEquals(50.0, $stats->getCompletionPercentage());
    }

    /**
     * Test that the completion percentage is zero
     * when the total number of folders is zero.
     */
    public function testCompletionPercentageWithZeroTotal(): void
    {
        $stats = new FolderStats(0, 0);

        $this->assertEquals(0, $stats->getCompletionPercentage());
    }
}