<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Test;

use Doctrine\DBAL\Exception as DBALException;
use Mautic\CoreBundle\Doctrine\DatabasePlatform;
use Mautic\CacheBundle\Cache\CacheProvider;
use Mautic\InstallBundle\InstallFixtures\ORM\LeadFieldData;
use Mautic\InstallBundle\InstallFixtures\ORM\RoleData;
use Mautic\UserBundle\DataFixtures\ORM\LoadRoleData;
use Mautic\UserBundle\DataFixtures\ORM\LoadUserData;
use Mautic\UserBundle\Entity\User;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

abstract class MauticMysqlTestCase extends AbstractMauticTestCase
{
    private const TRUNCATE_TABLE_SQL = 'TRUNCATE TABLE';

    private bool $databaseInstalled = false;

    private bool $setUpInvoked      = false;

    /**
     * Use transaction rollback for cleanup. Sometimes it is not possible to use it because of the following:
     *     1. A query that alters a DB schema causes an open transaction being committed immediately.
     *     2. Full-text search does not see uncommitted changes.
     *
     * @var bool
     */
    protected $useCleanupRollback = true;

    public function __construct(?string $name = null)
    {
        parent::__construct($name);

        // Only default to MySQL if no DB_DRIVER is not set
        if (!isset($this->configParams['db_driver']) || empty($this->configParams['db_driver'])) {
            $this->configParams['db_driver'] = 'pdo_mysql';
        }
        // Initialize default charset if no DB_CHARSET is set
        if (!isset($this->configParams['db_charset']) || empty($this->configParams['db_charset'])) {
            $this->configParams['db_charset'] = 'pdo_pgsql' == $this->configParams['db_driver'] ? 'UTF8' : 'utf8mb4';
        }
    }

    protected function isMysqlPlatform(): bool
    {
        // if its not PostgreSQL, we treat is as MySQL
        return !$this->isPostgresqlPlatform();
    }

    protected function isPostgresqlPlatform(): bool
    {
        return DatabasePlatform::isPostgreSQL($this->connection->getDatabasePlatform());
    }

    /**
     * @throws \Exception
     */
    protected function setUp(): void
    {
        $this->setUpInvoked = true;

        parent::setUp();
        $this->backupLocalConfig();

        if (!$this->isDatabasePrepared()) {
            $this->prepareDatabase();

            if ($this->databaseInstalled) {
                // re-create client/container as some services can be already wired
                parent::setUpSymfony($this->configParams);
            }

            $this->markDatabasePrepared();
        }

        $user = $this->em->getRepository(User::class)->findOneBy(['username' => $this->clientServer['PHP_AUTH_USER'] ?? 'admin']);
        $this->loginUser($user); // also creates session

        if ($this->useCleanupRollback) {
            $this->beforeBeginTransaction();
            $this->connection->beginTransaction();
        }
    }

    /**
     * @see beforeTearDown()
     */
    final protected function tearDown(): void
    {
        date_default_timezone_set('UTC');
        $this->restoreLocalConfig();
        $customFieldsReset = $this->resetCustomFields();
        $this->beforeTearDown();

        if (!$this->setUpInvoked) {
            throw new \LogicException('You omitted invoking parent::setUp(). This may lead to side effects.');
        }

        $isTransactionActive = $this->connection->isTransactionActive();

        if ($isTransactionActive && $this->useCleanupRollback) {
            $this->insertRollbackCheckData();
            $this->connection->rollback();
        }

        $this->afterRollback();

        if (!$this->useCleanupRollback || !$isTransactionActive || $customFieldsReset || !$this->wasRollbackSuccessful()) {
            $this->resetDatabase();
        }

        $this->restoreShellVerbosity();
        $this->clearCache();

        parent::tearDown();
    }

    /**
     * Override this method to execute some logic right before the transaction begins.
     */
    protected function beforeBeginTransaction(): void
    {
    }

    /**
     * Override this method to execute some logic right before the tearDown() is invoked.
     */
    protected function beforeTearDown(): void
    {
    }

    /**
     * Override this method to execute some logic right after the transaction ends.
     */
    protected function afterRollback(): void
    {
    }

    protected function setUpSymfony(array $defaultConfigOptions = []): void
    {
        if ($this->useCleanupRollback && isset($this->client)) {
            throw new \LogicException('You cannot re-create the client when a transaction rollback for cleanup is enabled. Turn it off using $useCleanupRollback property or avoid re-creating a client.');
        }

        self::ensureKernelShutdown();
        parent::setUpSymfony($defaultConfigOptions);
    }

