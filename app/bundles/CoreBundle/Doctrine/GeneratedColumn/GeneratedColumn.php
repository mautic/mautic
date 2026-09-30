<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Doctrine\GeneratedColumn;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Mautic\CoreBundle\Doctrine\DatabasePlatform;

final class GeneratedColumn implements GeneratedColumnInterface
{
    private readonly string $tablePrefix;

    private readonly string $columnName;

    private bool $stored = false;

    private ?string $originalDateColumn = null;

    private ?string $timeUnit = null;

    private array $indexColumns = [];

    private ?string $filterDateColumn = null;

    public function __construct(
        private readonly string $tableName,
        string $columnName,
        private readonly string $columnType,
        private readonly string $as,
    ) {
        $this->indexColumns[] = $columnName;
        $this->tablePrefix    = (string) MAUTIC_TABLE_PREFIX;
        $this->columnName     = $columnName;
    }

    public function getTableName(): string
    {
        return $this->tablePrefix.$this->tableName;
    }

    public function getColumnName(): string
    {
        return $this->columnName;
    }

    public function setStored(bool $stored): void
    {
        $this->stored = $stored;
    }

    public function addIndexColumn(string $indexColumn): void
    {
        $this->indexColumns[] = $indexColumn;
    }

    public function prependIndexColumn(string $indexColumn): void
    {
        array_unshift($this->indexColumns, $indexColumn);
    }

    public function setOriginalDateColumn(string $originalDateColumn, string $timeUnit): void
    {
        $this->originalDateColumn = $originalDateColumn;
        $this->timeUnit           = $timeUnit;
    }

    public function getOriginalDateColumn(): ?string
    {
        return $this->originalDateColumn;
    }

    public function getTimeUnit(): ?string
    {
        return $this->timeUnit;
    }

    public function getAlterTableSql(?AbstractPlatform $platform = null): string
    {
        return "ALTER TABLE {$this->getTableName()} {$this->getAddColumnSql($platform)};
            ALTER TABLE {$this->getTableName()} {$this->getAddIndexSql()}";
    }

    public function getAddColumnSql(?AbstractPlatform $platform = null): string
    {
        $add = DatabasePlatform::getAddColumnKeyword($platform);

        return "{$add} {$this->columnName} {$this->getColumnDefinition($platform)}";
    }

    public function getAddIndexSql(): string
    {
        return "ADD INDEX `{$this->getIndexName()}`({$this->indexColumnsToString()})";
    }

    public function getColumnDefinition(?AbstractPlatform $platform = null): string
    {
        return DatabasePlatform::getGeneratedColumnDefinition($platform, $this->columnType, $this->as, $this->stored);
    }

    public function getIndexColumns(): array
    {
        return $this->indexColumns;
    }

    public function getIndexName(): string
    {
        return $this->tablePrefix.$this->indexColumnsToString('_');
    }

    public function getFilterDateColumn(): ?string
    {
        return $this->filterDateColumn;
    }

    public function setFilterDateColumn(?string $filterDateColumn): void
    {
        $this->filterDateColumn = $filterDateColumn;
    }

    private function indexColumnsToString(string $separator = ', '): string
    {
        return implode($separator, $this->indexColumns);
    }
}
