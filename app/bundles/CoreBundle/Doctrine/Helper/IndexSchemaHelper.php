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

        if ([] === $textColumns) {
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

        // Replacements: create the new index under a temporary name first.
        // Only drop the old index after the new one exists. If the create
        // fails (PostgreSQL btree entry too large, or any other error), the
        // existing index is still in place. PostgreSQL DDL is transactional,
        // so the swap is wrapped in a transaction there. MySQL commits each
        // DDL statement; the temp-name order is what protects the old index.
        foreach ($this->changedIndexes as $index) {
            $this->replaceIndex($index, $platform);
        }

        foreach ($this->dropIndexes as $index) {
            $this->db->executeStatement($platform->getDropIndexSQL($index, $this->table));
        }

        foreach ($this->addedIndexes as $index) {
            $this->db->executeStatement($platform->getCreateIndexSQL($index, $this->table));
        }

        $this->changedIndexes = [];
        $this->dropIndexes    = [];
        $this->addedIndexes   = [];
    }

    /**
     * Create the replacement index under a temporary name, then drop the old
     * index and rename. The old index is only removed after the new one is in
     * place, so a failed create leaves the existing index untouched.
     */
    private function replaceIndex(Index $index, \Doctrine\DBAL\Platforms\AbstractPlatform $platform): void
    {
        $tempName = $index->getName().'_new';
        $temp     = new Index($tempName, $index->getColumns(), $index->isUnique(), $index->isPrimary(), $index->getFlags());

        $createTemp = $platform->getCreateIndexSQL($temp, $this->table);
        $dropOld    = $platform->getDropIndexSQL($index, $this->table);
        $dropTemp   = $platform->getDropIndexSQL($temp, $this->table);
        $createFinal = $platform->getCreateIndexSQL($index, $this->table);

        $isPg = DatabasePlatform::isPostgreSQL($platform);

        if ($isPg) {
            $this->db->beginTransaction();
        }

        try {
            $this->db->executeStatement($createTemp);
            $this->db->executeStatement($dropOld);
            $this->db->executeStatement($dropTemp);
            $this->db->executeStatement($createFinal);
            if ($isPg) {
                $this->db->commit();
            }
        } catch (\Throwable $e) {
            if ($isPg && $this->db->isTransactionActive()) {
                $this->db->rollBack();
            } else {
                // MySQL: best-effort cleanup of the temp index if it was created.
                try {
                    $this->db->executeStatement($dropTemp);
                } catch (\Throwable) {
                }
            }
            throw $e;
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
        return $this->sm->listTableIndexes($fullTableName);
    }

    /**
     * @param array<mixed> $indexColumns
     */
    private function tableHasIndex(string $tableName, string $indexName, array $indexColumns = []): bool
    {
        foreach ($this->getTableIndexes($tableName) as $idx) {
            if (strtolower($idx->getName()) === strtolower($indexName)) {
                if ([] === $indexColumns) {
                    return true;
                }
                $columns = $idx->getColumns();
                sort($columns);
                sort($indexColumns);

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
