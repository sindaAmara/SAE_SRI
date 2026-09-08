<?php

namespace Model\Persistence;

use Model\Entity\Conversation;
use Model\Entity\Message;
use Database;
use PDO;

/**
 * Class ConversationPDO
 * * Handles persistence operations for conversations and associated messages using PDO.
 */
class ConversationPDO
{
    /** @var PDO The database connection instance */
    private PDO $pdo;

    /**
     * ConversationPDO constructor.
     * Initializes the PDO connection via the Database singleton.
     */
    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    /**
     * Creates a new conversation and its initial message within a transaction.
     *
     * @param string $numEtu Student number.
     * @param string $name Student name.
     * @param string $email Student email.
     * @param string $subject Subject of the conversation.
     * @param string $messageContent Content of the first message.
     * @return int The ID of the newly created conversation.
     * @throws \Exception If the transaction fails.
     */
    public function create(string $numEtu, string $name, string $email, string $subject, string $messageContent): int
    {
        $this->pdo->beginTransaction();
        try {
            $sqlConv = "INSERT INTO conversations (student_numetu, name, email, subject, status, created_at) 
                        VALUES (:numetu, :name, :email, :subject, 'open', NOW())";
            $stmtConv = $this->pdo->prepare($sqlConv);
            $stmtConv->execute([
                ':numetu'  => $numEtu,
                ':name'    => $name,
                ':email'   => $email,
                ':subject' => $subject
            ]);

            $conversationId = (int) $this->pdo->lastInsertId();

            $sqlMsg = "INSERT INTO messages (conversation_id, sender_type, content, is_read, created_at) 
                       VALUES (:conv_id, 'student', :content, 0, NOW())";
            $stmtMsg = $this->pdo->prepare($sqlMsg);
            $stmtMsg->execute([
                ':conv_id' => $conversationId,
                ':content' => $messageContent
            ]);

            $this->pdo->commit();
            return $conversationId;

        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Adds a message to an existing conversation.
     *
     * @param int $conversationId The ID of the conversation.
     * @param string $senderType The type of sender ('student' or 'admin').
     * @param string $content The message body.
     * @return bool True on success, false on failure.
     */
    public function addMessage(int $conversationId, string $senderType, string $content): bool
    {
        $sql = "INSERT INTO messages (conversation_id, sender_type, content, is_read, created_at) 
                VALUES (:conv_id, :sender_type, :content, 0, NOW())";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':conv_id'     => $conversationId,
            ':sender_type' => $senderType,
            ':content'     => $content
        ]);
    }

    /**
     * Finds a conversation by its unique identifier.
     *
     * @param int $id Conversation ID.
     * @return Conversation|null The conversation entity or null if not found.
     */
    public function findById(int $id): ?Conversation
    {
        $stmt = $this->pdo->prepare("SELECT * FROM conversations WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!is_array($data)) return null;

        $conversation = $this->hydrateConversation($data);
        $this->attachMessagesToConversation($conversation);
        return $conversation;
    }

    /**
     * Retrieves all conversations belonging to a specific student.
     *
     * @param string $numEtu The student number.
     * @return array<int, Conversation> List of conversations.
     */
    public function findByStudentNumEtu(string $numEtu): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM conversations WHERE student_numetu = :numetu ORDER BY created_at DESC");
        $stmt->execute([':numetu' => $numEtu]);
        return $this->fetchAndHydrateList($stmt);
    }

    /**
     * Retrieves all conversations in the system.
     *
     * @return array<int, Conversation> List of all conversations.
     */
    public function findAll(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM conversations ORDER BY created_at DESC");
        if ($stmt === false) {
            return [];
        }
        return $this->fetchAndHydrateList($stmt);
    }

    /**
     * Finds conversations containing unread messages for a specific role.
     *
     * @param string $role The role of the person reading (admin/student).
     * @return array<int, Conversation> List of conversations with unread messages.
     */
    public function findUnreadByRole(string $role): array
    {
        $targetSender = $role === 'admin' ? 'student' : 'admin';
        $sql = "SELECT DISTINCT c.* FROM conversations c
                JOIN messages m ON c.id = m.conversation_id
                WHERE m.is_read = 0 AND m.sender_type = :sender
                ORDER BY c.created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':sender' => $targetSender]);
        return $this->fetchAndHydrateList($stmt);
    }

    /**
     * Marks all messages from the opposite party as read.
     *
     * @param int $conversationId The conversation ID.
     * @param string $readerRole The role of the user reading the messages.
     * @return bool True on success.
     */
    public function markAsRead(int $conversationId, string $readerRole): bool
    {
        $senderToMark = $readerRole === 'admin' ? 'student' : 'admin';
        $stmt = $this->pdo->prepare("UPDATE messages SET is_read = 1 WHERE conversation_id = :cid AND sender_type = :sender");
        return $stmt->execute([
            ':cid'    => $conversationId,
            ':sender' => $senderToMark
        ]);
    }

    /**
     * Deletes a conversation by its ID.
     *
     * @param int $id Conversation ID.
     * @return bool True on success.
     */
    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM conversations WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Iterates through a statement result to hydrate a list of Conversation entities.
     *
     * @param \PDOStatement $stmt
     * @return array<int, Conversation>
     */
    private function fetchAndHydrateList(\PDOStatement $stmt): array
    {
        $conversations = [];
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (is_array($data)) {
                $conv = $this->hydrateConversation($data);
                $this->attachMessagesToConversation($conv);
                $conversations[] = $conv;
            }
        }
        return $conversations;
    }

    /**
     * Transforms a database row into a Conversation entity.
     *
     * @param array<string, mixed> $data
     * @return Conversation
     */
    private function hydrateConversation(array $data): Conversation
    {
        $createdAt = is_string($data['created_at'] ?? null) ? $data['created_at'] : 'now';

        return new Conversation(
            studentNumEtu: is_scalar($data['student_numetu'] ?? null) ? (string) $data['student_numetu'] : '',
            name:          is_scalar($data['name'] ?? null)           ? (string) $data['name']           : '',
            email:         is_scalar($data['email'] ?? null)          ? (string) $data['email']          : '',
            subject:       is_scalar($data['subject'] ?? null)        ? (string) $data['subject']        : '',
            status:        is_scalar($data['status'] ?? null)         ? (string) $data['status']         : '',
            id:            is_numeric($data['id'] ?? null)            ? (int) $data['id']                : 0,
            createdAt:     new \DateTime($createdAt)
        );
    }

    /**
     * Fetches and attaches all messages to a given Conversation entity.
     *
     * @param Conversation $conversation
     * @return void
     */
    private function attachMessagesToConversation(Conversation $conversation): void
    {
        $stmt = $this->pdo->prepare("SELECT * FROM messages WHERE conversation_id = :cid ORDER BY created_at ASC");
        $stmt->execute([':cid' => $conversation->getId()]);

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (!is_array($row)) continue;

            $msg = new Message(
                conversationId: (int) $row['conversation_id'],
                senderType:     (string) $row['sender_type'],
                content:        (string) $row['content'],
                isRead:         (bool) $row['is_read'],
                id:             (int) $row['id'],
                createdAt:      new \DateTime((string) $row['created_at'])
            );
            $conversation->addMessage($msg);
        }
    }
}