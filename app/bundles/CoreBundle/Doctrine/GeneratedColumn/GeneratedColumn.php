<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Doctrine\GeneratedColumn;

final class GeneratedColumn implements GeneratedColumnInterface
{
    /**
     * @var string
     */
    private $tablePrefix = '';

    private string $columnName;

    private ?string $originalDateColumn = null;

    private ?string $timeUnit = null;

    private array $indexColumns = [];

    public function __construct(
        private string $tableName,
        string $columnName,
        private string $columnType,
        private string $as
    ) {
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

    public function addIndexColumn(string $indexColumn): void
    {
        $this->indexColumns[] = $indexColumn;
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

    public function getTimeUnit(): string
    {
        return $this->timeUnit;
    }

    public function getAlterTableSql(): string
    {
        return "ALTER TABLE {$this->getTableName()} ADD {$this->getColumnName()} {$this->getColumnDefinition()};
            ALTER TABLE {$this->getTableName()} ADD INDEX `{$this->getIndexName()}`({$this->indexColumnsToString()})";
    }

    public function getColumnDefinition(): string
    {
        return "{$this->columnType} AS ({$this->as}) COMMENT '(DC2Type:generated)'";
    }

    public function getIndexColumns(): array
    {
        return $this->indexColumns;
    }

    public function getIndexName(): string
    {
        return $this->tablePrefix.$this->indexColumnsToString('_');
    }

    private function indexColumnsToString(string $separator = ', '): string
    {
        return implode($separator, $this->indexColumns);
    }
}
