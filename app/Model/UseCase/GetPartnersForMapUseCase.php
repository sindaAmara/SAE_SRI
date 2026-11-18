<?php

namespace Model\UseCase;

use Model\Repository\PartnerRepositoryInterface;
use Model\Entity\Partner;

/**
 * GetPartnersForMapUseCase
 *
 * Use case responsible for retrieving the geolocated partner institutions
 * (AMU or IUT) shown on the interactive world map on the student homepage.
 * Delegates the actual query to the injected repository.
 */
class GetPartnersForMapUseCase
{
    /** @var PartnerRepositoryInterface Repository used to read partner data */
    private PartnerRepositoryInterface $repository;

    /**
     * @param PartnerRepositoryInterface $repository Repository used to read partner data
     */
    public function __construct(PartnerRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Returns the geolocated partners for the given site.
     *
     * Falls back to 'amu' when $site is neither 'amu' nor 'iut', so callers
     * (e.g. the controller reading a raw query-string value) don't need to
     * validate it themselves.
     *
     * @param string $site Requested partner site ('amu' or 'iut')
     * @return Partner[]
     */
    public function execute(string $site): array
    {
        $site = in_array($site, ['amu', 'iut'], true) ? $site : 'amu';

        return $this->repository->findGeolocatedBySite($site);
    }
}