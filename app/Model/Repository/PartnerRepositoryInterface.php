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

    /**
     * Finds all geolocated partner institutions for the given site.
     *
     * Only returns partners that have both a latitude and a longitude set,
     * since ungeocoded rows cannot be placed on the map.
     *
     * @param string $site Partner site to filter on ('amu' or 'iut')
     * @return Partner[]
     */
    public function findGeolocatedBySite(string $site): array;
}