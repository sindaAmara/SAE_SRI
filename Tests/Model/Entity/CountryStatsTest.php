<?php

namespace Model\Entity;

use PHPUnit\Framework\TestCase;

/**
 * Class CountryStatsTest
 *
 * Unit tests for the CountryStats entity.
 *
 * Tests:
 * - Constructor and getters
 * - Conversion to array
 */
class CountryStatsTest extends TestCase
{
    /**
     * Test that the constructor correctly sets properties
     * and that the getters return the expected values.
     */
    public function testConstructorAndGetters(): void
    {
        $countryStats = new CountryStats("France", 42);

        $this->assertEquals("France", $countryStats->getName());
        $this->assertEquals(42, $countryStats->getCount());
    }

    /**
     * Test that the toArray() method returns the expected array structure.
     */
    public function testToArray(): void
    {
        $countryStats = new CountryStats("Espagne", 10);

        $expected = [
            'name'  => 'Espagne',
            'count' => 10,
        ];

        $this->assertEquals($expected, $countryStats->toArray());
    }
}