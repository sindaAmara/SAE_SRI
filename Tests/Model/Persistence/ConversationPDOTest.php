<?php

namespace Tests\Model\Persistence;

use Model\Entity\Conversation;
use Model\Persistence\ConversationPDO;
use PDO;
use PDOStatement;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for {@see ConversationPDO}.
 *
 * Coverage areas:
 * - create() — successful insert with transaction, rollback on exception
 * - addMessage() — success and failure cases
 * - findById() — conversation with messages, not-found case
 * - findByStudentNumEtu() — non-empty list, empty list
 * - findAll() — multiple conversations, failed query
 * - findUnreadByRole() — admin filters by student sender, student filters by
 *   admin sender
 * - markAsRead() — admin marks student messages, student marks admin messages
 * - delete() — success and failure cases
 *
 * Strategy: newInstanceWithoutConstructor() + ReflectionProperty injection on
 * the $pdo field so the Database singleton is never touched.
 *
 * Run with:
 *   ./vendor/bin/phpunit Tests/Model/Persistence/ConversationPDOTest.php
 */
class ConversationPDOTest extends TestCase
{
    /** Mocked PDO connection injected directly into the repository. */
    /** @var PDO&MockObject */
    private PDO $pdoMock;

    /** Repository instance created without invoking the real constructor. */
    private ConversationPDO $repo;

    /**
     * Creates the repository via reflection (bypassing the constructor) and
     * injects a PDO mock so no real database connection is opened.
     */
    protected function setUp(): void
    {
        $this->repo = (new \ReflectionClass(ConversationPDO::class))
            ->newInstanceWithoutConstructor();

        $this->pdoMock = $this->createMock(PDO::class);

        $prop = new \ReflectionProperty(ConversationPDO::class, 'pdo');
        $prop->setAccessible(true);
        $prop->setValue($this->repo, $this->pdoMock);
    }

    // -------------------------------------------------------------------------
    // Statement factory helpers
    // -------------------------------------------------------------------------

    /**
     * Builds a mock PDOStatement that returns the given rows one by one via
     * fetch(), then returns false to signal the end of the result set.
     *
     * Use this for any loop that calls fetch() until false is returned.
     *
     * @param array<int, array<string, mixed>> $rows Rows to return in order.
     */
    private function makeFetchStmt(array $rows): PDOStatement
    {
        $stmt    = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $returns = array_merge($rows, [false]); // false terminates the fetch loop
        $stmt->method('fetch')->willReturnOnConsecutiveCalls(...$returns);
        return $stmt;
    }

    // -------------------------------------------------------------------------
    // Row fixtures
    // -------------------------------------------------------------------------

    /**
     * Returns a minimal conversation row for the given ID.
     *
     * @return array<string, mixed>
     */
    private function convRow(int $id): array
    {
        return [
            'id'             => $id,
            'student_numetu' => '12345678',
            'name'           => 'Alice',
            'email'          => 'alice@example.com',
            'subject'        => 'Question',
            'status'         => 'open',
            'created_at'     => '2024-01-15 10:00:00',
        ];
    }

    /**
     * Returns a minimal message row for the given message and conversation IDs.
     *
     * @return array<string, mixed>
     */
    private function msgRow(int $id, int $convId): array
    {
        return [
            'id'              => $id,
            'conversation_id' => $convId,
            'sender_type'     => 'student',
            'content'         => 'Bonjour',
            'is_read'         => 0,
            'created_at'      => '2024-01-15 10:01:00',
        ];
    }

    // =========================================================
    // create()
    // =========================================================

    /**
     * @test
     * create() must open a transaction, execute two INSERTs (conversation then
     * message), commit on success, and return the auto-incremented ID as an
     * integer.
     */
    public function testCreateReturnsInsertedId(): void
    {
        $stmtConv = $this->createMock(PDOStatement::class);
        $stmtMsg  = $this->createMock(PDOStatement::class);

        $this->pdoMock->expects($this->once())->method('beginTransaction');
        $this->pdoMock->expects($this->exactly(2))->method('prepare')
            ->willReturnOnConsecutiveCalls($stmtConv, $stmtMsg);
        $this->pdoMock->expects($this->once())->method('lastInsertId')->willReturn('42');
        $this->pdoMock->expects($this->once())->method('commit');

        $stmtConv->expects($this->once())->method('execute')->willReturn(true);
        $stmtMsg->expects($this->once())->method('execute')->willReturn(true);

        $this->assertSame(42, $this->repo->create('12345678', 'Alice', 'alice@example.com', 'Q', 'Msg'));
    }

