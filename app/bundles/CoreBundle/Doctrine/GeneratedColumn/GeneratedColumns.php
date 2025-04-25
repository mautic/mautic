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
     * search by date table, column and unit without need for a loop.
     */
    private array $dateColumnIndex = [];

    public function add(GeneratedColumn $generatedColumn): void
    {
        $this->generatedColumns[] = $generatedColumn;
        $originalDateColumn       = $generatedColumn->getOriginalDateColumn();
        $timeUnit                 = $generatedColumn->getTimeUnit();

        if (!$originalDateColumn || !$timeUnit) {
            return;
        }

        $tableName = $generatedColumn->getTableName();

        if (!isset($this->dateColumnIndex[$tableName])) {
            $this->dateColumnIndex[$tableName] = [];
        }

        if (!isset($this->dateColumnIndex[$tableName][$originalDateColumn])) {
            $this->dateColumnIndex[$tableName][$originalDateColumn] = [];
        }

        $this->dateColumnIndex[$tableName][$originalDateColumn][$timeUnit] = $generatedColumn;
    }

    public function getForOriginalDateColumnAndUnit(string $originalDateColumn, string $unit): GeneratedColumnInterface
    {
        // First try to search in the organized index
        foreach ($this->dateColumnIndex as $tableIndex) {
            if (isset($tableIndex[$originalDateColumn][$unit])) {
                return $tableIndex[$originalDateColumn][$unit];
            }
        }

        // If not found, search in the flat array (backward compatibility)
        foreach ($this->generatedColumns as $generatedColumn) {
            if ($generatedColumn->getOriginalDateColumn() === $originalDateColumn && $generatedColumn->getTimeUnit() === $unit) {
                return $generatedColumn;
            }
        }

        throw new \UnexpectedValueException("Generated column for original date column {$originalDateColumn} with unit {$unit} does not exist.");
    }

    public function getGeneratedColumnForDateColumn(string $table, string $column, string $unit): GeneratedColumn
    {
        if (isset($this->dateColumnIndex[$table][$column][$unit])) {
            return $this->dateColumnIndex[$table][$column][$unit];
        }

        throw new \UnexpectedValueException("Generated column for original date column {$column} in table {$table} with unit {$unit} does not exist.");
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
