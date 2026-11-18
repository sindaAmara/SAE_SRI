<?php

namespace Model\Entity;

/**
 * WorkflowStats
 *
 * Répartition des dossiers selon les 5 étapes du workflow de validation :
 * incomplet, complet à valider, validé admin (secrétariat), validé admin +
 * coordinateur, et validé par le chef de département.
 */
class WorkflowStats
{
    public function __construct(
        private int $total,
        private int $incomplete,
        private int $awaitingAdmin,
        private int $validatedAdmin,
        private int $validatedAdminAndCoordinateur,
        private int $validatedChefDepartement,
    ) {
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function getIncomplete(): int
    {
        return $this->incomplete;
    }

    public function getAwaitingAdmin(): int
    {
        return $this->awaitingAdmin;
    }

    public function getValidatedAdmin(): int
    {
        return $this->validatedAdmin;
    }

    public function getValidatedAdminAndCoordinateur(): int
    {
        return $this->validatedAdminAndCoordinateur;
    }

    public function getValidatedChefDepartement(): int
    {
        return $this->validatedChefDepartement;
    }

    private function pct(int $part): int
    {
        return $this->total > 0 ? (int) round($part / $this->total * 100) : 0;
    }

    public function getIncompletePercentage(): int
    {
        return $this->pct($this->incomplete);
    }

    public function getAwaitingAdminPercentage(): int
    {
        return $this->pct($this->awaitingAdmin);
    }

    public function getValidatedAdminPercentage(): int
    {
        return $this->pct($this->validatedAdmin);
    }

    public function getValidatedAdminAndCoordinateurPercentage(): int
    {
        return $this->pct($this->validatedAdminAndCoordinateur);
    }

    public function getValidatedChefDepartementPercentage(): int
    {
        return $this->pct($this->validatedChefDepartement);
    }
}