    /**
     * @test
     * When an exception is thrown during the INSERT, create() must call
     * rollBack() and re-throw the exception so the caller knows the operation
     * failed.
     */
    public function testCreateRollsBackOnException(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willThrowException(new \Exception('fail'));

        $this->pdoMock->method('beginTransaction');
        $this->pdoMock->method('prepare')->willReturn($stmt);
        $this->pdoMock->expects($this->once())->method('rollBack');

        $this->expectException(\Exception::class);
        $this->repo->create('12345678', 'Alice', 'alice@example.com', 'Q', 'Msg');
    }

    // =========================================================
    // addMessage()
    // =========================================================

    /**
     * @test
     * addMessage() must execute the INSERT with the correct conversation ID,
     * sender type, and message content, and return true on success.
     */
    public function testAddMessageReturnsTrueOnSuccess(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())
            ->method('execute')
            ->with([':conv_id' => 1, ':sender_type' => 'admin', ':content' => 'Rep'])
            ->willReturn(true);
        $this->pdoMock->method('prepare')->willReturn($stmt);

        $this->assertTrue($this->repo->addMessage(1, 'admin', 'Rep'));
    }

    /**
     * @test
     * When the INSERT execution returns false, addMessage() must return false
     * to signal the failure to the caller.
     */
    public function testAddMessageReturnsFalseOnFailure(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(false);
        $this->pdoMock->method('prepare')->willReturn($stmt);

        $this->assertFalse($this->repo->addMessage(99, 'student', 'Hello'));
    }

    // =========================================================
    // findById()
    // =========================================================

    /**
     * @test
     * findById() must return a Conversation entity populated with the correct
     * ID, status, and all associated messages.
     */
    public function testFindByIdReturnsConversationWithMessages(): void
    {
        $stmtConv = $this->makeFetchStmt([$this->convRow(1)]);
        $stmtMsg  = $this->makeFetchStmt([$this->msgRow(1, 1)]);

        $this->pdoMock->expects($this->exactly(2))->method('prepare')
            ->willReturnOnConsecutiveCalls($stmtConv, $stmtMsg);

        $conv = $this->repo->findById(1);

        $this->assertInstanceOf(Conversation::class, $conv);
        $this->assertSame(1,      $conv->getId());
        $this->assertSame('open', $conv->getStatus());
        $this->assertCount(1,     $conv->getMessages());
    }

    /**
     * @test
     * When no row is found for the given ID, findById() must return null.
     */
    public function testFindByIdReturnsNullWhenNotFound(): void
    {
        $stmt = $this->makeFetchStmt([]); // empty result set
        $this->pdoMock->method('prepare')->willReturn($stmt);

        $this->assertNull($this->repo->findById(999));
    }

    // =========================================================
    // findByStudentNumEtu()
    // =========================================================

    /**
     * @test
     * When the student has conversations, findByStudentNumEtu() must return a
     * non-empty list of Conversation entities.
     */
    public function testFindByStudentNumEtuReturnsConversationList(): void
    {
        $stmtConv = $this->makeFetchStmt([$this->convRow(2)]);
        $stmtMsg  = $this->makeFetchStmt([]); // no messages for simplicity

        $this->pdoMock->method('prepare')
            ->willReturnOnConsecutiveCalls($stmtConv, $stmtMsg);

        $list = $this->repo->findByStudentNumEtu('12345678');
        $this->assertCount(1, $list);
        $this->assertInstanceOf(Conversation::class, $list[0]);
    }

    /**
     * @test
     * When the student has no conversations, findByStudentNumEtu() must return
     * an empty array.
     */
    public function testFindByStudentNumEtuReturnsEmptyArray(): void
    {
        $this->pdoMock->method('prepare')->willReturn($this->makeFetchStmt([]));

        $this->assertSame([], $this->repo->findByStudentNumEtu('00000000'));
    }

    // =========================================================
    // findAll()
    // =========================================================

    /**
     * @test
     * findAll() must return a list containing one Conversation entity for each
     * row returned by the database query.
     */
    public function testFindAllReturnsAllConversations(): void
    {
        $stmtAll  = $this->makeFetchStmt([$this->convRow(1), $this->convRow(2)]);
        $stmtMsg1 = $this->makeFetchStmt([]); // messages for conversation 1
        $stmtMsg2 = $this->makeFetchStmt([]); // messages for conversation 2

        $this->pdoMock->expects($this->once())->method('query')->willReturn($stmtAll);
        $this->pdoMock->method('prepare')
            ->willReturnOnConsecutiveCalls($stmtMsg1, $stmtMsg2);

        $this->assertCount(2, $this->repo->findAll());
    }

    /**
     * @test
     * When PDO::query() returns false, findAll() must return an empty array
     * rather than crashing.
     */
    public function testFindAllReturnsEmptyArrayWhenQueryFails(): void
    {
        $this->pdoMock->method('query')->willReturn(false);

        $this->assertSame([], $this->repo->findAll());
    }

    // =========================================================
    // findUnreadByRole()
    // =========================================================

    /**
     * @test
     * When the caller is an admin, findUnreadByRole() must pass "student" as
     * the :sender parameter so that it returns conversations with unread
     * messages sent by students.
     */
    public function testFindUnreadByRoleAdminFiltersStudentSender(): void
    {
        /** @var PDOStatement&MockObject $stmtConv */
        $stmtConv = $this->createMock(PDOStatement::class);
        $stmtConv->method('execute')->willReturn(true);
        $stmtConv->method('fetch')->willReturnOnConsecutiveCalls($this->convRow(1), false);

        $stmtMsg = $this->makeFetchStmt([]); // no messages needed for this assertion

        $this->pdoMock->method('prepare')
            ->willReturnOnConsecutiveCalls($stmtConv, $stmtMsg);

        // The execute call must receive the student sender so admins see
        // messages from students.
        $stmtConv->expects($this->once())
            ->method('execute')
            ->with([':sender' => 'student']);

        $this->assertCount(1, $this->repo->findUnreadByRole('admin'));
    }

    /**
     * @test
     * When the caller is a student, findUnreadByRole() must pass "admin" as
     * the :sender parameter so that it returns conversations with unread
     * messages sent by admins.
     */
    public function testFindUnreadByRoleStudentFiltersAdminSender(): void
    {
        /** @var PDOStatement&MockObject $stmt */
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('fetch')->willReturn(false); // no conversations → empty result

        $this->pdoMock->method('prepare')->willReturn($stmt);

        // The execute call must receive the admin sender so students see
        // replies from admins.
        $stmt->expects($this->once())
            ->method('execute')
            ->with([':sender' => 'admin']);

        $this->assertSame([], $this->repo->findUnreadByRole('student'));
    }

    // =========================================================
    // markAsRead()
    // =========================================================

    /**
     * @test
     * When an admin marks a conversation as read, markAsRead() must bind
     * "student" as the sender type so that student messages are marked, not
     * admin ones.
     */
    public function testMarkAsReadAdminMarksStudentMessages(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())
            ->method('execute')
            ->with([':cid' => 5, ':sender' => 'student'])
            ->willReturn(true);
        $this->pdoMock->method('prepare')->willReturn($stmt);

        $this->assertTrue($this->repo->markAsRead(5, 'admin'));
    }

    /**
     * @test
     * When a student marks a conversation as read, markAsRead() must bind
     * "admin" as the sender type so that admin messages are marked, not student
     * ones.
     */
    public function testMarkAsReadStudentMarksAdminMessages(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())
            ->method('execute')
            ->with([':cid' => 3, ':sender' => 'admin'])
            ->willReturn(true);
        $this->pdoMock->method('prepare')->willReturn($stmt);

        $this->assertTrue($this->repo->markAsRead(3, 'student'));
    }

    // =========================================================
    // delete()
    // =========================================================

    /**
     * @test
     * delete() must execute the DELETE with the conversation ID bound to :id
     * and return true on success.
     */
    public function testDeleteReturnsTrueOnSuccess(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())
            ->method('execute')
            ->with([':id' => 7])
            ->willReturn(true);
        $this->pdoMock->method('prepare')->willReturn($stmt);

        $this->assertTrue($this->repo->delete(7));
    }

    /**
     * @test
     * When the DELETE execution returns false, delete() must return false to
     * signal that no row was removed.
     */
    public function testDeleteReturnsFalseOnFailure(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willReturn(false);
        $this->pdoMock->method('prepare')->willReturn($stmt);

        $this->assertFalse($this->repo->delete(999));
    }
}