    /**
     * Helper method that eases resetting auto increment values for passed $tables.
     * You should avoid using this method as relying on fixed auto-increment values makes tests more fragile.
     * For example, you should never assume that IDs of first three records are always 1, 2 and 3.
     *
     * @throws DBALException
     */
    protected function resetAutoincrement(array $tables): void
    {
        $prefix = $this->getTablePrefix();

        foreach ($tables as $table) {
            $fullTable = $prefix.$table;

            if ($this->isMysqlPlatform()) {
                $this->connection->executeStatement(sprintf('ALTER TABLE `%s` AUTO_INCREMENT=1', $fullTable));
            } elseif ($this->isPostgresqlPlatform()) {
                $sequence = DatabasePlatform::getSerialSequence($this->connection, $fullTable);

                if ($sequence) {
                    $quotedSequence = $this->connection->quoteIdentifier($sequence);
                    $this->connection->executeStatement("ALTER SEQUENCE $quotedSequence RESTART WITH 1");
                }
            }
        }
    }

    /**
     * Warning: To perform Truncate on tables with foreign keys we have to turn off the foreign keys temporarily.
     * This may lead to corrupted data. Make sure you know what you are doing.
     *
     * @throws DBALException
     */
    protected function truncateTables(string ...$tables): void
    {
        $prefix = $this->getTablePrefix();
        if ($this->isMysqlPlatform()) {
            $this->connection->executeQuery('SET FOREIGN_KEY_CHECKS = 0');
        }
        foreach ($tables as $table) {
            $fullTable    = $prefix.$table;
            $quotedTable  = $this->connection->quoteIdentifier($fullTable);

            $sql = self::TRUNCATE_TABLE_SQL.' '.$quotedTable;

            if ($this->isPostgresqlPlatform()) {
                // Reset sequences (equivalent to MySQL AUTO_INCREMENT reset)
                // and cascade to handle foreign key references (equivalent to disabling checks)
                $sql .= ' RESTART IDENTITY CASCADE';
            }

            $this->connection->executeQuery($sql);
        }
        if ($this->isMysqlPlatform()) {
            $this->connection->executeQuery('SET FOREIGN_KEY_CHECKS = 1');
        }
    }

    protected function loadEssentialFixtures(): void
    {
        $this->installDatabaseFixtures([
            LeadFieldData::class,
            RoleData::class,
            LoadRoleData::class,
            LoadUserData::class,
        ]);
    }

    /**
     * @throws \InvalidArgumentException
     * @throws ProcessFailedException
     */
    private function applySqlFromFile(string $file): void
    {
        $connectionParams = $this->connection->getParams();

        if ($this->isMysqlPlatform()) {
            // Existing mysql command (unchanged)
            $password = $connectionParams['password'] ? '-p'.escapeshellarg($connectionParams['password']) : '';
            $command  = sprintf(
                'mysql -h%s -P%s -u%s %s %s < %s',
                escapeshellarg($connectionParams['host']),
                escapeshellarg((string) $connectionParams['port']),
                escapeshellarg($connectionParams['user']),
                $password,
                escapeshellarg($connectionParams['dbname']),
                escapeshellarg($file)
            );
        } elseif ($this->isPostgresqlPlatform()) {
            // Use psql for PostgreSQL
            $password    = $connectionParams['password'] ?? '';
            $passwordCmd = $password ? "export PGPASSWORD={$password};" : '';
            $command     = $passwordCmd.sprintf(
                'psql -h %s -p %s -U %s -d %s -f %s',
                escapeshellarg($connectionParams['host']),
                escapeshellarg((string) ($connectionParams['port'] ?? 5432)),
                escapeshellarg($connectionParams['user']),
                escapeshellarg($connectionParams['dbname']),
                escapeshellarg($file)
            );
        } else {
            throw new \InvalidArgumentException('Unsupported database platform: '.$this->connection->getDatabasePlatform()::class);
        }

        $process = Process::fromShellCommandline($command);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }

