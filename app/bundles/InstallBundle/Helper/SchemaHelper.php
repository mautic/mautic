<?php

namespace Mautic\InstallBundle\Helper;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Index\IndexedColumn;
use Doctrine\DBAL\Schema\Index\IndexType;
use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\Tools\SchemaTool;
use Mautic\CoreBundle\Doctrine\Schema\AssetName;
use Mautic\CoreBundle\Release\ThisRelease;
use Mautic\InstallBundle\Exception\DatabaseVersionTooOldException;

final class SchemaHelper
{
    private Connection $db;

    /**
     * @var AbstractPlatform
     */
    private $platform;

    private array $dbParams;

    /**
     * @var AbstractSchemaManager<AbstractPlatform>|null
     */
    private ?AbstractSchemaManager $schemaManager = null;

    /**
     * @param array<string, mixed> $dbParams
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function __construct(
        array $dbParams,
        private readonly EntityManagerInterface $entityManager,
    ) {
        // suppress display of errors as we know its going to happen while testing the connection
        ini_set('display_errors', '0');

        // Support for env variables
        foreach ($dbParams as &$v) {
            if (!empty($v) && is_string($v) && preg_match('/getenv\((.*?)\)/', $v, $match)) {
                $v = (string) getenv($match[1]);
            }
        }

        $dbParams['charset'] = 'utf8mb4';
        if (isset($dbParams['name'])) {
            $dbParams['dbname'] = $dbParams['name'];
            unset($dbParams['name']);
        }

        $this->db = DriverManager::getConnection($dbParams);

        $this->dbParams = $dbParams;
    }

    /**
     * Test db connection.
     */
    public function testConnection(): void
    {
        if (isset($this->dbParams['dbname'])) {
            // Test connection credentials
            $dbParams = $this->dbParams;
            unset($dbParams['dbname']);
            $db = DriverManager::getConnection($dbParams);

            $db->getNativeConnection();
            $db->close();
        } else {
            $this->db->getNativeConnection();
            $this->db->close();
        }
    }

    public function createDatabase(): bool
    {
        try {
            $this->db->getNativeConnection();
        } catch (\Exception) {
            // it failed to connect so remove the dbname and try to create it
            $dbName                   = $this->dbParams['dbname'];
            $this->dbParams['dbname'] = null;

            try {
                // database does not exist so try to create it
                $this->getSchemaManager()->createDatabase($dbName);

                // close the connection and reconnect with the new database name
                $this->db->close();

                $this->dbParams['dbname'] = $dbName;
                $this->db                 = DriverManager::getConnection($this->dbParams);
                $this->db->close();
            } catch (\Exception) {
                return false;
            }
        }

        return true;
    }

    /**
     * Generates SQL for installation.
     *
     * @throws \Doctrine\DBAL\Exception
     * @throws ORMException
     */
    public function installSchema(): bool
    {
        $sm = $this->getSchemaManager();

        try {
            // check to see if the table already exist
            $tables = $sm->introspectTableNames();
        } catch (\Exception $e) {
            $this->db->close();

            throw $e;
        }

        $this->platform = $this->db->getDatabasePlatform();
        $backupPrefix   = (!empty($this->dbParams['backup_prefix'])) ? $this->dbParams['backup_prefix'] : 'bak_';

        $metadatas = $this->entityManager->getMetadataFactory()->getAllMetadata();
        if (empty($metadatas)) {
            $this->db->close();

            return false;
        }

        $schemaTool    = new SchemaTool($this->entityManager);
        $installSchema = $schemaTool->getSchemaFromMetadata($metadatas);
        $mauticTables  = [];

        foreach ($installSchema->getTables() as $m) {
            $tableName                = AssetName::of($m);
            $mauticTables[$tableName] = $this->generateBackupName($this->dbParams['table_prefix'], $backupPrefix, $tableName);
        }

        $isSqlite = $this->entityManager->getConnection()->getDatabasePlatform() instanceof SQLitePlatform;
        $sql      = $isSqlite ? [] : ['SET foreign_key_checks = 0;'];
        if ($this->dbParams['backup_tables']) {
            $sql = array_merge($sql, $this->backupExistingSchema($tables, $mauticTables, $backupPrefix));
        } else {
            $sql = array_merge($sql, $this->dropExistingSchema($tables, $mauticTables));
        }

        $sql = array_merge($sql, $installSchema->toSql($this->platform));

        // Execute drop queries
        foreach ($sql as $q) {
            try {
                $this->db->executeStatement($q);
            } catch (\Exception $exception) {
                $this->db->close();

                throw $exception;
            }
        }

        $this->db->close();

        return true;
    }

    public function validateDatabaseVersion(): void
    {
        // Version strings are in the format 10.3.30-MariaDB-1:10.3.30+maria~focal-log
        $version  = $this->db->executeQuery('SELECT VERSION()')->fetchOne();

        // Platform class names are in the format Doctrine\DBAL\Platforms\MariaDb1027Platform
        $platform = strtolower($this->db->getDatabasePlatform()::class);
        $metadata = ThisRelease::getMetadata();

        /**
         * The second case is for MariaDB < 10.2, where Doctrine reports it as MySQLPlatform. Here we can use a little
         * help from the version string, which contains "MariaDB" in that case: 10.1.48-MariaDB-1~bionic.
         */
        if (str_contains($platform, 'mariadb') || str_contains(strtolower($version), 'mariadb')) {
            $minSupported = $metadata->getMinSupportedMariaDbVersion();
        } elseif (str_contains($platform, 'mysql')) {
            $minSupported = $metadata->getMinSupportedMySqlVersion();
        } else {
            throw new \Exception('Invalid database platform '.$platform.'. Mautic only supports MySQL and MariaDB!');
        }

        if (version_compare($version, $minSupported, '<')) {
            throw new DatabaseVersionTooOldException($version);
        }
    }

