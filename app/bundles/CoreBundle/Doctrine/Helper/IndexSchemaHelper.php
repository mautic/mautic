<?php

namespace Mautic\CoreBundle\Doctrine\Helper;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\TextType;
use Mautic\CoreBundle\Doctrine\DatabasePlatform;
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
     * @throws SchemaException
     */
    public function setName($name): static
    {
        if (!$this->sm->tablesExist([$this->prefix.$name])) {
            throw new SchemaException("Table {$name} does not exist!");
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
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function addIndex($columns, $name, $options = []): static
    {
        $textColumns = $this->getTextColumns($columns);

        if ([] === $textColumns) {
            return $this;
        }

        $indexName = $this->prefix.$name;
        $index     = new Index($indexName, $textColumns, false, false, $options);

        // Check if index already exists with the same columns
        if ($this->tableHasIndex($this->table->getName(), $indexName, $textColumns)) {
            // Exact match → nothing to do
            return $this;
        }

        // Index either doesn't exist, or exists but has different columns
        if ($this->tableHasIndex($this->table->getName(), $indexName)) {
            // Index exists but has different columns
            $this->changedIndexes[] = $index;
        } else {
            // Index doesn't exist
            $this->addedIndexes[] = $index;
        }

        return $this;
    }

    /**
     * @param mixed  $columns
     * @param string $name
     * @param array  $options
     *
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function dropIndex($columns, $name, $options = []): static
    {
        $textColumns = $this->getTextColumns($columns);

        if (empty($textColumns)) {
            return $this;
        }

        $indexName = $this->prefix.$name;
        $index     = new Index($indexName, $textColumns, false, false, $options);

        if ($this->tableHasIndex($this->table->getName(), $indexName)) {
            $this->dropIndexes[] = $index;
        }

        return $this;
    }

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
        return $this->tableHasIndex(
            $this->prefix.$leadField->getCustomFieldObject(),
            $this->prefix.$leadField->getAlias().'_search'
        );
    }

    /**
     * @param array<mixed> $uniqueIdentifierColumns
     */
    public function hasMatchingUniqueIdentifierIndex(LeadField $leadField, array $uniqueIdentifierColumns): bool
    {
        return $this->tableHasIndex(
            $this->prefix.$leadField->getCustomFieldObject(),
            $this->prefix.$leadField->getObject().'_unique_identifier_search',
            $uniqueIdentifierColumns
        );
    }

    /**
     * @throws SchemaException
     */
    public function hasUniqueIdentifierIndex(LeadField $leadField): bool
    {
        return $this->tableHasIndex(
            $this->prefix.$leadField->getCustomFieldObject(),
            $this->prefix.$leadField->getObject().'_unique_identifier_search'
        );
    }

    /**
     * @return Index[]
     */
    public function getTableIndexes(string $fullTableName): array
    {
        // return $this->sm->listTableIndexes($fullTableName);
        return DatabasePlatform::listTableIndexes($this->db, $fullTableName);
    }

    /**
     * @param array<mixed> $indexColumns
     */
    private function tableHasIndex(string $tableName, string $indexName, array $indexColumns = []): bool
    {
        foreach ($this->getTableIndexes($tableName) as $idx) {
            if (strtolower($idx->getName()) === strtolower($indexName)) {
                if (empty($indexColumns)) {
                    return true;
                }
                $columns = $idx->getColumns();
                asort($columns);
                asort($indexColumns);

                return $columns === $indexColumns;
            }
        }

        return false;
    }

    /**
     * @param mixed $columns
     *
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    private function getTextColumns($columns): array
    {
        $platform  = $this->db->getDatabasePlatform();
        $allowText = DatabasePlatform::allowsTextInIndex($platform);

        foreach ($columns as $column) {
            if (!in_array($column, $this->allowedColumns, true)) {
                $columnSchema = $this->table->getColumn($column);
                $type         = $columnSchema->getType();

                if (!$type instanceof TextType || $allowText) {
                    $this->allowedColumns[] = $columnSchema->getName();
                }
            }
        }

        return array_values(array_intersect($columns, $this->allowedColumns));
    }
}
