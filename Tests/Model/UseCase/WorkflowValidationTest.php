<?php

namespace Tests\Model\UseCase;

use Model\Repository\FolderRepositoryInterface;
use Model\UseCase\ManageFolderUseCase;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests du circuit de validation étape par étape :
 * validerEtape(), devaliderEtape() et la protection de setAvisChef().
 *
 * Run with:
 *   ./vendor/bin/phpunit Tests/Model/UseCase/WorkflowValidationTest.php
 */
class WorkflowValidationTest extends TestCase
{
    /** @var FolderRepositoryInterface&MockObject */
    private FolderRepositoryInterface $repo;
    private ManageFolderUseCase $useCase;

    protected function setUp(): void
    {
        $this->repo    = $this->createMock(FolderRepositoryInterface::class);
        $this->useCase = new ManageFolderUseCase($this->repo);
    }

    /** @return array<string, mixed> */
    private function row(int $complet, int $admin, int $coord, ?string $chef = null): array
    {
        return ['NumEtu' => 'E1', 'IsComplete' => $complet, 'valide_admin' => $admin, 'valide_coordinateur' => $coord, 'avis_chef_departement' => $chef];
    }

    // ── validerEtape ─────────────────────────────────────────────

    public function test_admin_marks_folder_complete(): void
    {
        $this->repo->method('findByNumEtu')->willReturn($this->row(0, 0, 0));
        $this->repo->expects($this->once())->method('toggleCompleteStatus')->with('E1')->willReturn(true);

        $this->assertSame(['success' => true, 'error' => null], $this->useCase->validerEtape('E1', 'complet', 'admin'));
    }

    public function test_admin_validates_secretariat_when_complete(): void
    {
        $this->repo->method('findByNumEtu')->willReturn($this->row(1, 0, 0));
        $this->repo->expects($this->once())->method('setValideAdmin')->with('E1', true)->willReturn(true);

        $this->assertTrue($this->useCase->validerEtape('E1', 'secretariat', 'admin')['success']);
    }

    public function test_secretariat_refused_when_folder_incomplete(): void
    {
        $this->repo->method('findByNumEtu')->willReturn($this->row(0, 0, 0));
        $this->repo->expects($this->never())->method('setValideAdmin');

        $this->assertSame('wrong_order', $this->useCase->validerEtape('E1', 'secretariat', 'admin')['error']);
    }

    public function test_coordinator_refused_before_secretariat(): void
    {
        $this->repo->method('findByNumEtu')->willReturn($this->row(1, 0, 0));
        $this->repo->expects($this->never())->method('setValideCoordinateur');

        $this->assertSame('wrong_order', $this->useCase->validerEtape('E1', 'coordinateur', 'coordinateur_etude')['error']);
    }

    public function test_coordinator_validates_after_secretariat(): void
    {
        $this->repo->method('findByNumEtu')->willReturn($this->row(1, 1, 0));
        $this->repo->expects($this->once())->method('setValideCoordinateur')->with('E1', true)->willReturn(true);

        $this->assertTrue($this->useCase->validerEtape('E1', 'coordinateur', 'coordinateur_stage')['success']);
    }

    public function test_already_validated_step(): void
    {
        $this->repo->method('findByNumEtu')->willReturn($this->row(1, 1, 0));
        $this->repo->expects($this->never())->method('setValideAdmin');

        $this->assertSame('already_done', $this->useCase->validerEtape('E1', 'secretariat', 'admin')['error']);
    }

    public function test_admin_cannot_validate_coordinator_step(): void
    {
        $this->repo->expects($this->never())->method('findByNumEtu');
        $this->assertSame('forbidden', $this->useCase->validerEtape('E1', 'coordinateur', 'admin')['error']);
    }

    public function test_coordinator_cannot_validate_secretariat_step(): void
    {
        $this->assertSame('forbidden', $this->useCase->validerEtape('E1', 'secretariat', 'coordinateur')['error']);
    }

    public function test_unknown_action_and_unknown_folder(): void
    {
        $this->assertSame('unknown_action', $this->useCase->validerEtape('E1', 'chef', 'admin')['error']);

        $this->repo->method('findByNumEtu')->willReturn(null);
        $this->assertSame('not_found', $this->useCase->validerEtape('E1', 'secretariat', 'admin')['error']);
    }

