<?php

namespace Tests\Service\Bob;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Service\Bob\FolderContextFormatter;

/**
 * Unit tests for {@see FolderContextFormatter}.
 *
 * Coverage areas:
 * - no folder — explicit message
 * - status, mobility type, destination labels
 * - deadline — days left, overdue, hidden countdown when accepted
 * - per-document statuses from StatutDocuments JSON
 * - department head opinion, admin validation, admin comment
 * - base64 supporting documents are never included
 *
 * Run with:
 *   ./vendor/bin/phpunit Tests/Service/Bob/FolderContextFormatterTest.php
 */
class FolderContextFormatterTest extends TestCase
{
    private FolderContextFormatter $formatter;

    private DateTimeImmutable $today;

    protected function setUp(): void
    {
        $this->formatter = new FolderContextFormatter();
        $this->today     = new DateTimeImmutable('2026-11-12');
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function folder(array $overrides = []): array
    {
        return array_merge([
            'status'                => 'depot',
            'Mobilite'              => 'etude',
            'Destination'           => 'Universität Hamburg',
            'Pays'                  => 'Allemagne',
            'Continent'             => 'Europe',
            'DateLimite'            => '2026-12-01',
            'StatutDocuments'       => '{"photo":"pending","cv":"refused","lettre_motivation":"accepted"}',
            'CommentaireAdmin'      => 'Dossier incomplet',
            'avis_chef_departement' => null,
            'valide_admin'          => 0,
            'PiecesJustificatives'  => '{"cv":{"file":"JVBERi0xLjUKJdDUxdgK"}}',
            'ModifieLe'             => '2026-03-10 09:00:00',
        ], $overrides);
    }

    public function testNoFolder(): void
    {
        $this->assertStringContainsString('Aucun dossier', $this->formatter->format(null, $this->today));
    }

    public function testMainFields(): void
    {
        $text = $this->formatter->format($this->folder(), $this->today);

        $this->assertStringContainsString("Type de mobilité : mobilité d'études", $text);
        $this->assertStringContainsString('Destination : Universität Hamburg (Allemagne, Europe)', $text);
        $this->assertStringContainsString('Statut du dossier : en cours de dépôt', $text);
        $this->assertStringContainsString('Avis du chef de département : pas encore donné', $text);
        $this->assertStringContainsString("Validation finale par l'administration : pas encore", $text);
        $this->assertStringContainsString("Commentaire de l'administration : Dossier incomplet", $text);
    }

    public function testDocuments(): void
    {
        $text = $this->formatter->format($this->folder(), $this->today);

        $this->assertStringContainsString('Photo : déposé, en attente de vérification', $text);
        $this->assertStringContainsString('CV : refusé, à corriger et redéposer', $text);
        $this->assertStringContainsString('Lettre motivation : validé', $text);
    }

    public function testSupportingDocumentsContentIsNeverIncluded(): void
    {
        $text = $this->formatter->format($this->folder(), $this->today);

        $this->assertStringNotContainsString('JVBERi0', $text);
    }

    public function testDeadlineDaysLeft(): void
    {
        $text = $this->formatter->format($this->folder(), $this->today);

        $this->assertStringContainsString('Date limite : 01/12/2026 (il reste 19 jour(s))', $text);
    }

    public function testDeadlineOverdue(): void
    {
        $text = $this->formatter->format($this->folder(['DateLimite' => '2026-11-02']), $this->today);

        $this->assertStringContainsString('date dépassée depuis 10 jour(s)', $text);
    }

    public function testNoCountdownWhenAccepted(): void
    {
        $text = $this->formatter->format(
            $this->folder(['status' => 'accepte', 'DateLimite' => '2026-03-20', 'avis_chef_departement' => 'accepte']),
            $this->today
        );

        $this->assertStringContainsString('Date limite : 20/03/2026', $text);
        $this->assertStringNotContainsString('dépassée', $text);
        $this->assertStringContainsString('Avis du chef de département : favorable', $text);
    }

    public function testDestinationFallsBackToCountry(): void
    {
        $text = $this->formatter->format(
            $this->folder(['Destination' => null, 'Continent' => null]),
            $this->today
        );

        $this->assertStringContainsString('Destination : Allemagne', $text);
    }

    public function testInvalidJsonDocumentsAreIgnored(): void
    {
        $text = $this->formatter->format($this->folder(['StatutDocuments' => 'not json']), $this->today);

        $this->assertStringNotContainsString('Documents :', $text);
    }
}
