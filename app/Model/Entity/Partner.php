<?php

namespace Model\Entity;

/**
 * Partner
 *
 * Represents a partner institution in an international exchange or cooperation programme.
 * Stores the geographical location (continent, country, city) and details
 * about the institution itself (name and partnership type).
 *
 * $id, $latitude, $longitude and $offers are optional and only populated when
 * the partner is read back from storage for map display (see
 * PartnerRepositoryInterface::findGeolocatedBySite()); they stay null for
 * partners built by hand before insertion (see PartnersControllerAdmin).
 */
class Partner
{
    private ?int $id;
    private string $continent;
    private string $country;
    private string $city;
    private string $institution;
    private string $type;
    private ?float $latitude;
    private ?float $longitude;
    private ?string $offers;

    /**
     * @param string      $continent   Continent where the partner institution is located
     * @param string      $country     Country where the partner institution is located
     * @param string      $city        City where the partner institution is located
     * @param string      $institution Name of the partner institution
     * @param string      $type        Type of partnership (site AMU/IUT, e.g. 'amu' or 'iut')
     * @param int|null    $id          Database identifier, when known (map read path)
     * @param float|null  $latitude    Geocoded latitude, when known (map read path)
     * @param float|null  $longitude   Geocoded longitude, when known (map read path)
     * @param string|null $offers      What the institution offers, when known (map read path)
     */
    public function __construct(
        string $continent,
        string $country,
        string $city,
        string $institution,
        string $type,
        ?int $id = null,
        ?float $latitude = null,
        ?float $longitude = null,
        ?string $offers = null
    ) {
        $this->continent   = $continent;
        $this->country     = $country;
        $this->city        = $city;
        $this->institution = $institution;
        $this->type        = $type;
        $this->id          = $id;
        $this->latitude    = $latitude;
        $this->longitude   = $longitude;
        $this->offers      = $offers;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Returns the continent where the partner institution is located.
     *
     * @return string
     */
    public function getContinent(): string
    {
        return $this->continent;
    }

    /**
     * Returns the country where the partner institution is located.
     *
     * @return string
     */
    public function getCountry(): string
    {
        return $this->country;
    }

    /**
     * Returns the city where the partner institution is located.
     *
     * @return string
     */
    public function getCity(): string
    {
        return $this->city;
    }

    /**
     * Returns the name of the partner institution.
     *
     * @return string
     */
    public function getInstitution(): string
    {
        return $this->institution;
    }

    /**
     * Returns the type of partnership.
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    /**
     * Returns what the institution offers, or null when not filled in.
     *
     * @return string|null
     */
    public function getOffers(): ?string
    {
        return $this->offers;
    }
}