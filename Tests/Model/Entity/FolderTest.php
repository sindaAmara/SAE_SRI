<?php

namespace Tests\Model\Entity;

use PHPUnit\Framework\TestCase;
use Model\Entity\Folder;

/**
 * Class FolderTest
 *
 * Unit tests for the Folder entity.
 *
 * Tests:
 * - Constructor and getters
 * - isComplete method behavior for true and false states
 */
class FolderTest extends TestCase
{
    /**
     * Test that the constructor correctly sets the ID and completion status,
     * and that the getters return the expected values for a complete folder.
     */
    public function testConstructorAndGetters(): void
    {
        $folder = new Folder(1, true);

        $this->assertEquals(1, $folder->getId());
        $this->assertTrue($folder->isComplete());
    }

    /**
     * Test the behavior of isComplete for a folder that is not complete.
     */
    public function testIsCompleteFalse(): void
    {
        $folder = new Folder(2, false);

        $this->assertEquals(2, $folder->getId());
        $this->assertFalse($folder->isComplete());
    }
}