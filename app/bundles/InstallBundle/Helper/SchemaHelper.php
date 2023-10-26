<?php

namespace Mautic\InstallBundle\Helper;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\TableDiff;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Tools\SchemaTool;
use Mautic\CoreBundle\Release\ThisRelease;
use Mautic\InstallBundle\Exception\DatabaseVersionTooOldException;

class SchemaHelper
{
    /**
     * @var Connection
     */
    protected $db;

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var AbstractPlatform
     */
    protected $platform;

    /**
     * @var array
     */
    protected $dbParams = [];

    /**
     * @throws DBALException
     */
    public function __construct(array $dbParams)
    {
        //suppress display of errors as we know its going to happen while testing the connection
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

    public function setEntityManager(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Test db connection.
     */
    public function testConnection()
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

    /**
     * @return bool
     *
     * @throws DBALException
     */
    public function createDatabase()
    {
        try {
            $this->db->connect();
        } catch (\Exception $exception) {
            //it failed to connect so remove the dbname and try to create it
            $dbName                   = $this->dbParams['dbname'];
            $this->dbParams['dbname'] = null;
            $this->db                 = DriverManager::getConnection($this->dbParams);

            try {
                //database does not exist so try to create it
                $this->db->getSchemaManager()->createDatabase($dbName);

                //close the connection and reconnect with the new database name
                $this->db->close();

                $this->dbParams['dbname'] = $dbName;
                $this->db                 = DriverManager::getConnection($this->dbParams);
                $this->db->close();
            } catch (\Exception $e) {
                return false;
            }
        }

        return true;
    }

    /**
     * Generates SQL for installation.
     *
     * @return array|bool Array containing the flash message data on a failure, boolean true on success
     *
     * @throws DBALException
     * @throws ORMException
     */
    public function installSchema()
    {
        $sm = $this->db->getSchemaManager();

        try {
            //check to see if the table already exist
            $tables = $sm->listTableNames();
        } catch (\Exception $e) {
            $this->db->close();

            throw $e;
        }

        $this->platform = $sm->getDatabasePlatform();
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

        $sql = 'sqlite' === $this->em->getConnection()->getDatabasePlatform()->getName() ? [] : ['SET foreign_key_checks = 0;'];
        if ($this->dbParams['backup_tables']) {
            $sql = array_merge($sql, $this->backupExistingSchema($tables, $mauticTables, $backupPrefix));
        } else {
            $sql = array_merge($sql, $this->dropExistingSchema($tables, $mauticTables));
        }

        $sql = array_merge($sql, $installSchema->toSql($this->platform));

        // Execute drop queries
        if (!empty($sql)) {
            foreach ($sql as $q) {
                try {
                    $this->db->query($q);
                } catch (\Exception $exception) {
                    $this->db->close();

                    throw $exception;
                }
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
        $platform = strtolower(get_class($this->db->getDatabasePlatform()));
        $metadata = ThisRelease::getMetadata();

        /**
         * The second case is for MariaDB < 10.2, where Doctrine reports it as MySQLPlatform. Here we can use a little
         * help from the version string, which contains "MariaDB" in that case: 10.1.48-MariaDB-1~bionic.
         */
        if (false !== strpos($platform, 'mariadb') || false !== strpos(strtolower($version), 'mariadb')) {
            $minSupported = $metadata->getMinSupportedMariaDbVersion();
        } elseif (false !== strpos($platform, 'mysql')) {
            $minSupported = $metadata->getMinSupportedMySqlVersion();
        } else {
            throw new \Exception('Invalid database platform '.$platform.'. Mautic only supports MySQL and MariaDB!');
        }

        if (true !== version_compare($version, $minSupported, 'gt')) {
            throw new DatabaseVersionTooOldException($version);
        }
    }

    /**
     * @param $tables
     * @param $backupPrefix
     *
     * @return array
     *
     * @throws DBALException
     */
    protected function backupExistingSchema($tables, $mauticTables, $backupPrefix)
    {
        $sql = [];
        $sm  = $this->db->getSchemaManager();

        //backup existing tables
        $backupRestraints = $backupSequences = $backupIndexes = $backupTables = $dropSequences = $dropTables = [];

        //cycle through the first time to drop all the foreign keys
        foreach ($tables as $t) {
            if (!isset($mauticTables[$t]) && !in_array($t, $mauticTables)) {
                // Not an applicable table
                continue;
            }

            $restraints = $sm->listTableForeignKeys($t);

            if (isset($mauticTables[$t])) {
                //to be backed up
                $backupRestraints[$mauticTables[$t]] = $restraints;
                $backupTables[$t]                    = $mauticTables[$t];
                $backupIndexes[$t]                   = $sm->listTableIndexes($t);
            } else {
                //existing backup to be dropped
                $dropTables[] = $t;
            }

            foreach ($restraints as $restraint) {
                $sql[] = $this->platform->getDropForeignKeySQL($restraint, $t);
            }
        }

        //now drop all the backup tables
        foreach ($dropTables as $t) {
            $sql[] = $this->platform->getDropTableSQL($t);
        }

        //now backup tables
        foreach ($backupTables as $t => $backup) {
            //drop old indexes
            /** @var \Doctrine\DBAL\Schema\Index $oldIndex */
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

            //rename table
            $tableDiff          = new TableDiff($t);
            $tableDiff->newName = $backup;
            $queries            = $this->platform->getAlterTableSQL($tableDiff);
            $sql                = array_merge($sql, $queries);

            //create new index
            if (!empty($newIndexes)) {
                foreach ($newIndexes as $newIndex) {
                    $sql[] = $this->platform->getCreateIndexSQL($newIndex, $backup);
                }
                unset($newIndexes);
            }
        }

        //apply foreign keys to backup tables
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

    /**
     * @param $tables
     * @param $mauticTables
     *
     * @return array
     */
    protected function dropExistingSchema($tables, $mauticTables)
    {
        $sql = [];

        //drop tables
        foreach ($tables as $t) {
            if (isset($mauticTables[$t])) {
                $sql[] = $this->platform->getDropTableSQL($t);
            }
        }

        return $sql;
    }

    /**
     * @param $prefix
     * @param $backupPrefix
     * @param $name
     *
     * @return mixed|string
     */
    protected function generateBackupName($prefix, $backupPrefix, $name)
    {
        if (empty($prefix) || false === strpos($name, $prefix)) {
            return $backupPrefix.$name;
        } else {
            return str_replace($prefix, $backupPrefix, $name);
        }
    }
}
