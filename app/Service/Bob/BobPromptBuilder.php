<?php

namespace Service\Bob;

use DateTimeImmutable;

/**
 * BobPromptBuilder
 *
 * Builds Bob's system prompt from the text template (app/config/bob_prompt.txt)
 * by filling the {{DATE}} and {{DOSSIER}} placeholders.
 */
class BobPromptBuilder
{
    /** @var string Absolute path to the prompt template */
    private string $templatePath;

    /** @var FolderContextFormatter Formats the student's folder */
    private FolderContextFormatter $formatter;

    /**
     * @param string|null                 $templatePath Template path; defaults to app/config/bob_prompt.txt
     * @param FolderContextFormatter|null $formatter    Folder formatter
     */
    public function __construct(?string $templatePath = null, ?FolderContextFormatter $formatter = null)
    {
        $this->templatePath = $templatePath ?? dirname(__DIR__, 2) . '/config/bob_prompt.txt';
        $this->formatter    = $formatter ?? new FolderContextFormatter();
    }

    /**
     * Returns the full system prompt for a student.
     *
     * @param array<string, mixed>|null $folder Student's folder row, or null if none
     * @param DateTimeImmutable|null    $today  Reference date (defaults to today)
     * @return string
     * @throws BobException When the template file cannot be read
     */
    public function build(?array $folder, ?DateTimeImmutable $today = null): string
    {
        $template = is_readable($this->templatePath) ? file_get_contents($this->templatePath) : false;
        if ($template === false) {
            throw new BobException('Prompt du chatbot introuvable', 500);
        }

        $today ??= new DateTimeImmutable('today');

        return strtr($template, [
            '{{DATE}}'    => $today->format('d/m/Y'),
            '{{DOSSIER}}' => $this->formatter->format($folder, $today),
        ]);
    }
}
