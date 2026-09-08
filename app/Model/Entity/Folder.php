<?php

namespace Model\Entity;

/**
 * Folder
 *
 * Represents a student application folder (dossier).
 * Tracks the folder's unique identifier and its completion state.
 */
class Folder
{
    /** @var int Unique identifier of the folder */
    private int $id;

    /** @var bool Whether the folder has been fully completed */
    private bool $isComplete;

    /**
     * @param int  $id         Unique identifier of the folder
     * @param bool $isComplete Whether the folder has been fully completed
     */
    public function __construct(int $id, bool $isComplete)
    {
        $this->id = $id;
        $this->isComplete = $isComplete;
    }

    /**
     * Returns the unique identifier of the folder.
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Returns whether the folder has been fully completed.
     *
     * @return bool
     */
    public function isComplete(): bool
    {
        return $this->isComplete;
    }
}