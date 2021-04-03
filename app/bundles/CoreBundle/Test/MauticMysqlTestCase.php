<?php

namespace Mautic\CoreBundle\Test;

use Exception;
use LogicException;
use Mautic\InstallBundle\InstallFixtures\ORM\LeadFieldData;
use Mautic\InstallBundle\InstallFixtures\ORM\RoleData;
use Mautic\UserBundle\DataFixtures\ORM\LoadRoleData;
use Mautic\UserBundle\DataFixtures\ORM\LoadUserData;

abstract class MauticMysqlTestCase extends AbstractMauticTestCase
{
    /**
     * @var bool
     */
    private static $databasePrepared = false;

    /**
     * Use transaction rollback for cleanup. Sometimes it is not possible to use it because of the following:
     *     1. A query that alters a DB schema causes an open transaction being committed immediately.
     *     2. Full-text search does not see uncommitted changes.
     *
     * @var bool
     */
    protected $useCleanupRollback = true;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        if (!self::$databasePrepared) {
            $this->prepareDatabase();
            self::$databasePrepared = true;
        }

        if ($this->useCleanupRollback) {
            $this->beforeBeginTransaction();
            $this->connection->beginTransaction();
        }
    }

    protected function tearDown(): void
    {
        if ($this->useCleanupRollback) {
            if ($this->connection->isTransactionActive()) {
                $this->connection->rollback();
            }
        } else {
            $this->prepareDatabase();
        }

        $this->restoreShellVerbosity();

        parent::tearDown();
    }

    /**
     * Override this method to execute some logic right before the transaction begins.
     */
    protected function beforeBeginTransaction(): void
    {
    }

    protected function setUpSymfony(array $defaultConfigOptions = []): void
    {
        if ($this->useCleanupRollback && isset($this->client)) {
            throw new LogicException('You cannot re-create the client when a transaction rollback for cleanup is enabled. Turn it off using $useCleanupRollback property or avoid re-creating a client.');
        }

        parent::setUpSymfony($defaultConfigOptions);
    }

    /**
     * Helper method that eases resetting auto increment values for passed $tables.
     * You should avoid using this method as relying on fixed auto-increment values makes tests more fragile.
     * For example, you should never assume that IDs of first three records are always 1, 2 and 3.
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function resetAutoincrement(array $tables): void
    {
        $prefix     = self::$container->getParameter('mautic.db_table_prefix');
        $connection = $this->connection;

        foreach ($tables as $table) {
            $connection->query(sprintf('ALTER TABLE `%s%s` AUTO_INCREMENT=1', $prefix, $table));
        }
    }

    /**
     * Warning: To perform Truncate on tables with foreign keys we have to turn off the foreign keys temporarily.
     * This may lead to corrupted data. Make sure you know what you are doing.
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function truncateTables(string ...$tables): void
    {
        $prefix = MAUTIC_TABLE_PREFIX;

        foreach ($tables as $table) {
            $this->connection->query("SET FOREIGN_KEY_CHECKS = 0; TRUNCATE TABLE `{$prefix}{$table}`; SET FOREIGN_KEY_CHECKS = 1;");
        }
    }

    /**
     * @param $file
     *
     * @throws Exception
     */
    private function applySqlFromFile($file)
    {
        $connection = $this->connection;
        $password   = ($connection->getPassword()) ? " -p{$connection->getPassword()}" : '';
        $command    = "mysql -h{$connection->getHost()} -P{$connection->getPort()} -u{$connection->getUsername()}$password {$connection->getDatabase()} < {$file} 2>&1 | grep -v \"Using a password\" || true";

        $lastLine = system($command, $status);

        if (0 !== $status) {
            throw new Exception($command.' failed with status code '.$status.' and last line of "'.$lastLine.'"');
        }
    }

    /**
     * Reset each test using a SQL file if possible to prevent from having to run the fixtures over and over.
     *
     * @throws Exception
     */
    private function prepareDatabase()
    {
        if (!function_exists('system')) {
            $this->installDatabase();

            return;
        }

        $sqlDumpFile = self::$container->getParameter('kernel.cache_dir').'/fresh_db.sql';

        if (!file_exists($sqlDumpFile)) {
            $this->installDatabase();
            $this->dumpToFile($sqlDumpFile);

            return;
        }

        $this->applySqlFromFile($sqlDumpFile);
    }

    /**
     * @throws Exception
     */
    private function installDatabase()
    {
        $this->createDatabase();
        $this->applyMigrations();
        $this->installDatabaseFixtures([LeadFieldData::class, RoleData::class, LoadRoleData::class, LoadUserData::class]);
    }

    /**
     * @throws Exception
     */
    private function createDatabase()
    {
        $this->runCommand(
            'doctrine:database:drop',
            [
                '--env'   => 'test',
                '--force' => true,
            ]
        );

        $this->runCommand(
            'doctrine:database:create',
            [
                '--env' => 'test',
            ]
        );

        $this->runCommand(
            'doctrine:schema:create',
            [
                '--env' => 'test',
            ]
        );
    }

    /**
     * @throws Exception
     */
    private function dumpToFile(string $sqlDumpFile): void
    {
        $password   = ($this->connection->getPassword()) ? " -p{$this->connection->getPassword()}" : '';
        $command    = "mysqldump --add-drop-table --opt -h{$this->connection->getHost()} -P{$this->connection->getPort()} -u{$this->connection->getUsername()}$password {$this->connection->getDatabase()} > {$sqlDumpFile} 2>&1 | grep -v \"Using a password\" || true";

        $lastLine = system($command, $status);
        if (0 !== $status) {
            throw new Exception($command.' failed with status code '.$status.' and last line of "'.$lastLine.'"');
        }

        $f         = fopen($sqlDumpFile, 'r');
        $firstLine = fgets($f);
        if (false !== strpos($firstLine, 'Using a password')) {
            $file = file($sqlDumpFile);
            unset($file[0]);
            file_put_contents($sqlDumpFile, $file);
        }
        fclose($f);
    }

    /**
     * Restores the shell verbosity that might be set by Symfony console globally.
     *
     * @see \Symfony\Component\Console\Application::configureIO()
     */
    private function restoreShellVerbosity(): void
    {
        $defaultVerbosity=0;
        putenv('SHELL_VERBOSITY='.$defaultVerbosity);
        $_ENV['SHELL_VERBOSITY']    = $defaultVerbosity;
        $_SERVER['SHELL_VERBOSITY'] = $defaultVerbosity;
    }
}
