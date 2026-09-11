<?php

namespace Mautic\InstallBundle\Helper;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Index;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Tools\SchemaTool;
use Mautic\CoreBundle\Doctrine\DatabasePlatform;
use Mautic\CoreBundle\Doctrine\Provider\VersionProvider;
use Mautic\CoreBundle\Release\ThisRelease;
use Mautic\InstallBundle\Exception\DatabaseVersionTooOldException;

final class SchemaHelper
{
    private Connection $db;

    private ?EntityManagerInterface $em = null;

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
     * @throws DBALException
     */
    public function __construct(array $dbParams)
    {
        // suppress display of errors as we know its going to happen while testing the connection
        ini_set('display_errors', '0');

        // Support for env variables
        foreach ($dbParams as &$v) {
            if (!empty($v) && is_string($v) && preg_match('/getenv\((.*?)\)/', $v, $match)) {
                $v = (string) getenv($match[1]);
            }
        }

        if (isset($dbParams['name'])) {
            $dbParams['dbname'] = $dbParams['name'];
            unset($dbParams['name']);
        }

        $this->db = DriverManager::getConnection($dbParams);

        $this->dbParams = $dbParams;
    }

    public function setEntityManager(EntityManagerInterface $em): void
    {
        $this->em = $em;
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

            $db->connect();
            $db->close();
        } else {
            $this->db->connect();
            $this->db->close();
        }
    }

    public function createDatabase(): bool
    {
        try {
            $this->db->connect();
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
     * @throws DBALException
     * @throws ORMException
     */
    public function installSchema(): bool
    {
        $sm = $this->getSchemaManager();

        try {
            // check to see if the table already exist
            $tables = $sm->listTableNames();
        } catch (\Exception $e) {
            $this->db->close();

            throw $e;
        }

        $this->platform = $this->db->getDatabasePlatform();
        $backupPrefix   = (!empty($this->dbParams['backup_prefix'])) ? $this->dbParams['backup_prefix'] : 'bak_';

        $metadatas = $this->em->getMetadataFactory()->getAllMetadata();
        if (empty($metadatas)) {
            $this->db->close();

            return false;
        }

        $schemaTool    = new SchemaTool($this->em);
        $installSchema = $schemaTool->getSchemaFromMetadata($metadatas);
        $mauticTables  = [];

        foreach ($installSchema->getTables() as $m) {
            $tableName                = $m->getName();
            $mauticTables[$tableName] = $this->generateBackupName($this->dbParams['table_prefix'], $backupPrefix, $tableName);
        }

        // Add Doctrine-managed OAuth tables which are missing from metadata
        $doctrineDirectTables = [
            'oauth2_accesstokens',
            'oauth2_authcodes',
            'oauth2_clients',
            'oauth2_refreshtokens',
        ];
        foreach ($doctrineDirectTables as $table) {
            if (!isset($mauticTables[$table])) {
                $mauticTables[$table] = $this->generateBackupName($this->dbParams['table_prefix'], $backupPrefix, $table);
            }
        }

        $noForeignKeyChecks = DatabasePlatform::isPostgreSQL($this->em->getConnection()->getDatabasePlatform());
        $sql                = $noForeignKeyChecks ? [] : ['SET foreign_key_checks = 0;'];
        if ($this->dbParams['backup_tables']) {
            $sql = array_merge($sql, $this->backupExistingSchema($tables, $mauticTables, $backupPrefix));
        } else {
            $sql = array_merge($sql, $this->dropExistingSchema($tables, $mauticTables));
        }

        $sql = array_merge($sql, $installSchema->toSql($this->platform));

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
        $versionProvider = new VersionProvider($this->db);

        // Version strings are in the format:
        // 10.3.30-MariaDB-1:10.3.30+maria~focal-log
        // PostgreSQL 16.11 (Ubuntu 16.11-0ubuntu0.24.04.1) on x86_64-pc-linux-gnu, compiled by gcc (Ubuntu 13.3.0-6ubuntu2~24.04) 13.3.0, 64-bit
        $version = VersionProvider::getNumericVersion($versionProvider->getVersion());

        // Platform class names are in the format Doctrine\DBAL\Platforms\MariaDb1027Platform
        $platform = strtolower($this->db->getDatabasePlatform()::class);
        $metadata = ThisRelease::getMetadata();

        /**
         * The second case is for MariaDB < 10.2, where Doctrine reports it as MySQLPlatform. Here we can use a little
         * help from the version string, which contains "MariaDB" in that case: 10.1.48-MariaDB-1~bionic.
         */
        if (str_contains($platform, 'mariadb')) {
            $minSupported = $metadata->getMinSupportedMariaDbVersion();
        } elseif (str_contains($platform, 'mysql')) {
            $minSupported = $metadata->getMinSupportedMySqlVersion();
        } elseif (str_contains($platform, 'postgresql')) {
            $minSupported = $metadata->getMinSupportedPostgreSqlVersion();
        } else {
            throw new DBALException('Invalid database platform '.$platform.'. Mautic only supports MySQL, MariaDB and PostgreSQL.');
        }

        if (version_compare($version, $minSupported, '<')) {
            throw new DatabaseVersionTooOldException($version);
        }
    }

    /**
     * @throws DBALException
     */
    private function backupExistingSchema($tables, array $mauticTables, $backupPrefix): array
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

            $restraints = $sm->listTableForeignKeys($t);
            $sequences  = [];

            if (DatabasePlatform::isPostgreSQL($this->platform)) {
                foreach ($sm->listTableColumns($t) as $c) {
                    /*
                      * Can't use $c->getAutoincrement() check as doctrine dont set
                      * sequence ownership to column/table for postgresql
                      * need to check all
                      */
                    try {
                        $sequence = DatabasePlatform::getSerialSequence($this->db, $t, $c->getName());

                        if ($sequence) {
                            $sql[] = $this->platform->getDropSequenceSQL($sequence);
                        }
                    } catch (DBALException) {
                        // suppress
                    }
                }
            }

            if (isset($mauticTables[$t])) {
                // to be backed up
                $backupRestraints[$mauticTables[$t]] = $restraints;
                $backupTables[$t]                    = $mauticTables[$t];
                $backupIndexes[$t]                   = $sm->listTableIndexes($t);
                $backupSequences[$t]                 = $sequences;
            } else {
                // existing backup to be dropped
                $dropTables[]    = $t;
                $dropSequences[] = $sequence;
            }

            foreach ($restraints as $restraint) {
                $sql[] = $this->platform->getDropForeignKeySQL($restraint, $t);
            }
        }

        // now drop all the backup tables
        foreach ($dropSequences as $s) {
            $sql[] = $this->platform->getDropSequenceSQL($s);
        }

        foreach ($dropTables as $t) {
            $dropSql = $this->platform->getDropTableSQL($t);
            if (DatabasePlatform::isPostgreSQL($this->platform)) {
                // this prevent constraint on tables
                $dropSql .= ' CASCADE';
            }

            $sql[] = $dropSql;
        }

        // now backup tables
        foreach ($backupTables as $t => $backup) {
            // drop old indexes
            /** @var Index $oldIndex */
            foreach ($backupIndexes[$t] as $indexName => $oldIndex) {
                if ('primary' == $indexName) {
                    continue;
                }

                $oldName = $oldIndex->getName();
                $newName = $this->generateBackupName($this->dbParams['table_prefix'], $backupPrefix, $oldName);

                $newIndex = new Index(
                    $newName,
                    $oldIndex->getColumns(),
                    $oldIndex->isUnique(),
                    $oldIndex->isPrimary(),
                    $oldIndex->getFlags(),
                    $oldIndex->getOptions()
                );

                $newIndexes[] = $newIndex;
                $sql[]        = $this->platform->getDropIndexSQL($oldIndex, $t);
            }

            // rename table
            $queries = $this->platform->getRenameTableSQL($t, $backup);
            $sql     = array_merge($sql, $queries);

            // rename sequences
            if (!empty($backupSequences[$t])) {
                foreach ($backupSequences[$t] as $oldSequence) {
                    $newSequence = str_replace($t, $backup, $oldSequence);
                    $sql[]       = 'ALTER SEQUENCE '.$this->db->quoteIdentifier($oldSequence).' RENAME TO '.$this->db->quoteIdentifier($newSequence);
                }
            }

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
                $foreignTable     = $or->getForeignTableName();
                $foreignTableName = $this->generateBackupName($this->dbParams['table_prefix'], $backupPrefix, $foreignTable);
                $r                = new ForeignKeyConstraint(
                    $or->getLocalColumns(),
                    $foreignTableName,
                    $or->getForeignColumns(),
                    $backupPrefix.$or->getName(),
                    $or->getOptions()
                );
                $sql[] = $this->platform->getCreateForeignKeySQL($r, $table);
            }
        }

        return $sql;
    }

    private function dropExistingSchema($tables, array $mauticTables): array
    {
        $sql = [];
        $sm  = $this->getSchemaManager();

        // drop tables
        foreach ($tables as $t) {
            if (isset($mauticTables[$t])) {
                if (DatabasePlatform::isPostgreSQL($this->platform)) {
                    foreach ($sm->listTableColumns($t) as $c) {
                        /*
                         * Can't use $c->getAutoincrement() check as doctrine dont set
                         * sequence ownership to column/table for postgresql
                         * need to check all
                         */
                        try {
                            $sequence = DatabasePlatform::getSerialSequence($this->db, $t, $c->getName());

                            if ($sequence) {
                                $sql[] = $this->platform->getDropSequenceSQL($sequence);
                            }
                        } catch (DBALException) {
                            // suppress
                        }
                    }
                }

                $dropSql = $this->platform->getDropTableSQL($t);
                if (DatabasePlatform::isPostgreSQL($this->platform)) {
                    // this prevent constraint on table test_assets depends on table test_categories errors
                    $dropSql .= ' CASCADE';
                }
                $sql[] = $dropSql;
            }
        }

        return $sql;
    }

    /**
     * @return mixed[]|string
     */
    private function generateBackupName($prefix, $backupPrefix, $name): string|array
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
}
