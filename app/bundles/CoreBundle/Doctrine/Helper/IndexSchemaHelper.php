<?php

/*
 * @copyright   2015 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Doctrine\Helper;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\TextType;
use Mautic\CoreBundle\Exception\SchemaException;

class IndexSchemaHelper
{
    /**
     * @var Connection
     */
    protected $db;

    /**
     * @var
     */
    protected $prefix;

    /**
     * @var \Doctrine\DBAL\Schema\AbstractSchemaManager
     */
    protected $sm;

    /**
     * @var \Doctrine\DBAL\Schema\Schema
     */
    protected $schema;

    /**
     * @var Table
     */
    protected $table;

    /**
     * @var array
     */
    protected $allowedColumns = [];

    /**
     * @var array
     */
    protected $changedIndexes = [];

    /**
     * @var array
     */
    protected $addedIndexes = [];

    /**
     * @var array
     */
    protected $dropIndexes = [];

    /**
     * @param Connection $db
     * @param            $prefix
     */
    public function __construct(Connection $db, $prefix)
    {
        $this->db     = $db;
        $this->prefix = $prefix;
        $this->sm     = $this->db->getSchemaManager();
    }

    /**
     * @param $name
     *
     * @throws SchemaException
     */
    public function setName($name)
    {
        if (!$this->sm->tablesExist($this->prefix.$name)) {
            throw new SchemaException("Table $name does not exist!");
        }

        $this->table = $this->sm->listTableDetails($this->prefix.$name);
    }

    /**
     * @param $name
     */
    public function allowColumn($name)
    {
        $this->allowedColumns[] = $name;
    }

    /**
     * Add or update an index to the table.
     *
     * @param       $columns
     * @param       $name
     * @param array $options
     *
     * @throws SchemaException
     */
    public function addIndex($columns, $name, $options = [])
    {
        if (!is_array($columns)) {
            $columns = [$columns];
        }
        foreach ($columns as $column) {
            if (!in_array($column, $this->allowedColumns)) {
                $columnSchema = $this->table->getColumn($column);

                $type = $columnSchema->getType();
                if (!$type instanceof TextType) {
                    $this->allowedColumns[] = $columnSchema->getName();
                }
            }
        }

        // Indexes are only allowed on columns that are string
        $columns = array_intersect($columns, $this->allowedColumns);

        if (!empty($columns)) {
            $index = new Index($this->prefix.$name, $columns, false, false, $options);

            if ($this->table->hasIndex($this->prefix.$name)) {
                $this->changedIndexes[] = $index;
            } else {
                $this->addedIndexes[] = $index;
            }
        }
    }

    /**
     * Execute changes.
     */
    public function executeChanges()
    {
        $platform = $this->sm->getDatabasePlatform();

        $sql = [];
        if (count($this->changedIndexes)) {
            foreach ($this->changedIndexes as $index) {
                $sql[] = $platform->getDropIndexSQL($index, $this->table);
                $sql[] = $platform->getCreateIndexSQL($index, $this->table);
            }
        }

        if (count($this->dropIndexes)) {
            foreach ($this->dropIndexes as $index) {
                $sql[] = $platform->getDropIndexSQL($index, $this->table);
            }
        }

        if (count($this->addedIndexes)) {
            foreach ($this->addedIndexes as $index) {
                $sql[] = $platform->getCreateIndexSQL($index, $this->table);
            }
        }

        if (count($sql)) {
            foreach ($sql as $query) {
                $this->db->executeUpdate($query);
            }
            $this->changedIndexes = [];
            $this->dropIndexes    = [];
            $this->addedIndexes   = [];
        }
    }
}
