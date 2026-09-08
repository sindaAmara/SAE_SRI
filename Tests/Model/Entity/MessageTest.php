<?php

namespace Tests\Model\Entity;

use PHPUnit\Framework\TestCase;
use Model\Entity\Message;

/**
 * Class MessageTest
 *
 * Unit tests for the Message entity.
 *
 * Tests:
 * - Constructor and getters
 * - Default values
 * - markAsRead functionality
 */
class MessageTest extends TestCase
{
    /**
     * Test that the constructor correctly sets all properties
     * and that getters return the expected values.
     */
    public function testConstructorAndGetters(): void
    {
        $date = new \DateTime();

        $message = new Message(
            1,
            "student",
            "Bonjour",
            false,
            10,
            $date
        );

        $this->assertEquals(10,        $message->getId());
        $this->assertEquals(1,         $message->getConversationId());
        $this->assertEquals("student", $message->getSenderType());
        $this->assertEquals("Bonjour", $message->getContent());
        $this->assertFalse($message->isRead());
        $this->assertSame($date,       $message->getCreatedAt());
    }

    /**
     * Test that default values are correctly applied when optional parameters
     * are not provided (id, read status, createdAt).
     */
    public function testDefaultValues(): void
    {
        $message = new Message(
            2,
            "admin",
            "Message test"
        );

        $this->assertNull($message->getId());
        $this->assertEquals(2,              $message->getConversationId());
        $this->assertEquals("admin",        $message->getSenderType());
        $this->assertEquals("Message test", $message->getContent());
        $this->assertFalse($message->isRead());
        $this->assertInstanceOf(\DateTime::class, $message->getCreatedAt());
    }

    /**
     * Test that markAsRead sets the message's read status to true.
     */
    public function testMarkAsRead(): void
    {
        $message = new Message(
            1,
            "student",
            "Test message"
        );

        $this->assertFalse($message->isRead());

        $message->markAsRead();

        $this->assertTrue($message->isRead());
    }
}