    public function test_db_failure_is_reported(): void
    {
        $this->repo->method('findByNumEtu')->willReturn($this->row(1, 0, 0));
        $this->repo->method('setValideAdmin')->willReturn(false);

        $this->assertSame(['success' => false, 'error' => 'db'], $this->useCase->validerEtape('E1', 'secretariat', 'admin'));
    }

    // ── devaliderEtape (remise à zéro en cascade) ────────────────

    public function test_cancel_secretariat_resets_following_steps(): void
    {
        $this->repo->method('findByNumEtu')->willReturn($this->row(1, 1, 1, 'accepte'));
        $this->repo->expects($this->once())->method('setAvisChef')->with('E1', null)->willReturn(true);
        $this->repo->expects($this->once())->method('setValideCoordinateur')->with('E1', false)->willReturn(true);
        $this->repo->expects($this->once())->method('setValideAdmin')->with('E1', false)->willReturn(true);
        $this->repo->expects($this->never())->method('toggleCompleteStatus');

        $this->assertTrue($this->useCase->devaliderEtape('E1', 'secretariat', 'admin')['success']);
    }

    public function test_cancel_coordinator_keeps_secretariat(): void
    {
        $this->repo->method('findByNumEtu')->willReturn($this->row(1, 1, 1));
        $this->repo->expects($this->once())->method('setAvisChef')->with('E1', null)->willReturn(true);
        $this->repo->expects($this->once())->method('setValideCoordinateur')->with('E1', false)->willReturn(true);
        $this->repo->expects($this->never())->method('setValideAdmin');

        $this->assertTrue($this->useCase->devaliderEtape('E1', 'coordinateur', 'coordinateur')['success']);
    }

    public function test_back_to_incomplete_resets_everything(): void
    {
        $this->repo->method('findByNumEtu')->willReturn($this->row(1, 1, 0));
        $this->repo->method('setAvisChef')->willReturn(true);
        $this->repo->method('setValideCoordinateur')->willReturn(true);
        $this->repo->expects($this->once())->method('setValideAdmin')->with('E1', false)->willReturn(true);
        $this->repo->expects($this->once())->method('toggleCompleteStatus')->with('E1')->willReturn(true);

        $this->assertTrue($this->useCase->devaliderEtape('E1', 'complet', 'admin')['success']);
    }

    public function test_cannot_cancel_a_step_not_reached(): void
    {
        $this->repo->method('findByNumEtu')->willReturn($this->row(1, 0, 0));
        $this->repo->expects($this->never())->method('setValideAdmin');

        $this->assertSame('not_validated', $this->useCase->devaliderEtape('E1', 'secretariat', 'admin')['error']);
    }

    // ── setAvisChef protégé ──────────────────────────────────────

    public function test_chef_cannot_decide_before_coordinator(): void
    {
        $this->repo->method('findByNumEtu')->willReturn($this->row(1, 1, 0));
        $this->repo->expects($this->never())->method('setAvisChef');

        $this->assertFalse($this->useCase->setAvisChef('E1', 'accepte'));
    }

    public function test_chef_decides_after_coordinator(): void
    {
        $this->repo->method('findByNumEtu')->willReturn($this->row(1, 1, 1));
        $this->repo->expects($this->once())->method('setAvisChef')->with('E1', 'accepte')->willReturn(true);

        $this->assertTrue($this->useCase->setAvisChef('E1', 'accepte'));
    }

    public function test_chef_refusal_still_sets_global_status(): void
    {
        $this->repo->method('findByNumEtu')->willReturn($this->row(1, 1, 1));
        $this->repo->method('setAvisChef')->willReturn(true);
        $this->repo->expects($this->once())->method('setStatus')->with('E1', 'refuse');

        $this->assertTrue($this->useCase->setAvisChef('E1', 'refuse'));
    }

    public function test_reset_chef_decision_always_allowed(): void
    {
        $this->repo->expects($this->never())->method('findByNumEtu');
        $this->repo->expects($this->once())->method('setAvisChef')->with('E1', null)->willReturn(true);

        $this->assertTrue($this->useCase->setAvisChef('E1', null));
    }
}
