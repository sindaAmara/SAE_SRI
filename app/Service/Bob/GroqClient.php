<?php

namespace Service\Bob;

/**
 * GroqClient
 *
 * Calls the Groq chat completions API (OpenAI-compatible format).
 * Only class that knows the endpoint, the API key and the model.
 */
class GroqClient implements LlmClientInterface
{
    /** @var string Groq chat completions endpoint */
    private const ENDPOINT = 'https://api.groq.com/openai/v1/chat/completions';

    /** @var string API key (read from GROQ_API_KEY by default) */
    private string $apiKey;

    /** @var string Model identifier */
    private string $model;

    /** @var int Maximum tokens for the reply (gpt-oss reasons first, 1000 could cut the answer) */
    private int $maxTokens;

    /** @var int Request timeout in seconds */
    private int $timeout;

    /**
     * @param string|null $apiKey    API key; defaults to $_ENV['GROQ_API_KEY']
     * @param string      $model     Model identifier
     * @param int         $maxTokens Maximum tokens for the reply
     * @param int         $timeout   Request timeout in seconds
     */
    public function __construct(
        ?string $apiKey = null,
        string $model = 'openai/gpt-oss-120b',
        int $maxTokens = 2048,
        int $timeout = 30
    ) {
        $envKey          = $_ENV['GROQ_API_KEY'] ?? '';
        $this->apiKey    = $apiKey ?? (is_string($envKey) ? $envKey : '');
        $this->model     = $model;
        $this->maxTokens = $maxTokens;
        $this->timeout   = $timeout;
    }

    /**
     * {@inheritDoc}
     */
    public function chat(string $systemPrompt, array $messages): string
    {
        if ($this->apiKey === '') {
            throw new BobException('Clé API manquante', 500);
        }

        $payload = json_encode([
            'model'      => $this->model,
            'max_tokens' => $this->maxTokens,
            'messages'   => array_merge([['role' => 'system', 'content' => $systemPrompt]], $messages),
        ], JSON_INVALID_UTF8_SUBSTITUTE);

        $ch = curl_init(self::ENDPOINT);
        if ($ch === false || $payload === false) {
            throw new BobException('Erreur API Groq (init)', 502);
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
            ],
            CURLOPT_TIMEOUT        => $this->timeout,
        ]);

        $response  = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($httpCode !== 200 || !is_string($response)) {
            error_log('Groq error ' . $httpCode . ': ' . ($curlError !== '' ? $curlError : (string) $response));
            throw new BobException('Erreur API Groq (' . $httpCode . ')', 502);
        }

        return $this->extractReply($response);
    }

    /**
     * Extracts the assistant's text from a Groq JSON response.
     *
     * @param string $response Raw JSON response body
     * @return string The reply, or an empty string if absent
     */
    private function extractReply(string $response): string
    {
        $data  = json_decode($response, true);
        $reply = is_array($data) && is_array($data['choices'] ?? null) && is_array($data['choices'][0] ?? null)
            && is_array($data['choices'][0]['message'] ?? null)
            ? ($data['choices'][0]['message']['content'] ?? '')
            : '';

        if (!is_string($reply) || $reply === '') {
            error_log('Groq empty reply: ' . $response);
            return '';
        }

        return $reply;
    }
}
