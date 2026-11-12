<?php

namespace Service\Bob;

/**
 * MessageSanitizer
 *
 * Cleans the conversation history sent by the browser before it reaches the model:
 * only non-empty "user" / "assistant" messages are kept, so a fake "system"
 * message injected from the browser console is ignored.
 */
class MessageSanitizer
{
    /** @var int Maximum number of messages kept (most recent) */
    private const MAX_MESSAGES = 20;

    /** @var int Maximum length of a single message */
    private const MAX_LENGTH = 2000;

    /** @var array<int, string> Roles accepted from the browser */
    private const ALLOWED_ROLES = ['user', 'assistant'];

    /**
     * Returns the cleaned history.
     *
     * @param mixed $raw Decoded "messages" field from the request body
     * @return array<int, array{role: string, content: string}>
     */
    public function sanitize(mixed $raw): array
    {
        if (!is_array($raw)) {
            return [];
        }

        $clean = [];
        foreach (array_slice($raw, -self::MAX_MESSAGES) as $message) {
            if (!is_array($message)) {
                continue;
            }

            $role    = $message['role'] ?? null;
            $content = $message['content'] ?? null;

            if (!is_string($role) || !in_array($role, self::ALLOWED_ROLES, true)) {
                continue;
            }
            if (!is_string($content) || trim($content) === '') {
                continue;
            }

            $clean[] = ['role' => $role, 'content' => mb_substr($content, 0, self::MAX_LENGTH)];
        }

        return $clean;
    }
}
