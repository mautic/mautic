<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Doctrine;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Exception\AbortMigration;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class AbstractMauticMigration extends AbstractMigration
{
    protected const TABLE_NAME = null;

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
    protected array $supported = ['mysql'];

    /**
     * Database prefix.
     */
    protected string $prefix;

    /**
     * @throws \Doctrine\DBAL\Exception
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
            $this->$function($schema);
        }
    }

    public function down(Schema $schema): void
    {
        // Not supported
    }

    public function setContainer(?ContainerInterface $container = null): void
    {
        $this->container = $container;
        $this->prefix    = (string) $container->get(CoreParametersHelper::class)->get('db_table_prefix', '');
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

                    /** @var \Doctrine\DBAL\Schema\Index $i */
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

    protected function getColumnTypeSignedOrUnsigned(Schema $schema, string $tableName, string $columnName): string
    {
        $pagesTable  = $schema->getTable($this->getPrefixedTableName($tableName));
        $idColumn    = $pagesTable->getColumn($columnName);
        $idDataType  = self::COLUMN_TYPE_SIGNED;

        if (true === $idColumn->getUnsigned()) {
            $idDataType = self::COLUMN_TYPE_UNSIGNED;
        }

        return $idDataType;
    }
}
