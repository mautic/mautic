<?php

namespace Mautic\CoreBundle\Doctrine\Helper;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Mautic\CoreBundle\Exception\SchemaException;

/**
 * Class TableSchemaHelper.
 *
 * Used to manipulate creation/removal of tables
 */
class TableSchemaHelper
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
     * @var ColumnSchemaHelper
     */
    protected $columnHelper;

    /**
     * @var \Doctrine\DBAL\Schema\Schema
     */
    protected $schema;

    /**
     * @var array
     */
    protected $dropTables;

    /**
     * @var array
     */
    protected $addTables;

    /**
     * @param $prefix
     */
    public function __construct(Connection $db, $prefix, ColumnSchemaHelper $columnHelper)
    {
        $this->db           = $db;
        $this->sm           = $db->getSchemaManager();
        $this->prefix       = $prefix;
        $this->columnHelper = $columnHelper;
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
     * Add an array of tables to db.
     *
     * @return $this
     *
     * @throws SchemaException
     */
    public function addTables(array $tables)
    {
        //ensure none of the tables exist before manipulating the schema
        foreach ($tables as $table) {
            if (empty($table['name'])) {
                throw new SchemaException('Table is missing required name key.');
            }

            $this->checkTableExists($table['name'], true);
        }

        //now add the tables
        foreach ($tables as $table) {
            $this->addTables[] = $table;
            $this->addTable($table, false);
        }

        return $this;
    }

    /**
     * Add a table to the db.
     *
     * @param array $table
     *                     ['name']    string (required) unique name of table; cannot already exist
     *                     ['columns'] array  (optional) Array of columns to add in the format of
     *                     array(
     *                     array(
     *                     'name'    => 'column_name', //required
     *                     'type'    => 'string',  //optional, defaults to text
     *                     'options' => array(...) //optional, column options
     *                     ),
     *                     ...
     *                     )
     *                     ['options'] array  (optional) Defining options for table
     *                     array(
     *                     'primaryKey' => array(),
     *                     'uniqueIndex' => array()
     *                     )
     * @param $checkExists
     * @param $dropExisting
     *
     * @return $this
     *
     * @throws SchemaException
     */
    public function addTable(array $table, $checkExists = true, $dropExisting = false)
    {
        if (empty($table['name'])) {
            throw new SchemaException('Table is missing required name key.');
        }

        if ($checkExists || $dropExisting) {
            $throwException = ($dropExisting) ? false : true;
            if ($this->checkTableExists($table['name'], $throwException) && $dropExisting) {
                $this->deleteTable($table['name']);
            }
        }

        $this->addTables[] = $table;

        $options = (isset($table['options'])) ? $table['options'] : [];
        $columns = (isset($table['columns'])) ? $table['columns'] : [];

        $newTable = $this->getSchema()->createTable($this->prefix.$table['name']);

        if (!empty($columns)) {
            //just to make sure a same name column is not added
            $columnsAdded = [];
            foreach ($columns as $column) {
                if (empty($column['name'])) {
                    throw new SchemaException('A column is missing required name key.');
                }

                if (!isset($columns[$column['name']])) {
                    $type       = (isset($column['type'])) ? $column['type'] : 'text';
                    $colOptions = (isset($column['options'])) ? $column['options'] : [];

                    $newTable->addColumn($column['name'], $type, $colOptions);
                    $columnsAdded[] = $column['name'];
                }
            }
        }

        if (!empty($options)) {
            foreach ($options as $option => $value) {
                $func = ('uniqueIndex' == $option ? 'add' : 'set').ucfirst($option);
                $newTable->$func($value);
            }
        }

        return $this;
    }

    /**
     * @param $table
     *
     * @return $this
     *
     * @throws SchemaException
     */
    public function deleteTable($table)
    {
        if ($this->checkTableExists($table)) {
            $this->dropTables[] = $table;
        }

        return $this;
    }

    /**
     * Executes the changes.
     */
    public function executeChanges()
    {
        $platform = $this->db->getDatabasePlatform();

        if (!empty($this->dropTables)) {
            foreach ($this->dropTables as $t) {
                $this->sm->dropTable($this->prefix.$t);
            }
        }

        $sql = $this->getSchema()->toSql($platform);

        if (!empty($sql)) {
            foreach ($sql as $s) {
                $this->db->executeUpdate($s);
            }
        }

        //reset schema
        $this->schema     = new Schema([], [], $this->sm->createSchemaConfig());
        $this->dropTables = $this->addTables = [];
    }

    /**
     * Determine if a table exists.
     *
     * @param string $table
     * @param bool   $throwException
     *
     * @return bool
     *
     * @throws SchemaException
     */
    public function checkTableExists($table, $throwException = false)
    {
        if ($this->sm->tablesExist($this->prefix.$table)) {
            if ($throwException) {
                throw new SchemaException($this->prefix."$table already exists");
            }

            return true;
        }

        return false;
    }

    private function getSchema(): Schema
    {
        if ($this->schema) {
            return $this->schema;
        }

        if ($this->db instanceof \Doctrine\DBAL\Connections\MasterSlaveConnection) {
            $params       = $this->db->getParams();
            $schemaConfig = new \Doctrine\DBAL\Schema\SchemaConfig();
            $schemaConfig->setName($params['master']['dbname']);
            $this->schema = new Schema([], [], $schemaConfig);
        } else {
            $this->schema = new Schema([], [], $this->sm->createSchemaConfig());
        }

        return $this->schema;
    }
}
