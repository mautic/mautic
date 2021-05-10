<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Doctrine\GeneratedColumn;

final class GeneratedColumn implements GeneratedColumnInterface
{
    private string $tableName;

    /**
     * @var string
     */
    private $tablePrefix = '';

    private string $columnName;

    private ?string $originalDateColumn = null;

    private ?string $timeUnit = null;

    /**
     * @var bool
     */
    private $stored = false;

    /**
     * @var array
     */
    private $indexColumns = [];

    /**
     * @var ?string
     */
    private $filterDateColumn;

    private string $columnType;

    private string $as;

    /**
     * @param string $tableName
     * @param string $columnName
     * @param string $columnType
     * @param string $as
     */
    public function __construct($tableName, $columnName, $columnType, $as)
    {
        $this->as             = $as;
        $this->tableName      = $tableName;
        $this->columnType     = $columnType;
        $this->indexColumns[] = $columnName;
        $this->tablePrefix    = MAUTIC_TABLE_PREFIX;
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

    /**
     * If set then the line chart queries will use this column for the time unit instead of the original.
     */
    public function setOriginalDateColumn(string $originalDateColumn, string $timeUnit): void
    {
        $this->originalDateColumn = $originalDateColumn;
        $this->timeUnit           = $timeUnit;
    }

    public function getOriginalDateColumn(): ?string
    {
        return $this->originalDateColumn;
    }

    public function getTimeUnit(): string
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
        return "ADD {$this->getColumnName()} {$this->getColumnDefinition()}";
    }

    public function getAddIndexSql(): string
    {
        return "ADD INDEX `{$this->getIndexName()}`({$this->indexColumnsToString()})";
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
