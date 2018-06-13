<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Doctrine\Helper;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Table;
use Mautic\CoreBundle\Exception\SchemaException;

/**
 * Class ColumnSchemaHelper.
 *
 * Used to manipulate the schema of an existing table
 */
class ColumnSchemaHelper
{
    /**
     * @var Connection
     */
    protected $db;

    /**
     * @var \Doctrine\DBAL\Schema\AbstractSchemaManager
     */
    protected $sm;

    /**
     * @var string
     */
    protected $prefix;

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

    /**
     * @param Connection $db
     * @param string     $prefix
     */
    public function __construct(Connection $db, $prefix)
    {
        $this->db     = $db;
        $this->sm     = $db->getSchemaManager();
        $this->prefix = $prefix;
    }

    /**
     * Set the table to be manipulated.
     *
     * @param      $table
     * @param bool $addPrefix
     *
     * @return $this
     *
     * @throws SchemaException
     */
    public function setName($table, $addPrefix = true)
    {
        $this->tableName = ($addPrefix) ? $this->prefix.$table : $table;

        //make sure the table exists
        $this->checkTableExists($this->tableName, true);

        //use the to schema to get table details so that changes will be calculated
        $this->fromTable = $this->sm->listTableDetails($this->tableName);
        $this->toTable   = clone $this->fromTable;

        return $this;
    }

    /**
     * Get the SchemaManager.
     *
     * @return \Doctrine\DBAL\Schema\AbstractSchemaManager
     */
    public function getSchemaManager()
    {
        return $this->sm;
    }

    /**
     * Get table details.
     *
     * @return \Doctrine\DBAL\Schema\Table
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
     * @param array $columns
     *
     * @throws SchemaException
     */
    public function addColumns(array $columns)
    {
        //ensure none of the columns exist before manipulating the schema
        foreach ($columns as $column) {
            if (empty($column['name'])) {
                throw new SchemaException('Column is missing required name key.');
            }

            $this->checkColumnExists($column['name'], true);
        }

        //now add the columns
        foreach ($columns as $column) {
            $this->addColumn($column, false);
        }
    }

    /**
     * Add a column to the table.
     *
     * @param array $column
     *                           ['name']    string (required) unique name of column; cannot already exist
     *                           ['type']    string (optional) Doctrine type for column; defaults to text
     *                           ['options'] array  (optional) Defining options for column
     * @param bool  $checkExists Check if table exists; pass false if this has already been done
     *
     * @return $this
     *
     * @throws SchemaException
     */
    public function addColumn(array $column, $checkExists = true)
    {
        if (empty($column['name'])) {
            throw new SchemaException('Column is missing required name key.');
        }

        if ($checkExists) {
            $this->checkColumnExists($column['name'], true);
        }

        $type    = (isset($column['type'])) ? $column['type'] : 'text';
        $options = (isset($column['options'])) ? $column['options'] : [];

        $this->toTable->addColumn($column['name'], $type, $options);

        return $this;
    }

    /**
     * Drops a column from table.
     *
     * @param $columnName
     *
     * @return $this
     */
    public function dropColumn($columnName)
    {
        if ($this->checkColumnExists($columnName)) {
            $this->toTable->dropColumn($columnName);
        }

        return $this;
    }

    /**
     * Computes and executes the changes.
     */
    public function executeChanges()
    {
        //create a table diff
        $comparator = new Comparator();
        $diff       = $comparator->diffTable($this->fromTable, $this->toTable);

        if ($diff) {
            $this->sm->alterTable($diff);
        }
    }

    /**
     * Determine if a column already exists.
     *
     * @param string $column
     * @param bool   $throwException
     *
     * @return bool
     *
     * @throws SchemaException
     */
    public function checkColumnExists($column, $throwException = false)
    {
        //check to ensure column doesn't exist
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
     * @param            $table
     * @param bool|false $throwException
     *
     * @return bool
     *
     * @throws SchemaException
     */
    public function checkTableExists($table, $throwException = false)
    {
        if (!$this->sm->tablesExist($table)) {
            if ($throwException) {
                throw new SchemaException("Table $table does not exist!");
            } else {
                return false;
            }
        } else {
            return true;
        }
    }
}
