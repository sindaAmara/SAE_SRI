<?php

namespace Model\UseCase;

use Model\Persistence\FolderRepositoryPDO;
use Model\Repository\FolderRepositoryInterface;
use Service\Bob\BobException;
use Service\Bob\BobPromptBuilder;
use Service\Bob\GroqClient;
use Service\Bob\LlmClientInterface;
use Service\Bob\MessageSanitizer;
use Throwable;

/**
 * AskBobUseCase
 *
 * Use case "a student asks Bob a question":
 * cleans the history, loads the student's folder, builds the prompt
 * with it and asks the language model.
 */
class AskBobUseCase
{
    /** @var FolderRepositoryInterface Repository used to read the student's folder */
    private FolderRepositoryInterface $folderRepo;

    /** @var LlmClientInterface Language-model client */
    private LlmClientInterface $llm;

    /** @var MessageSanitizer Cleans the history sent by the browser */
    private MessageSanitizer $sanitizer;

    /** @var BobPromptBuilder Builds the system prompt */
    private BobPromptBuilder $promptBuilder;

    /**
     * @param FolderRepositoryInterface|null $folderRepo    Defaults to FolderRepositoryPDO
     * @param LlmClientInterface|null        $llm           Defaults to GroqClient
     * @param MessageSanitizer|null          $sanitizer     Defaults to MessageSanitizer
     * @param BobPromptBuilder|null          $promptBuilder Defaults to BobPromptBuilder
     */
    public function __construct(
        ?FolderRepositoryInterface $folderRepo = null,
        ?LlmClientInterface $llm = null,
        ?MessageSanitizer $sanitizer = null,
        ?BobPromptBuilder $promptBuilder = null
    ) {
        $this->folderRepo    = $folderRepo ?? new FolderRepositoryPDO();
        $this->llm           = $llm ?? new GroqClient();
        $this->sanitizer     = $sanitizer ?? new MessageSanitizer();
        $this->promptBuilder = $promptBuilder ?? new BobPromptBuilder();
    }

    /**
     * Returns Bob's reply for the given student and conversation.
     *
     * @param string $numetu      Student number of the logged-in student
     * @param mixed  $rawMessages "messages" field of the request body
     * @return string Bob's reply
     * @throws BobException When the request is invalid or the model is unreachable
     */
    public function execute(string $numetu, mixed $rawMessages): string
    {
        $messages = $this->sanitizer->sanitize($rawMessages);
        if ($messages === []) {
            throw new BobException('Requête invalide', 400);
        }

        $prompt = $this->promptBuilder->build($this->loadFolder($numetu));

        return $this->llm->chat($prompt, $messages);
    }

    /**
     * Loads the student's folder; a database failure must not break the chat.
     *
     * @param string $numetu Student number
     * @return array<string, mixed>|null
     */
    private function loadFolder(string $numetu): ?array
    {
        try {
            return $this->folderRepo->findByNumEtu($numetu);
        } catch (Throwable $e) {
            error_log('Bob folder error: ' . $e->getMessage());
            return null;
        }
    }
}