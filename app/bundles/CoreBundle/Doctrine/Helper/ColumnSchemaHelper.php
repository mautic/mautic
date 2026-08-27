<?php

namespace Mautic\CoreBundle\Doctrine\Helper;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Table;
use Mautic\CoreBundle\Exception\SchemaException;
use Mautic\LeadBundle\Entity\LeadField;

/**
 * Used to manipulate the schema of an existing table.
 */
class ColumnSchemaHelper
{
    /**
     * @var AbstractSchemaManager<\Doctrine\DBAL\Platforms\AbstractMySQLPlatform>
     */
    protected AbstractSchemaManager $sm;

    /**
     * @var string
     */
    protected $tableName;

    /**
     * @var Table
     */
    protected $fromTable;

    /**
     * @var Table
     */
    protected $toTable;

    private $columns;

    /**
     * @param string $prefix
     */
    public function __construct(
        protected Connection $db,
        protected $prefix,
    ) {
        $this->sm = $db->createSchemaManager();
    }

    /**
     * Set the table to be manipulated.
     *
     * @throws SchemaException
     */
    public function setName($table, bool $addPrefix = true): static
    {
        $this->tableName = ($addPrefix) ? $this->prefix.$table : $table;

        // make sure the table exists
        $this->checkTableExists($this->tableName, true);

        // use the to schema to get table details so that changes will be calculated
        $this->fromTable = $this->sm->introspectTable($this->tableName);
        $this->toTable   = clone $this->fromTable;

        return $this;
    }

    /**
     * @return AbstractSchemaManager<\Doctrine\DBAL\Platforms\AbstractMySQLPlatform>
     */
    public function getSchemaManager(): AbstractSchemaManager
    {
        return $this->sm;
    }

    /**
     * Get table details.
     *
     * @return Table
     */
    public function getTable()
    {
        return $this->toTable;
    }

    /**
     * Get array of Doctrine\DBAL\Schema\Column instances for the table.
     *
     * @return array
     */
    public function getColumns()
    {
        if (empty($this->columns)) {
            $this->columns = $this->toTable->getColumns();
        }

        return $this->columns;
    }

    /**
     * Add an array of columns to the table.
     *
     * @throws SchemaException
     */
    public function addColumns(array $columns): void
    {
        // ensure none of the columns exist before manipulating the schema
        foreach ($columns as $column) {
            if (empty($column['name'])) {
                throw new SchemaException('Column is missing required name key.');
            }

            $this->checkColumnExists($column['name'], true);
        }

        // now add the columns
        foreach ($columns as $column) {
            $this->addColumn($column, false);
        }
    }

    /**
     * Add a column to the table.
     *
     *                           ['name']    string (required) unique name of column; cannot already exist
     *                           ['type']    string (optional) Doctrine type for column; defaults to text
     *                           ['options'] array  (optional) Defining options for column
     *
     * @param bool $checkExists Check if table exists; pass false if this has already been done
     *
     * @throws SchemaException
     */
    public function addColumn(array $column, bool $checkExists = true): static
    {
        if (empty($column['name'])) {
            throw new SchemaException('Column is missing required name key.');
        }

        if ($checkExists) {
            $this->checkColumnExists($column['name'], true);
        }

        $type    = $column['type'] ?? 'text';
        $options = $column['options'] ?? [];

        $this->toTable->addColumn($column['name'], $type, $options);

        return $this;
    }

    /**
     * @throws SchemaException
     * @throws \OutOfRangeException
     */
    public function updateColumnLength(string $column, ?int $length): self
    {
        if (empty($column)) {
            throw new SchemaException('The column name is should not be empty/missing.');
        }

        if (null !== $length && ($length < 1 || $length > LeadField::MAX_VARCHAR_LENGTH)) {
            throw new \OutOfRangeException('Column length should be between 1 and 191.');
        }

        $this->toTable->modifyColumn($column, ['length' => $length]);

        return $this;
    }

    /**
     * Drops a column from table.
     */
    public function dropColumn($columnName): static
    {
        if ($this->checkColumnExists($columnName)) {
            $this->toTable->dropColumn($columnName);
        }

        return $this;
    }

    /**
     * Computes and executes the changes.
     */
    public function executeChanges(): void
    {
        // create a table diff
        $comparator = new Comparator();
        $diff       = $comparator->compareTables($this->fromTable, $this->toTable);

        if (!$diff->isEmpty()) {
            $this->sm->alterTable($diff);
        }
    }

    /**
     * Determine if a column already exists.
     *
     * @param string $column
     *
     * @throws SchemaException
     */
    public function checkColumnExists($column, bool $throwException = false): bool
    {
        // check to ensure column doesn't exist
        if ($this->toTable->hasColumn($column)) {
            if ($throwException) {
                throw new SchemaException("The column {$column} already exists in {$this->tableName}");
            }

            return true;
        }

        return false;
    }

    /**
     * Determine if a table exists.
     *
     * @param bool|false $throwException
     *
     * @throws SchemaException
     */
    public function checkTableExists($table, bool $throwException = false): bool
    {
        if (!$this->sm->tablesExist([$table])) {
            if ($throwException) {
                throw new SchemaException("Table {$table} does not exist!");
            }

            return false;
        }

        return true;
    }
}
