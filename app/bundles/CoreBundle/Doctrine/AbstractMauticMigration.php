<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Doctrine;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Exception\AbortMigration;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class AbstractMauticMigration extends AbstractMigration
{
    protected const TABLE_NAME = null;

    protected const INDEX_NAME = null;

    /**
     * @var string
     */
    public const COLUMN_TYPE_SIGNED = 'SIGNED';

    /**
     * @var string
     */
    public const COLUMN_TYPE_UNSIGNED = 'UNSIGNED';

    protected ContainerInterface $container;

    /**
     * Supported platforms.
     *
     * @var string[]
     */
    protected array $supported = ['mysql', 'postgresql'];

    /**
     * Database prefix.
     */
    protected string $prefix;

    /**
     * @throws Exception
     * @throws AbortMigration
     *
     * @todo remove this method to make it absctract for Mautic 6
     */
    public function up(Schema $schema): void
    {
        $platform = DatabasePlatform::getDatabasePlatform($this->platform);

        // Abort the migration if the platform is unsupported
        $this->abortIf(!in_array($platform, $this->supported), 'The database platform is unsupported for migrations');

        $function = $platform.'Up';

        if (method_exists($this, $function)) {
            $this->{$function}($schema);
        }
    }

    public function down(Schema $schema): void
    {
        // Not supported
    }

    public function setContainer(ContainerInterface $container): void
    {
        $this->container = $container;
    }

    public function setPrefix(string $prefix): void
    {
        $this->prefix = $prefix;
    }

    protected function indexExists(string $tableName, string $indexName, array $columns = []): bool
    {
        $indexes = $this->getIndexes($tableName);

        $lowerIndexName = strtolower($indexName);
        $expectedColumns = array_map('strtolower', $columns);
        foreach ($indexes as $index) {
            if (strtolower($index->getName()) !== $lowerIndexName) {
                continue;
            }

            // Name matches – if no columns were requested, we're done
            if ([] === $expectedColumns) {
                return true;
            }

            // Compare columns (order matters)
            $actualColumns = array_map('strtolower', $index->getColumns());

            if ($actualColumns === $expectedColumns) {
                return true;
            }
        }

        return false;
    }

    /**
     * Lists the indexes for a given table returning an array of Index instances.
     *
     * Keys of the portable indexes list are all lower-cased.
     *
     * @param string $tableName the name of the table
     *
     * @return Index[]
     *
     * @throws Exception
     */
    protected function getIndexes(string $tableName): array
    {
        // return $this->sm->listTableIndexes($tableName);
        return DatabasePlatform::listTableIndexes($this->connection, $tableName);
    }

    protected function dropIndex(string $tableName, string $indexName, bool $ifExists = true): void
    {
        $this->addSql(
            DatabasePlatform::getDropIndexSql(
                $this->platform,
                $tableName,
                $indexName,
                false,
                $ifExists
            )
        );
    }

    /**
     * @param array<string> $columns
     */
    protected function createIndex(string $tableName, string $indexName, array $columns, bool $unique = false, bool $ifNotExists = true): void
    {
        $this->addSql(
            DatabasePlatform::getCreateIndexSql(
                $this->platform,
                $tableName,
                $indexName,
                $columns,
                $unique,
                false,
                $ifNotExists
            )
        );
    }

    /**
     * Finds/creates the local name for constraints and indexes.
     *
     * @return string
     */
    protected function findPropertyName($table, $type, $suffix)
    {
        static $schemaManager;
        static $tables = [];

        if (empty($schemaManager)) {
            $schemaManager = $this->connection->createSchemaManager();
        }

        // Prepend prefix
        $table = $this->prefix.$table;

        if (!array_key_exists($table, $tables)) {
            $tables[$table] = [];
        }

        $type   = strtolower($type);
        $suffix = strtolower(substr($suffix, -4));

        switch ($type) {
            case 'fk':
                if (!array_key_exists('fk', $tables[$table])) {
                    $keys = $schemaManager->listTableForeignKeys($table);
                    /** @var \Doctrine\DBAL\Schema\ForeignKeyConstraint $k */
                    foreach ($keys as $k) {
                        $name                       = strtolower($k->getName());
                        $key                        = substr($name, -4);
                        $tables[$table]['fk'][$key] = $name;
                    }
                }

                $localName = $tables[$table]['fk'][$suffix];

                break;
            case 'idx':
            case 'uniq':
                if (!array_key_exists('idx', $tables[$table])) {
                    $tables[$table]['idx'] = [
                        'idx'  => [],
                        'uniq' => [],
                    ];

                    $indexes = $schemaManager->listTableIndexes($table);

                    /** @var Index $i */
                    foreach ($indexes as $i) {
                        $name   = strtolower($i->getName());
                        $isIdx  = stripos($name, 'idx');
                        $isUniq = stripos($name, 'uniq');

                        if (false !== $isIdx || false !== $isUniq) {
                            $key     = substr($name, -4);
                            $keyType = (false !== $isIdx) ? 'idx' : 'uniq';

                            $tables[$table]['idx'][$keyType][$key] = $name;
                        }
                    }
                }

                $localName = $tables[$table]['idx'][$type][$suffix];

                break;
        }

        return strtoupper($localName);
    }

    /**
     * Generate the  name for the property.
     *
     * @return string
     */
    protected function generatePropertyName($table, $type, array $columnNames)
    {
        $columnNames = array_merge([$this->prefix.$table], $columnNames);
        $hash        = implode(
            '',
            array_map(
                fn ($column): string => dechex(crc32($column)),
                $columnNames
            )
        );

        return substr(strtoupper($type.'_'.$hash), 0, 63);
    }

    /**
     * Generate index and foreign constraint.
     *
     * @return array [idx, fk]
     */
    protected function generateKeys($table, array $columnNames)
    {
        return [
            $this->generatePropertyName($table, 'idx', $columnNames),
            $this->generatePropertyName($table, 'fk', $columnNames),
        ];
    }

    /**
     * Use this when you're doing a migration that
     * purposely does not have any SQL statements,
     * such as when moving data using the query builder.
     */
    protected function suppressNoSQLStatementError()
    {
        $this->addSql('SELECT "This migration did not generate select statements." AS purpose');
    }

    /**
     * This method will remove the burden of getting prefixed table name in individual migration file.
     * Individual migration files just need to keep a protected constant TABLE_NAME.
     */
    protected function getPrefixedTableName(?string $tableName = null): string
    {
        if (null === $tableName) {
            $tableName = static::TABLE_NAME;
        }

        return $this->prefix.$tableName;
    }

    /**
     * This method will remove the burden of getting prefixed table name index in individual migration file.
     * Individual migration files just need to keep a protected constant INDEX_NAME.
     */
    protected function getPrefixedIndexName(?string $indexName = null): string
    {
        if (null === $indexName) {
            $indexName = static::INDEX_NAME;
        }

        return $this->prefix.$indexName;
    }

    protected function getColumnTypeSignedOrUnsigned(Schema $schema, string $tableName, string $columnName): string
    {
        $table       = $schema->getTable($this->getPrefixedTableName($tableName));
        $idColumn    = $table->getColumn($columnName);
        $idDataType  = self::COLUMN_TYPE_SIGNED;

        if (true === $idColumn->getUnsigned()) {
            $idDataType = self::COLUMN_TYPE_UNSIGNED;
        }

        return $idDataType;
    }

    protected function getColumnType(Schema $schema, string $tableName, string $columnName): string
    {
        $table = $schema->getTable($this->getPrefixedTableName($tableName));

        return Type::getTypeRegistry()->lookupName($table->getColumn($columnName)->getType());
    }
}
