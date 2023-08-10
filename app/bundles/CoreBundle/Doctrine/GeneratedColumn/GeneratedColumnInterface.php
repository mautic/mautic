<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Doctrine\GeneratedColumn;

interface GeneratedColumnInterface
{
    public function getTableName(): string;

    public function getColumnName(): string;

    public function addIndexColumn(string $indexColumn): void;

    /**
     * If set then the line chart queries will use this column for the time unit instead of the original.
     */
    public function setOriginalDateColumn(string $originalDateColumn, string $timeUnit): void;

    public function getOriginalDateColumn(): ?string;

    public function getTimeUnit(): string;

    public function getAlterTableSql(): string;

    public function getColumnDefinition(): string;

    public function getIndexColumns(): array;

    public function getIndexName(): string;
}
