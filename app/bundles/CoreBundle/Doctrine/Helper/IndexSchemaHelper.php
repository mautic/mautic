<?php

namespace Mautic\CoreBundle\Doctrine\Helper;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\TextType;
use Mautic\CoreBundle\Exception\SchemaException;
use Mautic\LeadBundle\Entity\LeadField;

class IndexSchemaHelper
{
    /**
     * @var \Doctrine\DBAL\Schema\AbstractSchemaManager<\Doctrine\DBAL\Platforms\AbstractMySQLPlatform>
     */
    protected \Doctrine\DBAL\Schema\AbstractSchemaManager $sm;

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
     * @param string $prefix
     */
    public function __construct(
        protected Connection $db,
        protected $prefix,
    ) {
        $this->sm = $this->db->createSchemaManager();
    }

    /**
     * @return $this
     *
     * @throws SchemaException
     */
    public function setName($name)
    {
        if (!$this->sm->tablesExist($this->prefix.$name)) {
            throw new SchemaException("Table $name does not exist!");
        }

        $this->table = $this->sm->introspectTable($this->prefix.$name);

        return $this;
    }

    public function allowColumn($name): void
    {
        $this->allowedColumns[] = $name;
    }

    /**
     * @param string $name
     * @param array  $options
     *
     * @return $this
     *
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function addIndex($columns, $name, $options = [])
    {
        $textColumns = $this->getTextColumns($columns);

        if (empty($textColumns)) {
            return $this;
        }

        $index = new Index($this->prefix.$name, $textColumns, false, false, $options);

        if ($this->table->hasIndex($this->prefix.$name)) {
            $this->changedIndexes[] = $index;

            return $this;
        }

        $this->addedIndexes[] = $index;

        return $this;
    }

    /**
     * @param mixed  $columns
     * @param string $name
     * @param array  $options
     *
     * @return self
     *
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function dropIndex($columns, $name, $options = [])
    {
        $textColumns = $this->getTextColumns($columns);

        $index = new Index($this->prefix.$name, $textColumns, false, false, $options);
        if ($this->table->hasIndex($this->prefix.$name)) {
            $this->dropIndexes[] = $index;
        }

        return $this;
    }

    /**
     * Execute changes.
     */
    public function executeChanges(): void
    {
        $platform = $this->db->getDatabasePlatform();

        $sql = [];
        foreach ($this->changedIndexes as $index) {
            $sql[] = $platform->getDropIndexSQL($index, $this->table);
            $sql[] = $platform->getCreateIndexSQL($index, $this->table);
        }

        foreach ($this->dropIndexes as $index) {
            $sql[] = $platform->getDropIndexSQL($index, $this->table);
        }

        foreach ($this->addedIndexes as $index) {
            $sql[] = $platform->getCreateIndexSQL($index, $this->table);
        }

        if (count($sql)) {
            foreach ($sql as $query) {
                $this->db->executeStatement($query);
            }
            $this->changedIndexes = [];
            $this->dropIndexes    = [];
            $this->addedIndexes   = [];
        }
    }

    /**
     * @throws SchemaException
     */
    public function hasIndex(LeadField $leadField): bool
    {
        $alias = $leadField->getAlias();
        $this->setName($leadField->getCustomFieldObject());

        return $this->table->hasIndex($this->prefix."{$alias}_search");
    }

    /**
     * @param array<mixed> $uniqueIdentifierColumns
     */
    public function hasMatchingUniqueIdentifierIndex(LeadField $leadField, array $uniqueIdentifierColumns): bool
    {
        $this->setName($leadField->getCustomFieldObject());

        $index = $this->table->getIndex($this->prefix.'unique_identifier_search');

        $columns = $index->getColumns();

        asort($columns);
        asort($uniqueIdentifierColumns);

        return $columns === $uniqueIdentifierColumns;
    }

    /**
     * @throws SchemaException
     */
    public function hasUniqueIdentifierIndex(LeadField $leadField): bool
    {
        $this->setName($leadField->getCustomFieldObject());

        return $this->table->hasIndex($this->prefix.'unique_identifier_search');
    }

    /**
     * @param mixed $columns
     *
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    private function getTextColumns($columns): array
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

        return $columns;
    }
}
