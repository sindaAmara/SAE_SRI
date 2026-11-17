<?php

namespace Tests\Model\Entity;

use PHPUnit\Framework\TestCase;
use Model\Entity\Partner;

/**
 * Class PartnerTest
 *
 * Unit tests for the Partner entity.
 *
 * Tests:
 * - Constructor and getters
 */
class PartnerTest extends TestCase
{
    /**
     * Test that the constructor correctly sets all properties
     * and that getters return the expected values.
     */
    public function testConstructorAndGetters(): void
    {
        $partner = new Partner(
            "Europe",
            "Espagne",
            "Barcelone",
            "Universitat de Barcelona",
            "Échange"
        );

        $this->assertEquals("Europe",                    $partner->getContinent());
        $this->assertEquals("Espagne",                    $partner->getCountry());
        $this->assertEquals("Barcelone",                  $partner->getCity());
        $this->assertEquals("Universitat de Barcelona",   $partner->getInstitution());
        $this->assertEquals("Échange",                    $partner->getType());
    }
}