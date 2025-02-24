<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Doctrine\GeneratedColumn;

final class GeneratedColumns implements GeneratedColumnsInterface
{
    private int $position = 0;

    /**
     * Simple array of generated columns.
     */
    private array $generatedColumns = [];

    /**
     * Array structure holding the generated columns that allows to
     * search by date column and unit without need for a loop.
     */
    private array $dateColumnIndex = [];

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

    public function rewind(): void
    {
        $this->position = 0;
    }

    public function current(): mixed
    {
        return $this->generatedColumns[$this->position];
    }

    public function key(): mixed
    {
        return $this->position;
    }

    public function next(): void
    {
        ++$this->position;
    }

    public function valid(): bool
    {
        return isset($this->generatedColumns[$this->position]);
    }

    public function count(): int
    {
        return count($this->generatedColumns);
    }
}
