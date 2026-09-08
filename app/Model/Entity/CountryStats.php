<?php

namespace Model\Entity;

/**
 * CountryStats
 *
 * Represents statistical data for a single country,
 * holding its name and the number of students associated with it.
 */
class CountryStats
{
    /** @var string Name of the country */
    private string $name;

    /** @var int Number of students from this country */
    private int $count;

    /**
     * @param string $name  Name of the country
     * @param int    $count Number of students from this country
     */
    public function __construct(string $name, int $count)
    {
        $this->name = $name;
        $this->count = $count;
    }

    /**
     * Returns the name of the country.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Returns the number of students from this country.
     *
     * @return int
     */
    public function getCount(): int
    {
        return $this->count;
    }

    /**
     * Returns the country statistics as an associative array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'count' => $this->count
        ];
    }
}