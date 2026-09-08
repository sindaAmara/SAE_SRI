<?php

namespace Model\Entity;

/**
 * GenderStats
 *
 * Holds the gender distribution for a group of students.
 * Provides raw counts as well as percentage calculations for
 * male and female students relative to the total.
 */
class GenderStats
{
    /** @var int Number of male students */
    private int $male;

    /** @var int Number of female students */
    private int $female;

    /**
     * @param int $male   Number of male students
     * @param int $female Number of female students
     */
    public function __construct(int $male, int $female)
    {
        $this->male = $male;
        $this->female = $female;
    }

    /**
     * Returns the number of male students.
     *
     * @return int
     */
    public function getMale(): int
    {
        return $this->male;
    }

    /**
     * Returns the number of female students.
     *
     * @return int
     */
    public function getFemale(): int
    {
        return $this->female;
    }

    /**
     * Returns the combined total of male and female students.
     *
     * @return int
     */
    public function getTotal(): int
    {
        return $this->male + $this->female;
    }

    /**
     * Returns the percentage of male students out of the total.
     *
     * Returns 0 if the total is zero to avoid division by zero.
     *
     * @return float Percentage between 0 and 100
     */
    public function getMalePercentage(): float
    {
        $total = $this->getTotal();
        return $total > 0 ? ($this->male / $total) * 100 : 0;
    }

    /**
     * Returns the percentage of female students out of the total.
     *
     * Returns 0 if the total is zero to avoid division by zero.
     *
     * @return float Percentage between 0 and 100
     */
    public function getFemalePercentage(): float
    {
        $total = $this->getTotal();
        return $total > 0 ? ($this->female / $total) * 100 : 0;
    }

    /**
     * Returns the gender statistics as an associative array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'male' => $this->male,
            'female' => $this->female
        ];
    }
}