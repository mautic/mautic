<?php

namespace Mautic\CoreBundle\Doctrine\Helper;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
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
        if (!$this->sm->tablesExist([$this->prefix.$name])) {
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

        $indexName = $this->prefix.$name;
        $index     = new Index($indexName, $textColumns, false, false, $options);

        // Check if index already exists with the same columns
        if ($this->_hasIndex($this->table->getName(), $indexName, $textColumns)) {
            // Exact match → nothing to do
            return $this;
        }

        // Index either doesn't exist, or exists but has different columns
        if ($this->_hasIndex($this->table->getName(), $indexName)) {
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
     * @return self
     *
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function dropIndex($columns, $name, $options = [])
    {
        $textColumns = $this->getTextColumns($columns);

        if (empty($textColumns)) {
            return $this;
        }

        $indexName = $this->prefix.$name;
        $index     = new Index($indexName, $textColumns, false, false, $options);

        if ($this->_hasIndex($this->table->getName(), $indexName)) {
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
        return $this->_hasIndex(
            $this->prefix.$leadField->getCustomFieldObject(),
            $this->prefix.$leadField->getAlias().'_search'
        );
    }

    /**
     * @param array<mixed> $uniqueIdentifierColumns
     */
    public function hasMatchingUniqueIdentifierIndex(LeadField $leadField, array $uniqueIdentifierColumns): bool
    {
        return $this->_hasIndex(
            $this->prefix.$leadField->getCustomFieldObject(),
            $this->prefix.'unique_identifier_search',
            $uniqueIdentifierColumns
        );
    }

    /**
     * @throws SchemaException
     */
    public function hasUniqueIdentifierIndex(LeadField $leadField): bool
    {
        return $this->_hasIndex(
            $this->prefix.$leadField->getCustomFieldObject(),
            $this->prefix.'unique_identifier_search'
        );
    }

    /**
     * Custom reliable index listing for PostgreSQL (fallback to Doctrine for other platforms)
     * This bypasses the buggy Doctrine introspection in older DBAL versions (below 4.0)
     * (the deprecated getListTableIndexesSQL misses indexes due to flawed joins/filters).
     *
     * @return Index[]
     */
    public function getTableIndexes(string $fullTableName): array
    {
        $platform = $this->db->getDatabasePlatform();

        if (!$platform instanceof PostgreSQLPlatform) {
            return $this->sm->listTableIndexes($fullTableName);
        }

        // Reliable custom query for PostgreSQL
        $sql = "
            SELECT
                i.relname AS index_name,
                array_agg(a.attname ORDER BY c.ordinality) AS columns,
                ix.indisunique AS is_unique,
                ix.indisprimary AS is_primary
            FROM
                pg_class t
                JOIN pg_namespace ns ON ns.oid = t.relnamespace
                JOIN pg_index ix ON t.oid = ix.indrelid
                JOIN pg_class i ON ix.indexrelid = i.oid
                JOIN unnest(ix.indkey) WITH ORDINALITY AS c(attnum, ordinality) ON true
                JOIN pg_attribute a ON a.attrelid = t.oid AND a.attnum = c.attnum
            WHERE
                t.relkind = 'r'
                AND t.relname = :table
                AND ns.nspname = CURRENT_SCHEMA()
            GROUP BY
                i.relname, ix.indisunique, ix.indisprimary, i.oid
            ORDER BY
                i.relname
        ";

        $stmt    = $this->db->prepare($sql);
        $stmt->bindValue('table', $fullTableName);
        $results = $stmt->executeQuery()->fetchAllAssociative();

        $indexes = [];
        foreach ($results as $row) {
            $columns = $row['columns'];

            // Handle both native PHP array (newer drivers) and string representation {col1,col2}
            if (is_string($columns)) {
                $columnsStr = trim($columns, '{}');
                $columns    = explode(',', $columnsStr);
                $columns    = array_map(fn ($part) => trim($part, '"'), $columns);
            }
            // If already array (some drivers return native array), leave as-is

            $indexes[] = new Index(
                $row['index_name'],
                $columns,
                (bool) $row['is_unique'],
                (bool) $row['is_primary']
            );
        }

        return $indexes;
    }

    /**
     * @param array<mixed> $indexColumns
     */
    private function _hasIndex(string $tableName, string $indexName, array $indexColumns = []): bool
    {
        foreach ($this->getTableIndexes($tableName) as $idx) {
            if (strtolower($idx->getName()) === strtolower($indexName)) {
                if (empty($indexColumns)) {
                    return true;
                } else {
                    $columns = $idx->getColumns();
                    asort($columns);
                    asort($indexColumns);

                    return $columns === $indexColumns;
                }
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
        $allowText = $platform instanceof PostgreSQLPlatform;

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
