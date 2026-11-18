<?php

namespace Tests\Model\Entity;

use Model\Entity\WorkflowStep;
use PHPUnit\Framework\TestCase;

/**
 * Tests de {@see WorkflowStep} : calcul de l'étape d'un dossier.
 *
 * Run with:
 *   ./vendor/bin/phpunit Tests/Model/Entity/WorkflowStepTest.php
 */
class WorkflowStepTest extends TestCase
{
    /** @return array<string, array{0: array<string, mixed>, 1: string}> */
    public static function rowsProvider(): array
    {
        return [
            'incomplet'                       => [['IsComplete' => 0, 'valide_admin' => 0, 'valide_coordinateur' => 0, 'avis_chef_departement' => null], WorkflowStep::INCOMPLET],
            'IsComplete NULL = incomplet'     => [['IsComplete' => null], WorkflowStep::INCOMPLET],
            'complet, à valider'              => [['IsComplete' => 1, 'valide_admin' => 0, 'valide_coordinateur' => 0], WorkflowStep::A_VALIDER],
            'validé secrétariat'              => [['IsComplete' => 1, 'valide_admin' => 1, 'valide_coordinateur' => 0], WorkflowStep::VALIDE_SECRETARIAT],
            'validé coordinateur'             => [['IsComplete' => 1, 'valide_admin' => 1, 'valide_coordinateur' => 1, 'avis_chef_departement' => null], WorkflowStep::VALIDE_COORDINATEUR],
            'chef a refusé → reste étape 4'   => [['IsComplete' => 1, 'valide_admin' => 1, 'valide_coordinateur' => 1, 'avis_chef_departement' => 'refuse'], WorkflowStep::VALIDE_COORDINATEUR],
            'validé chef'                     => [['IsComplete' => 1, 'valide_admin' => 1, 'valide_coordinateur' => 1, 'avis_chef_departement' => 'accepte'], WorkflowStep::VALIDE_CHEF],
            // Cas présent dans la BD actuelle : avis chef sans validation secrétariat
            'avis chef sans secrétariat'      => [['IsComplete' => 1, 'valide_admin' => 0, 'valide_coordinateur' => 0, 'avis_chef_departement' => 'accepte'], WorkflowStep::A_VALIDER],
            'valide_admin mais incomplet'     => [['IsComplete' => 0, 'valide_admin' => 1, 'valide_coordinateur' => 1, 'avis_chef_departement' => 'accepte'], WorkflowStep::INCOMPLET],
            'valeurs en chaîne (PDO)'         => [['IsComplete' => '1', 'valide_admin' => '1', 'valide_coordinateur' => '0'], WorkflowStep::VALIDE_SECRETARIAT],
        ];
    }

    /**
     * @dataProvider rowsProvider
     * @param array<string, mixed> $row
     */
    public function test_fromRow(array $row, string $expected): void
    {
        $this->assertSame($expected, WorkflowStep::fromRow($row));
    }

    public function test_order_and_index(): void
    {
        $this->assertCount(5, WorkflowStep::ORDER);
        $this->assertSame(0, WorkflowStep::index(WorkflowStep::INCOMPLET));
        $this->assertSame(4, WorkflowStep::index(WorkflowStep::VALIDE_CHEF));
        $this->assertSame(-1, WorkflowStep::index('nimporte'));
    }

    public function test_isValid_rejects_unknown_codes(): void
    {
        $this->assertTrue(WorkflowStep::isValid(WorkflowStep::A_VALIDER));
        $this->assertFalse(WorkflowStep::isValid("a_valider' OR 1=1 --"));
        $this->assertFalse(WorkflowStep::isValid(''));
    }

    public function test_every_step_has_a_label(): void
    {
        foreach (WorkflowStep::ORDER as $step) {
            $this->assertArrayHasKey($step, WorkflowStep::LABELS);
        }
    }

    /**
     * Le CASE SQL et fromRow() doivent donner le même résultat : on exécute
     * le CASE sur SQLite en mémoire pour chaque cas du provider.
     */
    public function test_sqlCase_matches_fromRow(): void
    {
        if (!in_array('sqlite', \PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('pdo_sqlite non disponible');
        }
        $pdo = new \PDO('sqlite::memory:');
        $pdo->exec('CREATE TABLE dossiers (IsComplete INT, valide_admin INT, valide_coordinateur INT, avis_chef_departement TEXT)');
        $insert = $pdo->prepare('INSERT INTO dossiers VALUES (?, ?, ?, ?)');

        foreach (self::rowsProvider() as $name => [$row, $expected]) {
            $pdo->exec('DELETE FROM dossiers');
            $insert->execute([$row['IsComplete'] ?? null, $row['valide_admin'] ?? null, $row['valide_coordinateur'] ?? null, $row['avis_chef_departement'] ?? null]);
            $stmt = $pdo->query('SELECT ' . WorkflowStep::sqlCase() . ' FROM dossiers');
            $this->assertNotFalse($stmt);
            $sqlStep = $stmt->fetchColumn();
            $this->assertSame($expected, $sqlStep, "Cas : $name");
        }
    }
}
