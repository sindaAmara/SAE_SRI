<?php

namespace Model\Entity;

/**
 * Partner
 *
 * Represents a partner institution in an international exchange or cooperation programme.
 * Stores the geographical location (continent, country, city) and details
 * about the institution itself (name and partnership type).
 */
class Partner
{
    /** @var string Continent where the partner institution is located */
    private string $continent;

    /** @var string Country where the partner institution is located */
    private string $country;

    /** @var string City where the partner institution is located */
    private string $city;

    /** @var string Name of the partner institution */
    private string $institution;

    /** @var string Type of partnership (e.g. exchange, double degree) */
    private string $type;

    /**
     * @param string $continent   Continent where the partner institution is located
     * @param string $country     Country where the partner institution is located
     * @param string $city        City where the partner institution is located
     * @param string $institution Name of the partner institution
     * @param string $type        Type of partnership
     */
    public function __construct(string $continent, string $country, string $city, string $institution, string $type)
    {
        $this->continent   = $continent;
        $this->country     = $country;
        $this->city        = $city;
        $this->institution = $institution;
        $this->type        = $type;
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
}