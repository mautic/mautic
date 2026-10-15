<?php

namespace Mautic\CoreBundle\Doctrine\Helper;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\TableDiff;
use Mautic\CoreBundle\Doctrine\DatabasePlatform;
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
     * @param bool $addPrefix
     *
     * @throws SchemaException
     */
    public function setName($table, $addPrefix = true): static
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
    public function addColumn(array $column, $checkExists = true): static
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
            if (DatabasePlatform::isPostgreSQL($this->db->getDatabasePlatform())) {
                $this->alterPostgreSqlTable($diff);
            } else {
                $this->sm->alterTable($diff);
            }
        }
    }

    private function alterPostgreSqlTable(TableDiff $diff): void
    {
        $changedColumns = [];
        foreach ($diff->getModifiedColumns() as $column) {
            if ($column->hasTypeChanged() || $column->hasLengthChanged() || $column->hasPrecisionChanged() || $column->hasScaleChanged() || $column->hasFixedChanged()) {
                $changedColumns[] = $column->getOldColumn()?->getName() ?? $column->getNewColumn()->getName();
            }
        }
        if (!$changedColumns) {
            $this->sm->alterTable($diff);

            return;
        }

        $generatedColumns = $this->db->fetchAllAssociative(
            <<<'SQL'
                SELECT DISTINCT generated.attnum, generated.attname,
                    format_type(generated.atttypid, generated.atttypmod) AS type,
                    pg_get_expr(definition.adbin, definition.adrelid) AS expression,
                    generated.attnotnull, generated.attgenerated,
                    col_description(generated.attrelid, generated.attnum) AS comment
                FROM pg_attribute source
                JOIN pg_depend dependency ON dependency.refobjid = source.attrelid AND dependency.refobjsubid = source.attnum
                    AND dependency.classid = 'pg_attrdef'::regclass
                JOIN pg_attrdef definition ON definition.oid = dependency.objid
                JOIN pg_attribute generated ON generated.attrelid = definition.adrelid AND generated.attnum = definition.adnum
                WHERE source.attrelid = to_regclass(:table) AND source.attname IN (:columns) AND generated.attgenerated <> ''
                SQL,
            ['table' => $this->tableName, 'columns' => $changedColumns],
            ['columns' => ArrayParameterType::STRING]
        );
        if (!$generatedColumns) {
            $this->sm->alterTable($diff);

            return;
        }

        $parameters = ['table' => $this->tableName, 'columns' => array_column($generatedColumns, 'attnum')];
        $types      = ['columns' => ArrayParameterType::INTEGER];
        $indexes    = $this->db->fetchFirstColumn(
            <<<'SQL'
                SELECT DISTINCT pg_get_indexdef(index.indexrelid)
                FROM pg_index index
                JOIN pg_depend dependency ON dependency.objid = index.indexrelid AND dependency.classid = 'pg_class'::regclass
                    AND dependency.refobjid = index.indrelid
                WHERE index.indrelid = to_regclass(:table) AND dependency.refobjsubid IN (:columns)
                    AND NOT EXISTS (SELECT 1 FROM pg_constraint c WHERE c.conindid = index.indexrelid AND c.contype IN ('p', 'u', 'x'))
                SQL,
            $parameters,
            $types
        );
        $constraints = $this->db->fetchAllAssociative(
            <<<'SQL'
                SELECT conname, pg_get_constraintdef(oid) AS definition
                FROM pg_constraint
                WHERE conrelid = to_regclass(:table) AND EXISTS (SELECT 1 FROM unnest(conkey) AS column_number WHERE column_number IN (:columns))
                SQL,
            $parameters,
            $types
        );
        $table = $this->db->quoteIdentifier($this->tableName);

        // PostgreSQL forbids changing a generated column's source type; rebuilding dependencies must be atomic.
        $this->db->transactional(function () use ($diff, $generatedColumns, $indexes, $constraints, $table): void {
            foreach ($generatedColumns as $column) {
                $this->db->executeStatement('ALTER TABLE '.$table.' DROP COLUMN '.$this->db->quoteIdentifier($column['attname']));
            }
            $this->sm->alterTable($diff);
            foreach ($generatedColumns as $column) {
                $name    = $this->db->quoteIdentifier($column['attname']);
                $storage = 's' === $column['attgenerated'] ? 'STORED' : 'VIRTUAL';
                $this->db->executeStatement("ALTER TABLE {$table} ADD COLUMN {$name} {$column['type']} GENERATED ALWAYS AS ({$column['expression']}) {$storage}".($column['attnotnull'] ? ' NOT NULL' : ''));
                if (null !== $column['comment']) {
                    $this->db->executeStatement("COMMENT ON COLUMN {$table}.{$name} IS ".$this->db->quote($column['comment']));
                }
            }
            foreach ($constraints as $constraint) {
                $this->db->executeStatement('ALTER TABLE '.$table.' ADD CONSTRAINT '.$this->db->quoteIdentifier($constraint['conname']).' '.$constraint['definition']);
            }
            foreach ($indexes as $sql) {
                $this->db->executeStatement($sql);
            }
        });
    }

    /**
     * Determine if a column already exists.
     *
     * @param string $column
     * @param bool   $throwException
     *
     * @throws SchemaException
     */
    public function checkColumnExists($column, $throwException = false): bool
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
    public function checkTableExists($table, $throwException = false): bool
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
