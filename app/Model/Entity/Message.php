<?php

namespace Model\Entity;

/**
 * Message
 *
 * Represents a single message within a Conversation thread.
 * Tracks the sender type (admin or student), the message content,
 * its read state, and the timestamp at which it was created.
 */
class Message
{
    /** @var int|null Database identifier, null before persistence */
    private ?int $id;

    /** @var int Identifier of the parent conversation */
    private int $conversationId;

    /** @var string Type of sender ('admin' or 'student') */
    private string $senderType;

    /** @var string Text content of the message */
    private string $content;

    /** @var bool Whether the message has been read by the recipient */
    private bool $isRead;

    /** @var \DateTime Date and time the message was created */
    private \DateTime $createdAt;

    /**
     * @param int            $conversationId Identifier of the parent conversation
     * @param string         $senderType     Type of sender ('admin' or 'student')
     * @param string         $content        Text content of the message
     * @param bool           $isRead         Whether the message has already been read (defaults to false)
     * @param int|null       $id             Database identifier (null for new messages)
     * @param \DateTime|null $createdAt      Creation timestamp (defaults to now)
     */
    public function __construct(
        int $conversationId,
        string $senderType,
        string $content,
        bool $isRead = false,
        ?int $id = null,
        ?\DateTime $createdAt = null
    ) {
        $this->id             = $id;
        $this->conversationId = $conversationId;
        $this->senderType     = $senderType;
        $this->content        = $content;
        $this->isRead         = $isRead;
        $this->createdAt      = $createdAt ?? new \DateTime();
    }

    /**
     * Returns the database identifier of the message.
     *
     * @return int|null
     */
    public function getId(): ?int { return $this->id; }

    /**
     * Returns the identifier of the parent conversation.
     *
     * @return int
     */
    public function getConversationId(): int { return $this->conversationId; }

    /**
     * Returns the sender type ('admin' or 'student').
     *
     * @return string
     */
    public function getSenderType(): string { return $this->senderType; }

    /**
     * Returns the text content of the message.
     *
     * @return string
     */
    public function getContent(): string { return $this->content; }

    /**
     * Returns whether the message has been read by the recipient.
     *
     * @return bool
     */
    public function isRead(): bool { return $this->isRead; }

    /**
     * Returns the creation timestamp of the message.
     *
     * @return \DateTime
     */
    public function getCreatedAt(): \DateTime { return $this->createdAt; }

    /**
     * Marks the message as read.
     *
     * @return void
     */
    public function markAsRead(): void
    {
        $this->isRead = true;
    }
}