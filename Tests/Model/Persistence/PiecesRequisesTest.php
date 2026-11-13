<?php

namespace Tests\Model\Persistence;

use Model\Persistence\FolderRepositoryPDO;
use PHPUnit\Framework\TestCase;

/**
 * Tests de {@see FolderRepositoryPDO::piecesRequises()} : pièces obligatoires
 * selon le type (entrant / sortant) et la mobilité (stage / études).
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
}