    /**
     * @param string[]              $tables
     * @param array<string, string> $mauticTables
     *
     * @throws \Doctrine\DBAL\Exception
     */
    private function backupExistingSchema(array $tables, array $mauticTables, string $backupPrefix): array
    {
        $sql = [];
        $sm  = $this->getSchemaManager();

        // backup existing tables
        $backupRestraints = $backupSequences = $backupIndexes = $backupTables = $dropSequences = $dropTables = [];

        // cycle through the first time to drop all the foreign keys
        foreach ($tables as $t) {
            if (!isset($mauticTables[$t]) && !in_array($t, $mauticTables)) {
                // Not an applicable table
                continue;
            }

            $restraints = $sm->introspectTableForeignKeyConstraintsByUnquotedName($t);

            if (isset($mauticTables[$t])) {
                // to be backed up
                $backupRestraints[$mauticTables[$t]] = $restraints;
                $backupTables[$t]                    = $mauticTables[$t];
                $backupIndexes[$t]                   = $sm->introspectTableIndexesByUnquotedName($t);
            } else {
                // existing backup to be dropped
                $dropTables[] = $t;
            }

            foreach ($restraints as $restraint) {
                $sql[] = $this->platform->getDropForeignKeySQL(self::constraintName($restraint), $t);
            }
        }

        // now drop all the backup tables
        foreach ($dropTables as $t) {
            $sql[] = $this->platform->getDropTableSQL($t);
        }

        // now backup tables
        foreach ($backupTables as $t => $backup) {
            // drop old indexes
            /** @var Index $oldIndex */
            foreach ($backupIndexes[$t] as $indexName => $oldIndex) {
                if ('primary' == $indexName) {
                    continue;
                }

                $oldName = AssetName::of($oldIndex);
                $newName = $this->generateBackupName($this->dbParams['table_prefix'], $backupPrefix, $oldName);

                $newIndex = new Index(
                    $newName,
                    self::indexColumnNames($oldIndex),
                    IndexType::UNIQUE === $oldIndex->getType(),
                    // primary keys are skipped above, and are a PrimaryKeyConstraint in DBAL 4 rather than an index type
                    false,
                    $oldIndex->isClustered() ? ['clustered'] : [],
                    null !== $oldIndex->getPredicate() ? ['where' => $oldIndex->getPredicate()] : []
                );

                $newIndexes[] = $newIndex;
                $sql[]        = $this->platform->getDropIndexSQL(AssetName::of($oldIndex), $t);
            }

            // rename table
            // DBAL 4 returns a single statement here rather than a list of them
            $sql[] = $this->platform->getRenameTableSQL($t, $backup);

            // create new index
            if (!empty($newIndexes)) {
                foreach ($newIndexes as $newIndex) {
                    $sql[] = $this->platform->getCreateIndexSQL($newIndex, $backup);
                }
                unset($newIndexes);
            }
        }

        // apply foreign keys to backup tables
        foreach ($backupRestraints as $table => $oldRestraints) {
            foreach ($oldRestraints as $or) {
                $foreignTable     = AssetName::fromName($or->getReferencedTableName());
                $foreignTableName = $this->generateBackupName($this->dbParams['table_prefix'], $backupPrefix, $foreignTable);
                $r                = new ForeignKeyConstraint(
                    self::namesToStrings($or->getReferencingColumnNames()),
                    $foreignTableName,
                    self::namesToStrings($or->getReferencedColumnNames()),
                    $backupPrefix.self::constraintName($or),
                    null !== $or->getMatchType() ? ['match' => $or->getMatchType()] : []
                );
                $sql[] = $this->platform->getCreateForeignKeySQL($r, $table);
            }
        }

        return $sql;
    }

    /**
     * @param string[]              $tables
     * @param array<string, string> $mauticTables
     */
    private function dropExistingSchema(array $tables, array $mauticTables): array
    {
        $sql = [];

        // drop tables
        foreach ($tables as $t) {
            if (isset($mauticTables[$t])) {
                $sql[] = $this->platform->getDropTableSQL($t);
            }
        }

        return $sql;
    }

    private function generateBackupName(?string $prefix, string $backupPrefix, string $name): string
    {
        if (empty($prefix) || !str_contains($name, $prefix)) {
            return $backupPrefix.$name;
        }

        return str_replace($prefix, $backupPrefix, $name);
    }

    /**
     * @return AbstractSchemaManager<AbstractPlatform>
     */
    private function getSchemaManager(): AbstractSchemaManager
    {
        if (null !== $this->schemaManager) {
            return $this->schemaManager;
        }

        return $this->schemaManager = $this->db->createSchemaManager();
    }

    /**
     * A foreign key's name is optional in DBAL 4, so it is read through the nullable
     * getObjectName() rather than AssetName::of(), which expects an always-named object.
     */
    private static function constraintName(ForeignKeyConstraint $constraint): string
    {
        $name = $constraint->getObjectName();

        return null === $name ? '' : AssetName::fromName($name);
    }

    /**
     * @return list<string>
     */
    private static function indexColumnNames(Index $index): array
    {
        return array_map(
            static fn (IndexedColumn $indexedColumn): string => AssetName::fromName($indexedColumn->getColumnName()),
            $index->getIndexedColumns()
        );
    }

    /**
     * @param list<UnqualifiedName> $names
     *
     * @return list<string>
     */
    private static function namesToStrings(array $names): array
    {
        return array_map(AssetName::fromName(...), $names);
    }
}
