<?php

namespace Tests\Controllers\HomeController;

use Controllers\HomeController\HomeControllerCoordinateur;
use Model\Entity\WorkflowStats;
use Model\Entity\WorkflowStep;
use PHPUnit\Framework\TestCase;

/**
 * Tests des 3 cases « Mes dossiers » de l'accueil coordinateur / chef de département.
 *
 * Run with:
 *   ./vendor/bin/phpunit Tests/Controllers/HomeController/CoordinatorHomeTilesTest.php
 */
class CoordinatorHomeTilesTest extends TestCase
{
    // total, incomplets, à valider secrétariat, validés secrétariat, validés coordinateur, validés chef
    private function stats(): WorkflowStats
    {
        return new WorkflowStats(total: 15, incomplete: 5, awaitingAdmin: 4, validatedAdmin: 3, validatedAdminAndCoordinateur: 2, validatedChefDepartement: 1);
    }

    /**
     * @param array<int, array<string, mixed>> $tiles
     * @return array<int, mixed>
     */
    private function col(array $tiles, string $key): array
    {
        return array_map(fn(array $t) => $t[$key], $tiles);
    }

    public function test_coordinator_awaits_validated_by_admin_office(): void
    {
        $tiles = HomeControllerCoordinateur::buildWorkflowTiles('coordinateur_etude', 'etude', $this->stats());

        $this->assertSame([WorkflowStep::INCOMPLET, WorkflowStep::VALIDE_SECRETARIAT, WorkflowStep::VALIDE_CHEF], $this->col($tiles, 'etape'));
        $this->assertSame([5, 3, 1], $this->col($tiles, 'count'));
        $this->assertSame('coordinateur-etude', $tiles[1]['links'][0]['page']);
        $this->assertCount(1, $tiles[1]['links']);
    }

    public function test_chef_awaits_validated_by_coordinator(): void
    {
        $tiles = HomeControllerCoordinateur::buildWorkflowTiles('chef_departement', null, $this->stats());

        $this->assertSame([WorkflowStep::INCOMPLET, WorkflowStep::VALIDE_COORDINATEUR, WorkflowStep::VALIDE_CHEF], $this->col($tiles, 'etape'));
        $this->assertSame([5, 2, 1], $this->col($tiles, 'count'));
        $this->assertSame('chef-departement', $tiles[0]['links'][0]['page']);
    }

    public function test_internship_coordinator_links_to_internship_list(): void
    {
        $tiles = HomeControllerCoordinateur::buildWorkflowTiles('coordinateur_stage', 'stage', $this->stats());
        $this->assertSame('coordinateur-stage', $tiles[0]['links'][0]['page']);
    }

    public function test_coordinator_of_both_types_gets_two_links_without_filter(): void
    {
        $tiles = HomeControllerCoordinateur::buildWorkflowTiles('coordinateur', null, $this->stats());
        $this->assertSame(['coordinateur-etude', 'coordinateur-stage'], array_column($tiles[1]['links'], 'page'));

        $tiles = HomeControllerCoordinateur::buildWorkflowTiles('coordinateur', 'stage', $this->stats());
        $this->assertSame(['coordinateur-stage'], array_column($tiles[1]['links'], 'page'));
    }
}
