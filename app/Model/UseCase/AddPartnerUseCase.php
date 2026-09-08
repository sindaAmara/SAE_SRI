<?php

namespace Model\UseCase;

use Model\Repository\PartnerRepositoryInterface;
use Model\Entity\Partner;
use PDOException;

/**
 * AddPartnerUseCase
 *
 * Use case responsible for adding a new partner institution to the system.
 * Delegates persistence to the injected repository.
 */
class AddPartnerUseCase
{
    /** @var PartnerRepositoryInterface Repository used to persist partner data */
    private PartnerRepositoryInterface $repository;

    /**
     * @param PartnerRepositoryInterface $repository Repository used to persist partner data
     */
    public function __construct(PartnerRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Persists the given partner institution via the repository.
     *
     * @param Partner $partner The partner entity to add
     * @return void
     */
    public function execute(Partner $partner): void
    {
        $this->repository->addPartner($partner);
    }
}