    /**
     * Reset each test using a SQL file if possible to prevent from having to run the fixtures over and over.
     *
     * @throws \Exception
     */
    private function prepareDatabase(): void
    {
        if (!function_exists('proc_open')) {
            $this->installDatabase();

            return;
        }

        $sqlDumpFile = $this->getSqlFilePath('fresh_db');

        if (!file_exists($sqlDumpFile)) {
            $this->installDatabase();

            if ($this->databaseInstalled && $this->isMysqlPlatform()) {
                // Only generate full dump and reset SQL for MySQL
                $this->dumpToFile($sqlDumpFile);
                $this->generateResetDatabaseSql($this->getSqlFilePath('reset_db'));
            } elseif ($this->databaseInstalled && $this->isPostgresqlPlatform()) {
                // Generate fast TRUNCATE-based reset SQL for PostgreSQL
                $this->generateResetDatabaseSql($this->getSqlFilePath('reset_db'));
            }

            return;
        }

        $this->applySqlFromFile($sqlDumpFile);
    }

    private function resetDatabase(): void
    {
        $resetFile = $this->getSqlFilePath('reset_db');

        if (file_exists($resetFile) && ($this->isMysqlPlatform() || $this->isPostgresqlPlatform())) {
            $this->applySqlFromFile($resetFile);

            // PostgreSQL needs essential fixtures reloaded after TRUNCATE
            if ($this->isPostgresqlPlatform()) {
                $this->loadEssentialFixtures();
            }
        } else {
            // Fallback (rare)
            if ($this->isPostgresqlPlatform()) {
                $prefix        = $this->getTablePrefix();
                $schemaManager = $this->connection->createSchemaManager();
                $tables        = $schemaManager->listTableNames();

                $prefixedTables = array_filter($tables, fn (string $table): bool => str_starts_with($table, $prefix));

                if (!empty($prefixedTables)) {
                    $quotedTables = array_map($this->connection->quoteIdentifier(...), $prefixedTables);
                    $this->connection->executeStatement(
                        self::TRUNCATE_TABLE_SQL.' '.implode(', ', $quotedTables).' RESTART IDENTITY CASCADE'
                    );
                }

                $this->loadEssentialFixtures();
            }
        }
    }

    /**
     * @throws \Exception
     */
    private function installDatabase(): void
    {
        $this->createDatabase();
        $this->applyMigrations();
        $this->installDatabaseFixtures([LeadFieldData::class, RoleData::class, LoadRoleData::class, LoadUserData::class]);
        $this->databaseInstalled = true;
    }

    private function createDatabase(): void
    {
        if (!$this->isPostgresqlPlatform()) {
            // DROP doesnt work on postgresq with existing connection
            $this->testSymfonyCommand('doctrine:database:drop', ['--if-exists' => true, '--force' => true]);
        }
        $this->testSymfonyCommand('doctrine:database:create', ['--if-not-exists' => true]);
        if ($this->isPostgresqlPlatform()) {
            // Database can't be dropped if there is existing connection (drop schema instead)
            $this->testSymfonyCommand('doctrine:schema:drop', ['--force' => true, '--full-database' => true]);
        }
        $this->testSymfonyCommand('doctrine:schema:create');
        $this->testSymfonyCommand('doctrine:migration:sync-metadata-storage');
    }

    private function generateResetDatabaseSql(string $file): void
    {
        if ($this->isMysqlPlatform()) {
            $this->generateMysqlResetSql($file);
        } elseif ($this->isPostgresqlPlatform()) {
            $this->generatePostgresqlResetSql($file);
        }
    }

    private function generateMysqlResetSql(string $file): void
    {
        $content = 'SET autocommit=0;'.PHP_EOL;
        $content .= 'SET unique_checks=0;'.PHP_EOL;
        $content .= 'SET FOREIGN_KEY_CHECKS=0;'.PHP_EOL;

        $tables = $this->connection->executeQuery('SELECT TABLE_NAME FROM information_schema.tables WHERE table_type = "BASE TABLE" AND table_schema = ?', [$this->connection->getParams()['dbname']])
            ->fetchFirstColumn();

        foreach ($tables as $table) {
            $content .= sprintf('DELETE FROM %s;'.PHP_EOL, $table);
        }

        $password = ($this->connection->getParams()['password']) ? " -p{$this->connection->getParams()['password']}" : '';
        $command  = "mysqldump --set-gtid-purged=OFF --skip-triggers --compact --no-create-info --skip-opt --single-transaction --opt -h{$this->connection->getParams()['host']} -P{$this->connection->getParams()['port']} -u{$this->connection->getParams()['user']}$password {$this->connection->getParams()['dbname']} | grep -v \"LOCK TABLE\" | grep -v \"ALTER TABLE\"";

        $content .= shell_exec($command);
        $content .= 'COMMIT;'.PHP_EOL;
        $content .= 'SET unique_checks=1;'.PHP_EOL;
        $content .= 'SET FOREIGN_KEY_CHECKS=1;'.PHP_EOL;

        file_put_contents($file, $content);
    }

