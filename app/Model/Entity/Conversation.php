<?php

namespace Model\Entity;

/**
 * Conversation
 *
 * Represents a support conversation thread between a student and an administrator.
 * Holds the conversation metadata (subject, status, timestamps) and owns a
 * collection of Message objects that form the dialogue.
 */
class Conversation
{
    /** @var int|null Database identifier, null before persistence */
    private ?int $id;

    /** @var string Student number (numéro étudiant) of the conversation owner */
    private string $studentNumEtu;

    /** @var string Display name of the student */
    private string $name;

    /** @var string Email address of the student */
    private string $email;

    /** @var string Subject / title of the conversation */
    private string $subject;

    /** @var string Current status of the conversation (e.g. 'open', 'closed') */
    private string $status;

    /** @var \DateTime Date and time the conversation was created */
    private \DateTime $createdAt;

    /** @var array<int, Message> Ordered list of messages belonging to this conversation */
    private array $messages;

    /**
     * @param string         $studentNumEtu Student number of the conversation owner
     * @param string         $name          Display name of the student
     * @param string         $email         Email address of the student
     * @param string         $subject       Subject / title of the conversation
     * @param string         $status        Initial status (defaults to 'open')
     * @param int|null       $id            Database identifier (null for new conversations)
     * @param \DateTime|null $createdAt     Creation timestamp (defaults to now)
     */
    public function __construct(
        string $studentNumEtu,
        string $name,
        string $email,
        string $subject,
        string $status = 'open',
        ?int $id = null,
        ?\DateTime $createdAt = null
    ) {
        $this->id            = $id;
        $this->studentNumEtu = $studentNumEtu;
        $this->name          = $name;
        $this->email         = $email;
        $this->subject       = $subject;
        $this->status        = $status;
        $this->createdAt     = $createdAt ?? new \DateTime();
        $this->messages      = [];
    }

    // ─── Getters Basiques ──────────────────────────────────────────────

    /**
     * Returns the database identifier of the conversation.
     *
     * @return int|null
     */
    public function getId(): ?int { return $this->id; }

    /**
     * Returns the student number of the conversation owner.
     *
     * @return string
     */
    public function getStudentNumEtu(): string { return $this->studentNumEtu; }

    /**
     * Returns the display name of the student.
     *
     * @return string
     */
    public function getName(): string { return $this->name; }

    /**
     * Returns the email address of the student.
     *
     * @return string
     */
    public function getEmail(): string { return $this->email; }

    /**
     * Returns the subject of the conversation.
     *
     * @return string
     */
    public function getSubject(): string { return $this->subject; }

    /**
     * Returns the current status of the conversation.
     *
     * @return string
     */
    public function getStatus(): string { return $this->status; }

    /**
     * Returns the creation timestamp of the conversation.
     *
     * @return \DateTime
     */
    public function getCreatedAt(): \DateTime { return $this->createdAt; }

    /**
     * Updates the status of the conversation.
     *
     * @param string $status The new status value
     * @return void
     */
    public function setStatus(string $status): void { $this->status = $status; }

    // ─── Gestion des Messages (La relation 1 -> N) ─────────────────────

    /**
     * Appends a message to the conversation's message list.
     *
     * @param Message $message The message to add
     * @return void
     */
    public function addMessage(Message $message): void
    {
        $this->messages[] = $message;
    }

    /**
     * Returns all messages belonging to this conversation.
     *
     * @return array<int, Message>
     */
    public function getMessages(): array
    {
        return $this->messages;
    }

    /**
     * Returns the first message of the conversation, or null if there are none.
     *
     * @return Message|null
     */
    public function getFirstMessage(): ?Message
    {
        return $this->messages[array_key_first($this->messages)] ?? null;
    }

    /**
     * Returns the last message of the conversation, or null if there are none.
     *
     * @return Message|null
     */
    public function getLastMessage(): ?Message
    {
        if (empty($this->messages)) return null;
        return end($this->messages);
    }

    /**
     * Checks whether the conversation contains unread messages for a given role.
     *
     * Determines the expected sender type based on the viewer's role:
     * an admin sees unread messages sent by students, and vice versa.
     *
     * @param string $role The role of the viewer ('admin' or 'student')
     * @return bool True if at least one unread message from the opposite sender exists
     */
    public function hasUnreadMessagesFor(string $role): bool
    {
        $targetSender = $role === 'admin' ? 'student' : 'admin';

        foreach ($this->messages as $msg) {
            if (!$msg->isRead() && $msg->getSenderType() === $targetSender) {
                return true;
            }
        }
        return false;
    }
}