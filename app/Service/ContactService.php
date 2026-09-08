<?php

namespace Service;

use Model\Entity\Conversation;
use Model\Persistence\ConversationPDO;
use Service\Email\EmailReminderService;

/**
 * Service managing contact-related operations.
 * Handles conversation creation, message management, and notification triggering.
 */
class ContactService
{
    /** @var ConversationPDO The persistence repository */
    private ConversationPDO $repository;

    /**
     * @param ConversationPDO $repository
     */
    public function __construct(ConversationPDO $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Creates a new conversation and validates input data.
     * * @param string $studentNumEtu Student number.
     * @param string $name Display name.
     * @param string $email Valid email address.
     * @param string $subject Message subject.
     * @param string $initialMessage Content of the first message.
     * @return int ID of the created conversation.
     * @throws \InvalidArgumentException if validation fails.
     */
    public function createConversation(string $studentNumEtu, string $name, string $email, string $subject, string $initialMessage): int 
    {
        $this->validateEmail($email);
        $this->validateRequired($name, 'name');
        $this->validateRequired($subject, 'subject');
        $this->validateRequired($initialMessage, 'message');

        return $this->repository->create($studentNumEtu, $name, $email, $subject, $initialMessage);
    }

    /**
     * Adds a message to a conversation and sends an email notification to the other party.
     * * @param int $conversationId
     * @param string $senderType 'admin' or 'student'.
     * @param string $content Message body.
     * @return bool True on success.
     */
    public function addMessage(int $conversationId, string $senderType, string $content): bool
    {
        $this->validateRequired($content, 'content');
        $conversation = $this->repository->findById($conversationId);
        if (!$conversation) return false;

        $saved = $this->repository->addMessage($conversationId, $senderType, $content);

        if ($saved) {
            error_log("📧 DEBUG: Message saved, sending notification. Sender: {$senderType}");
            
            if ($senderType === 'admin') {
                $recipientEmail = $conversation->getEmail();
                $recipientName = $conversation->getName();
                $senderName = 'Service Relations Internationales';
                $platformLink = 'https://ri-amu.app/index.php?page=contact-student';
                
                EmailReminderService::sendMessageNotification(
                    $recipientEmail,
                    $recipientName,
                    $senderName,
                    $content,
                    $platformLink
                );
                
            } elseif ($senderType === 'student') {
                $adminEmail = 'relations.internationales@univ-amu.fr';
                $recipientName = 'Administrateur';
                $senderName = $conversation->getName();
                $platformLink = 'https://ri-amu.app/index.php?page=messages-admin';
                
                EmailReminderService::sendMessageNotification(
                    $adminEmail,
                    $recipientName,
                    $senderName,
                    $content,
                    $platformLink
                );
            }
            
            usleep(500000); // 0.5 second delay
        }

        return $saved;
    }

    /**
     * @param string $numEtu
     * @return array<int, Conversation>
     */
    public function getStudentConversations(string $numEtu): array
    {
        return $this->repository->findByStudentNumEtu($numEtu);
    }

    /**
     * @return array<int, Conversation>
     */
    public function getAllConversations(): array
    {
        return $this->repository->findAll();
    }

    /**
     * @param string $role 'admin' or 'student'.
     * @return array<int, Conversation>
     */
    public function getUnreadConversations(string $role): array
    {
        return $this->repository->findUnreadByRole($role);
    }

    /**
     * @param int $id
     * @return Conversation|null
     */
    public function getConversationById(int $id): ?Conversation
    {
        return $this->repository->findById($id);
    }

    /**
     * @param int $conversationId
     * @param string $readerRole
     * @return bool
     */
    public function markConversationAsRead(int $conversationId, string $readerRole): bool
    {
        return $this->repository->markAsRead($conversationId, $readerRole);
    }

    /**
     * @param int $id
     * @return bool
     */
    public function deleteConversation(int $id): bool
    {
        return $this->repository->delete($id);
    }

    /**
     * @param string $email
     * @throws \InvalidArgumentException
     */
    private function validateEmail(string $email): void
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new \InvalidArgumentException('Invalid email');
    }

    /**
     * @param string $value
     * @param string $fieldName
     * @throws \InvalidArgumentException
     */
    private function validateRequired(string $value, string $fieldName): void
    {
        if (empty(trim($value))) throw new \InvalidArgumentException("Field '$fieldName' is required");
    }
}