    private function generatePostgresqlResetSql(string $file): void
    {
        $prefix        = $this->getTablePrefix();
        $schemaManager = $this->connection->createSchemaManager();
        $tables        = $schemaManager->listTableNames();

        $prefixedTables = array_filter($tables, fn (string $table): bool => str_starts_with($table, $prefix));

        if (empty($prefixedTables)) {
            // Nothing to do
            file_put_contents($file, '-- No tables to truncate');

            return;
        }

        // Quote identifiers properly for PostgreSQL
        $quotedTables = array_map($this->connection->quoteIdentifier(...), $prefixedTables);

        $content = "-- PostgreSQL reset script for prefixed tables\n";
        $content .= self::TRUNCATE_TABLE_SQL.' '.implode(', ', $quotedTables)." RESTART IDENTITY CASCADE;\n";

        file_put_contents($file, $content);
    }

    /**
     * @throws \Exception
     */
    private function dumpToFile(string $sqlDumpFile): void
    {
        if (!$this->isMysqlPlatform()) {
            // Skip full dump for PostgreSQL (not needed with TRUNCATE-based reset)
            return;
        }

        $connectionParams = $this->connection->getParams();
        $password         = $connectionParams['password'] ? '-p'.escapeshellarg($connectionParams['password']) : '';
        $command          = sprintf(
            'mysqldump --set-gtid-purged=OFF --opt -h%s -P%s -u%s %s %s > %s',
            escapeshellarg($connectionParams['host']),
            escapeshellarg((string) $connectionParams['port']),
            escapeshellarg($connectionParams['user']),
            $password,
            escapeshellarg($connectionParams['dbname']),
            escapeshellarg($sqlDumpFile)
        );

        $process = Process::fromShellCommandline($command);
        $process->run();

        // executes after the command finishes
        if (!$process->isSuccessful()) {
            if (file_exists($sqlDumpFile)) {
                unlink($sqlDumpFile);
            }
            throw new \Exception($command.' failed with status code '.$process->getExitCode().' and last line of "'.$process->getErrorOutput().'"');
        }
    }

    /**
     * Restores the shell verbosity that might be set by Symfony console globally.
     *
     * @see \Symfony\Component\Console\Application::configureIO()
     */
    private function restoreShellVerbosity(): void
    {
        $defaultVerbosity = 0;
        putenv('SHELL_VERBOSITY='.$defaultVerbosity);
        $_ENV['SHELL_VERBOSITY']    = $defaultVerbosity;
        $_SERVER['SHELL_VERBOSITY'] = $defaultVerbosity;
    }

    private function getSqlFilePath(string $name): string
    {
        return sprintf('%s/%s-%s.sql', static::getContainer()->getParameter('kernel.cache_dir'), $name, $this->connection->getParams()['dbname']);
    }

    private function resetCustomFields(): bool
    {
        try {
            $prefix = $this->getTablePrefix();
            $result = $this->connection->fetchAllAssociative(sprintf('SELECT alias, object, is_unique_identifer, is_index FROM %slead_fields WHERE date_added IS NOT NULL', $prefix));
            foreach ($result as $data) {
                $table = 'company' === $data['object'] ? 'companies' : 'leads';

                // Drop column from main table
                try {
                    $this->connection->executeStatement(sprintf('ALTER TABLE %s%s DROP COLUMN %s', $prefix, $table, $data['alias']));
                } catch (DBALException) {
                    // Ignore if table doesn't exist
                }
                // Drop dynamic search/unique index table if the field required it
                // Mautic creates {prefix}{alias}_search when is_unique_identifer = true or is_index = true
                if ($this->isPostgresqlPlatform() && ($data['is_unique_identifer'] || $data['is_index'])) {
                    $indexName = $prefix.$data['alias'].'_search';

                    $this->connection->executeStatement(sprintf(
                        'DROP INDEX IF EXISTS %s CASCADE',
                        $indexName
                    ));

                    $this->connection->executeStatement(
                        sprintf('DROP TABLE IF EXISTS %s CASCADE',
                            $indexName
                        ));
                }
            }
        } catch (DBALException) {
            // SQLSTATE[25P02]: In failed sql transaction: 7 ERROR:
            // current transaction is aborted, commands ignored until end of transaction block
            $result = true; // on any error we force database clean
        }

        return (bool) $result;
    }

