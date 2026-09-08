<?php

namespace Service;

use Model\Entity\Conversation;
use Model\Persistence\ConversationPDO;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class ContactServiceTest
 *
 * Unit tests for the ContactService class.
 *
 * These tests verify the interaction between the service layer and the
 * ConversationPDO repository, including conversation creation, message addition,
 * retrieval, marking as read, and deletion.
 */
class ContactServiceTest extends TestCase
{
    /**
     * @var ConversationPDO&MockObject Mocked repository for conversations.
     */
    private ConversationPDO $repositoryMock;

    /** @var ContactService The service under test. */
    private ContactService $service;

    /**
     * Setup before each test: create a repository mock and the service instance.
     */
    protected function setUp(): void
    {
        $this->repositoryMock = $this->createMock(ConversationPDO::class);
        $this->service        = new ContactService($this->repositoryMock);
    }

    // -------------------------------------------------------------------------
    // Conversation creation
    // -------------------------------------------------------------------------

    /**
     * Test that createConversation calls the repository and returns the ID.
     */
    public function testCreateConversationSuccess(): void
    {
        $this->repositoryMock
            ->expects($this->once())
            ->method('create')
            ->with('12345', 'John Doe', 'john@test.com', 'Subject', 'Message')
            ->willReturn(1);

        $result = $this->service->createConversation(
            '12345',
            'John Doe',
            'john@test.com',
            'Subject',
            'Message'
        );

        $this->assertEquals(1, $result);
    }

    // -------------------------------------------------------------------------
    // Message addition
    // -------------------------------------------------------------------------

    /**
     * Test adding a message to a conversation that does not exist returns false.
     */
    public function testAddMessageConversationNotFound(): void
    {
        $this->repositoryMock
            ->method('findById')
            ->willReturn(null);

        $result = $this->service->addMessage(1, 'student', 'Hello');

        $this->assertFalse($result);
    }

    /**
     * Test adding a message to an existing conversation succeeds.
     */
    public function testAddMessageSuccess(): void
    {
        $conversationMock = $this->createMock(Conversation::class);

        $this->repositoryMock
            ->method('findById')
            ->willReturn($conversationMock);

        $this->repositoryMock
            ->method('addMessage')
            ->willReturn(true);

        $result = $this->service->addMessage(1, 'student', 'Hello');

        $this->assertTrue($result);
    }

    // -------------------------------------------------------------------------
    // Retrieval methods
    // -------------------------------------------------------------------------

    /**
     * Test getting conversations for a student by their number.
     */
    public function testGetStudentConversations(): void
    {
        $expected = [];

        $this->repositoryMock
            ->expects($this->once())
            ->method('findByStudentNumEtu')
            ->with('12345')
            ->willReturn($expected);

        $result = $this->service->getStudentConversations('12345');

        $this->assertEquals($expected, $result);
    }

    /**
     * Test retrieving all conversations.
     */
    public function testGetAllConversations(): void
    {
        $expected = [];

        $this->repositoryMock
            ->expects($this->once())
            ->method('findAll')
            ->willReturn($expected);

        $result = $this->service->getAllConversations();

        $this->assertEquals($expected, $result);
    }

    // -------------------------------------------------------------------------
    // Mark as read
    // -------------------------------------------------------------------------

    /**
     * Test marking a conversation as read for a given user role.
     */
    public function testMarkConversationAsRead(): void
    {
        $this->repositoryMock
            ->expects($this->once())
            ->method('markAsRead')
            ->with(1, 'admin')
            ->willReturn(true);

        $result = $this->service->markConversationAsRead(1, 'admin');

        $this->assertTrue($result);
    }

    // -------------------------------------------------------------------------
    // Deletion
    // -------------------------------------------------------------------------

    /**
     * Test deleting a conversation by its ID.
     */
    public function testDeleteConversation(): void
    {
        $this->repositoryMock
            ->expects($this->once())
            ->method('delete')
            ->with(1)
            ->willReturn(true);

        $result = $this->service->deleteConversation(1);

        $this->assertTrue($result);
    }
}