<?php

namespace Service\Bob;

/**
 * LlmClientInterface
 *
 * Contract for any language-model provider used by Bob (Groq today).
 * Allows the use case to be tested with a mock instead of a real HTTP call.
 */
interface LlmClientInterface
{
    /**
     * Sends a conversation to the model and returns its reply.
     *
     * @param string $systemPrompt System instructions for the model
     * @param array<int, array{role: string, content: string}> $messages Conversation history (user/assistant)
     * @return string The model's reply (may be empty)
     * @throws BobException When the provider is unreachable or misconfigured
     */
    public function chat(string $systemPrompt, array $messages): string;
}
