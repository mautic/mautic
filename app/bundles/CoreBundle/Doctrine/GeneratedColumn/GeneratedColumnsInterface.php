<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Doctrine\GeneratedColumn;

interface GeneratedColumnsInterface extends \Iterator, \Countable
{
    public function add(GeneratedColumn $generatedColumn): void;

    /**
     * @throws \UnexpectedValueException
     */
    public function getForOriginalDateColumnAndUnit(string $originalDateColumn, string $unit): GeneratedColumnInterface;

    public function getGeneratedColumnForDateColumn(string $table, string $column, string $unit): GeneratedColumn;

    public function rewind(): void;

    public function current(): GeneratedColumn;

    public function key(): int;

    public function next(): void;

    public function valid(): bool;

    public function count(): int;
}
