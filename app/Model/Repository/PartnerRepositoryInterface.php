<?php

namespace Model\Repository;

use Model\Entity\Partner;

/**
 * Interface PartnerRepositoryInterface
 * * Defines operations for managing partner institution records.
 */
interface PartnerRepositoryInterface
{
    /**
     * Adds a new partner institution to the repository.
     * * @param Partner $partner The partner entity object.
     * @return void
     */
    public function addPartner(Partner $partner): void;
}