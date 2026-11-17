<?php

namespace Tests\Model\Persistence;

use Model\Persistence\FolderRepositoryPDO;
use PHPUnit\Framework\TestCase;

/**
 * Tests de {@see FolderRepositoryPDO::piecesRequises()} : pièces obligatoires
 * selon le type (entrant / sortant) et la mobilité (stage / études),
 * et de {@see FolderRepositoryPDO::analyserDocuments()} (SQLite en mémoire).
 *
 * Run with:
 *   ./vendor/bin/phpunit Tests/Model/Persistence/PiecesRequisesTest.php
 */
class PiecesRequisesTest extends TestCase
{
    public function test_sortant_stage_requires_convention(): void
    {
        $this->assertSame(['photo', 'cv', 'convention', 'langues'], FolderRepositoryPDO::piecesRequises('sortant', 'stage', []));
    }

    public function test_sortant_etudes_does_not_require_convention(): void
    {
        $this->assertSame(['photo', 'cv', 'langues'], FolderRepositoryPDO::piecesRequises('sortant', 'etude', []));
        $this->assertSame(['photo', 'cv', 'langues'], FolderRepositoryPDO::piecesRequises('Sortant', ' Etudes ', []));
    }

    public function test_sortant_etudes_with_uploaded_convention_still_not_required(): void
    {
        $this->assertSame(['photo', 'cv', 'langues'], FolderRepositoryPDO::piecesRequises('sortant', 'etude', ['convention' => 'base64...']));
    }

    public function test_sortant_unknown_mobility_keeps_convention(): void
    {
        $this->assertSame(['photo', 'cv', 'convention', 'langues'], FolderRepositoryPDO::piecesRequises('sortant', null, []));
    }

    public function test_entrant_requires_motivation_letter(): void
    {
        $this->assertSame(['photo', 'cv', 'lettre_motivation', 'langues'], FolderRepositoryPDO::piecesRequises('entrant', 'etude', []));
    }

    public function test_unknown_type_falls_back_on_uploaded_documents(): void
    {
        $this->assertSame(['photo', 'cv', 'convention', 'langues'], FolderRepositoryPDO::piecesRequises(null, null, ['convention' => 'x']));
        $this->assertSame(['photo', 'cv', 'lettre_motivation', 'langues'], FolderRepositoryPDO::piecesRequises('', null, ['lettre_motivation' => 'x']));
        $this->assertSame(['photo', 'cv', 'convention', 'lettre_motivation', 'langues'], FolderRepositoryPDO::piecesRequises(null, null, []));
    }

    // ── analyserDocuments() ─────────────────────────────────────

    /**
     * Repository branché sur une base SQLite en mémoire contenant un seul dossier.
     *
     * @param array<string, mixed> $pieces
     * @param array<string, string> $statuts
     */
    private function repoWith(string $type, string $mobilite, array $pieces, array $statuts): FolderRepositoryPDO
    {
        if (!in_array('sqlite', \PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('pdo_sqlite non disponible');
        }
        $pdo = new \PDO('sqlite::memory:');
        $pdo->exec('CREATE TABLE dossiers (NumEtu TEXT, PiecesJustificatives TEXT, StatutDocuments TEXT, Type TEXT, Mobilite TEXT)');
        $pdo->prepare('INSERT INTO dossiers VALUES (?, ?, ?, ?, ?)')
            ->execute(['E1', json_encode($pieces), json_encode($statuts), $type, $mobilite]);

        $repo = (new \ReflectionClass(FolderRepositoryPDO::class))->newInstanceWithoutConstructor();
        $prop = new \ReflectionProperty(FolderRepositoryPDO::class, 'db');
        $prop->setAccessible(true);
        $prop->setValue($repo, $pdo);
        return $repo;
    }

    public function test_status_stored_only_in_pieces_is_taken_into_account(): void
    {
        // Cas réel : CV « Acceptée » à l'écran, mais absent de StatutDocuments
        $repo = $this->repoWith('sortant', 'stage', ['cv' => ['file' => 'x', 'status' => 'accepted', 'comment' => '']], []);
        $docs = $repo->analyserDocuments('E1');

        $this->assertSame('accepted', $docs['statuts']['cv']);
        $this->assertSame(['cv'], $docs['presents']);
        $this->assertSame(['photo', 'convention', 'langues'], $docs['manquants']);
    }

    public function test_statutDocuments_has_priority_over_piece_status(): void
    {
        $repo = $this->repoWith('sortant', 'stage', ['cv' => ['file' => 'x', 'status' => 'accepted']], ['cv' => 'refused']);
        $this->assertSame('refused', $repo->analyserDocuments('E1')['statuts']['cv']);
    }

    public function test_etudes_folder_does_not_list_convention_as_missing(): void
    {
        $repo = $this->repoWith('sortant', 'etude', ['photo' => 'x'], []);
        $this->assertSame(['cv', 'langues'], $repo->analyserDocuments('E1')['manquants']);
    }
}
