<?php

namespace Model\Entity;

use PHPUnit\Framework\TestCase;

/**
 * Class ConversationTest
 *
 * Unit tests for the Conversation entity.
 *
 * It tests:
 * - Status management.
 * - Adding and retrieving messages.
 * - Retrieving the first and last message.
 * - Detecting unread messages for admin and student.
 */
class ConversationTest extends TestCase
{
    /**
     * Test that the status of a conversation can be set and retrieved.
     */
    public function testSetStatus(): void
    {
        $conversation = new Conversation(
            "12345",
            "John Doe",
            "john@example.com",
            "Subject test"
        );

        $conversation->setStatus("closed");

        $this->assertEquals("closed", $conversation->getStatus());
    }

    /**
     * Test adding a message and retrieving it from the conversation.
     */
    public function testAddAndGetMessages(): void
    {
        $conversation = new Conversation(
            "12345",
            "John Doe",
            "john@example.com",
            "Subject test"
        );

        $message = $this->createMock(Message::class);
        $conversation->addMessage($message);

        $messages = $conversation->getMessages();

        $this->assertCount(1, $messages);
        $this->assertSame($message, $messages[0]);
    }

    /**
     * Test retrieving the first message from a conversation.
     */
    public function testGetFirstMessage(): void
    {
        $conversation = new Conversation(
            "12345",
            "John Doe",
            "john@example.com",
            "Subject test"
        );

        // No messages yet
        $this->assertNull($conversation->getFirstMessage());

        $message1 = $this->createMock(Message::class);
        $message2 = $this->createMock(Message::class);

        $conversation->addMessage($message1);
        $conversation->addMessage($message2);

        $this->assertSame($message1, $conversation->getFirstMessage());
    }

    /**
     * Test retrieving the last message from a conversation.
     */
    public function testGetLastMessage(): void
    {
        $conversation = new Conversation(
            "12345",
            "John Doe",
            "john@example.com",
            "Subject test"
        );

        // No messages yet
        $this->assertNull($conversation->getLastMessage());

        $message1 = $this->createMock(Message::class);
        $message2 = $this->createMock(Message::class);

        $conversation->addMessage($message1);
        $conversation->addMessage($message2);

        $this->assertSame($message2, $conversation->getLastMessage());
    }

    /**
     * Test detection of unread messages for admin.
     */
    public function testHasUnreadMessagesForAdmin(): void
    {
        $conversation = new Conversation(
            "12345",
            "John Doe",
            "john@example.com",
            "Subject test"
        );

        $message = $this->createMock(Message::class);
        $message->method('isRead')->willReturn(false);
        $message->method('getSenderType')->willReturn('student');

        $conversation->addMessage($message);

        $this->assertTrue($conversation->hasUnreadMessagesFor('admin'));
    }

    /**
     * Test detection of unread messages for student.
     */
    public function testHasUnreadMessagesForStudent(): void
    {
        $conversation = new Conversation(
            "12345",
            "John Doe",
            "john@example.com",
            "Subject test"
        );

        $message = $this->createMock(Message::class);
        $message->method('isRead')->willReturn(false);
        $message->method('getSenderType')->willReturn('admin');

        $conversation->addMessage($message);

        $this->assertTrue($conversation->hasUnreadMessagesFor('student'));
    }

    /**
     * Test that no unread messages are reported when all messages are read.
     */
    public function testNoUnreadMessages(): void
    {
        $conversation = new Conversation(
            "12345",
            "John Doe",
            "john@example.com",
            "Subject test"
        );

        $message = $this->createMock(Message::class);
        $message->method('isRead')->willReturn(true);
        $message->method('getSenderType')->willReturn('student');

        $conversation->addMessage($message);

        $this->assertFalse($conversation->hasUnreadMessagesFor('admin'));
    }
}