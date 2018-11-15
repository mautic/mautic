<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Doctrine\GeneratedColumn;

final class GeneratedColumn implements GeneratedColumnInterface
{
    /**
     * @var string
     */
    private $tableName;

    /**
     * @var string
     */
    private $tablePrefix = '';

    /**
     * @var string
     */
    private $columnName;

    /**
     * @var string
     */
    private $columnType;

    /**
     * @var string
     */
    private $as;

    /**
     * @var string
     */
    private $originalDateColumn;

    /**
     * @var string
     */
    private $timeUnit;

    /**
     * @var array
     */
    private $indexColumns = [];

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
        $this->indexColumns[] = $columnName;
        $this->tablePrefix    = MAUTIC_TABLE_PREFIX;
        $this->columnName     = $columnName;
        $this->columnType     = $columnType;
    }

    /**
     * @return string
     */
    public function getTableName()
    {
        return $this->tablePrefix.$this->tableName;
    }

    /**
     * @return string
     */
    public function getColumnName()
    {
        return $this->columnName;
    }

    /**
     * @param string $indexColumn
     */
    public function addIndexColumn($indexColumn)
    {
        $this->indexColumns[] = $indexColumn;
    }

    /**
     * If set then the line chart queries will use this column for the time unit instead of the original.
     *
     * @param string $originalDateColumn
     * @param string $timeUnit
     */
    public function setOriginalDateColumn($originalDateColumn, $timeUnit)
    {
        $this->originalDateColumn = $originalDateColumn;
        $this->timeUnit           = $timeUnit;
    }

    /**
     * @return string
     */
    public function getOriginalDateColumn()
    {
        return $this->originalDateColumn;
    }

    /**
     * @return string
     */
    public function getTimeUnit()
    {
        return $this->timeUnit;
    }

    /**
     * @return string
     */
    public function getAlterTableSql()
    {
        return "ALTER TABLE {$this->tablePrefix}email_stats 
            ADD {$this->columnName} {$this->getColumnDefinition()},
            ADD index `{$this->tablePrefix}{$this->getIndexName()}`({$this->indexColumnsToString()})";
    }

    /**
     * @return string
     */
    public function getColumnDefinition()
    {
        return "{$this->columnType} AS ({$this->as}) COMMENT '(DC2Type:generated)'";
    }

    /**
     * @return array
     */
    public function getIndexColumns()
    {
        return $this->indexColumns;
    }

    /**
     * @return string
     */
    public function getIndexName()
    {
        return $this->tablePrefix.$this->indexColumnsToString('_');
    }

    /**
     * @param string $separator
     *
     * @return string
     */
    private function indexColumnsToString($separator = ', ')
    {
        return implode($separator, $this->indexColumns);
    }
}
