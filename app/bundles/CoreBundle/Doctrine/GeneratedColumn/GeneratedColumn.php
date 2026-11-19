<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Doctrine\GeneratedColumn;

final class GeneratedColumn implements GeneratedColumnInterface
{
    private readonly string $tablePrefix;

    private readonly string $columnName;

    private bool $stored = false;

    private ?string $originalDateColumn = null;

    private ?string $timeUnit = null;

    private array $indexColumns = [];

    /**
     * Column specifications for index (e.g., 'column_name(100)').
     *
     * @var array<string>
     */
    private array $indexColumnSpecs = [];

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

    /**
     * @param array<string> $indexColumns
     */
    public function setIndexColumns(array $indexColumns): void
    {
        $this->indexColumnSpecs = $indexColumns;
        $this->indexColumns     = array_map(
            fn (string $spec): ?string => preg_replace('/\(\d+\)$/', '', $spec),
            $indexColumns
        );
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

    public function getAlterTableSql(): string
    {
        return "ALTER TABLE {$this->getTableName()} {$this->getAddColumnSql()};
            ALTER TABLE {$this->getTableName()} {$this->getAddIndexSql()}";
    }

    public function getAddColumnSql(): string
    {
        return "ADD {$this->columnName} {$this->getColumnDefinition()}";
    }

    public function getAddIndexSql(): string
    {
        $columns = implode(', ', $this->hasIndexColumnLengths() ? $this->indexColumnSpecs : $this->indexColumns);

        return "ADD INDEX `{$this->getIndexName()}`({$columns})";
    }

    public function getColumnDefinition(): string
    {
        $stored = $this->stored ? ' STORED' : '';

        return "{$this->columnType} AS ({$this->as}){$stored} COMMENT '(DC2Type:generated)'";
    }

    public function getIndexColumns(): array
    {
        return $this->indexColumns;
    }

    /**
     * Check if this generated column's index uses column lengths.
     *
     * Returns true if any index column specification includes a column length
     * (e.g., 'column_name(100)'), which indicates the index cannot be created
     * using Doctrine's addIndex() and must use raw SQL instead.
     */
    public function hasIndexColumnLengths(): bool
    {
        return [] !== $this->indexColumnSpecs;
    }

    public function getIndexName(): string
    {
        return $this->tablePrefix.implode('_', $this->indexColumns);
    }

    public function getFilterDateColumn(): ?string
    {
        return $this->filterDateColumn;
    }

    public function setFilterDateColumn(?string $filterDateColumn): void
    {
        $this->filterDateColumn = $filterDateColumn;
    }
}
