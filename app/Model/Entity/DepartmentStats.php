<?php

namespace Model\Entity;

/**
 * DepartmentStats
 *
 * Represents statistical data for a single department,
 * holding its name and the number of students associated with it.
 */
class DepartmentStats
{
    /** @var string Name of the department */
    private string $name;

    /** @var int Number of students in this department */
    private int $count;

    /**
     * @param string $name  Name of the department
     * @param int    $count Number of students in this department
     */
    public function __construct(string $name, int $count)
    {
        $this->name = $name;
        $this->count = $count;
    }

    /**
     * Returns the name of the department.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Returns the number of students in this department.
     *
     * @return int
     */
    public function getCount(): int
    {
        return $this->count;
    }

    /**
     * Returns the department statistics as an associative array.
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