    private function backupLocalConfig(): void
    {
        $path = $this->getLocalConfigFile();

        if (!file_exists($path)) {
            file_put_contents($path, '<?php $parameters = [];');
        }

        if (!copy($path, $path.'.backup')) {
            throw new \RuntimeException(sprintf('Unable to copy file %s => %s', $path, $path.'.backup'));
        }
    }

    private function restoreLocalConfig(): void
    {
        $path = $this->getLocalConfigFile();

        if (!file_exists($path.'.backup')) {
            return;
        }

        if (!rename($path.'.backup', $path)) {
            throw new \RuntimeException(sprintf('Unable to move file %s => %s', $path.'.backup', $path));
        }
    }

    private function getLocalConfigFile(): string
    {
        /** @var \AppKernel $kernel */
        $kernel = static::$kernel;

        return $kernel->getLocalConfigFile();
    }

    private function insertRollbackCheckData(): void
    {
        try {
            $fullTable = $this->getTablePrefix().'ip_addresses';
            if ($this->isPostgresqlPlatform()) {
                $sequence = DatabasePlatform::getSerialSequence($this->connection, $fullTable);
                $this->connection->executeStatement("INSERT INTO $fullTable (id, ip_address) VALUES (nextval('$sequence'), '0.0.0.0')");
            } else {
                $this->connection->executeStatement("INSERT INTO $fullTable (ip_address) VALUES ('0.0.0.0')");
            }
        } catch (DBALException) {
            // SQLSTATE[25P02]: In failed sql transaction: 7 ERROR:
            // current transaction is aborted, commands ignored until end of transaction block
        }
    }

    private function wasRollbackSuccessful(): bool
    {
        return false === $this->connection->fetchOne("SELECT 1 FROM {$this->getTablePrefix()}ip_addresses LIMIT 1");
    }

    private function getTablePrefix(): string
    {
        return (string) static::getContainer()->getParameter('mautic.db_table_prefix');
    }

    private function isDatabasePrepared(): bool
    {
        return file_exists($this->getSqlFilePath('prepared'));
    }

    private function markDatabasePrepared(): void
    {
        touch($this->getSqlFilePath('prepared'));
    }

    private function clearCache(): void
    {
        $cacheProvider = static::getContainer()->get(CacheProvider::class);
        $this->assertInstanceOf(CacheItemPoolInterface::class, $cacheProvider);
        $cacheProvider->clear();
    }

    /**
     * Helper method to ensure booleans are strings in HTTP payloads.
     *
     * this ensures the payload is compatible with a change in Symfony 5.2
     *
     * @see https://github.com/symfony/browser-kit/commit/1d033e7dccc9978dd7a2bde778d06ebbbf196392
     */
    protected function generateTypeSafePayload(mixed $payload): mixed
    {
        array_walk_recursive($payload, function (&$value): void {
            $value = is_bool($value) ? ($value ? '1' : '0') : $value;
        });

        return $payload;
    }

    /**
     * Platform-safe create (unique) test index.
     *
     * @param array<string> $columns
     */
    protected function createTestIndex(
        string $tableName,
        string $indexName,
        array $columns,
        bool $unique = false,
        bool $withAlter = false,
        bool $ifNotExists = false,
    ): int|string {
        return $this->connection->executeStatement(
            DatabasePlatform::getCreateIndexSql(
                $this->connection->getDatabasePlatform(),
                $tableName,
                $indexName,
                $columns,
                $unique,
                $withAlter,
                $ifNotExists
            )
        );
    }

    /**
     * Platform-safe drop test index.
     */
    protected function dropTestIndex(
        string $tableName,
        string $indexName,
        bool $withAlter = false,
        bool $ifExists = false,
    ): int|string {
        return $this->connection->executeStatement(
            DatabasePlatform::getDropIndexSql(
                $this->connection->getDatabasePlatform(),
                $tableName,
                $indexName,
                $withAlter,
                $ifExists
            )
        );
    }
}
