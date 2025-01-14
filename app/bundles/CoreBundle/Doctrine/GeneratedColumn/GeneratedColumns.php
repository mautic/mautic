<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Doctrine\GeneratedColumn;

final class GeneratedColumns implements GeneratedColumnsInterface
{
    /**
     * @var int
     */
    private $position = 0;

    /**
     * Simple array of generated columns.
     *
     * @var array
     */
    private $generatedColumns = [];

    /**
     * Array structure holding the generated columns that allows to
     * search by date column and unit without need for a loop.
     *
     * @var array
     */
    private $dateColumnIndex = [];

    public function add(GeneratedColumn $generatedColumn): void
    {
        $this->generatedColumns[] = $generatedColumn;

        if ($generatedColumn->getOriginalDateColumn() && $generatedColumn->getTimeUnit()) {
            if (!isset($this->dateColumnIndex[$generatedColumn->getOriginalDateColumn()])) {
                $this->dateColumnIndex[$generatedColumn->getOriginalDateColumn()] = [];
            }

            $this->dateColumnIndex[$generatedColumn->getOriginalDateColumn()][$generatedColumn->getTimeUnit()] = $generatedColumn;
        }
    }

    public function getForOriginalDateColumnAndUnit(string $originalDateColumn, string $unit): GeneratedColumnInterface
    {
        if (isset($this->dateColumnIndex[$originalDateColumn][$unit])) {
            return $this->dateColumnIndex[$originalDateColumn][$unit];
        }

        throw new \UnexpectedValueException("Generated column for original date column {$originalDateColumn} with unit {$unit} does not exist.");
    }

    public function rewind()
    {
        $this->position = 0;
    }

    public function current()
    {
        return $this->generatedColumns[$this->position];
    }

    public function key()
    {
        return $this->position;
    }

    public function next()
    {
        ++$this->position;
    }

    public function valid()
    {
        return isset($this->generatedColumns[$this->position]);
    }
}
