<?php

namespace Mautic\CoreBundle\Test;

use Doctrine\DBAL\Exception as DBALException;
use Mautic\InstallBundle\InstallFixtures\ORM\LeadFieldData;
use Mautic\InstallBundle\InstallFixtures\ORM\RoleData;
use Mautic\UserBundle\DataFixtures\ORM\LoadRoleData;
use Mautic\UserBundle\DataFixtures\ORM\LoadUserData;
use Mautic\UserBundle\Entity\User;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Process\Process;

abstract class MauticMysqlTestCase extends AbstractMauticTestCase
{
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

    /**
     * @param array<mixed> $data
     */
    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->configParams += [
            'db_driver' => 'pdo_mysql',
        ];
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
        $prefix     = $this->getTablePrefix();
        $connection = $this->connection;

        foreach ($tables as $table) {
            $connection->executeStatement(sprintf('ALTER TABLE `%s%s` AUTO_INCREMENT=1', $prefix, $table));
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
        $prefix = MAUTIC_TABLE_PREFIX;

        foreach ($tables as $table) {
            $this->connection->executeStatement("SET FOREIGN_KEY_CHECKS = 0; TRUNCATE TABLE `{$prefix}{$table}`; SET FOREIGN_KEY_CHECKS = 1;");
        }
    }

    /**
     * @throws \Exception
     */
    private function applySqlFromFile($file): void
    {
        $connectionParams = $this->connection->getParams();
        $password         = $connectionParams['password'] ? '-p'.escapeshellarg($connectionParams['password']) : '';
        $command          = sprintf(
            'mysql -h%s -P%s -u%s %s %s < %s',
            escapeshellarg($connectionParams['host']),
            escapeshellarg((string) $connectionParams['port']),
            escapeshellarg($connectionParams['user']),
            $password,
            escapeshellarg($connectionParams['dbname']),
            escapeshellarg($file)
        );

        $process = Process::fromShellCommandline($command);
        $process->run();

        // executes after the command finishes
        if (!$process->isSuccessful()) {
            throw new \Exception($command.' failed with status code '.$process->getExitCode().' and last line of "'.$process->getErrorOutput().'"');
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
            $this->dumpToFile($sqlDumpFile);
            $this->generateResetDatabaseSql($this->getSqlFilePath('reset_db'));

            return;
        }

        $this->applySqlFromFile($sqlDumpFile);
    }

    private function resetDatabase(): void
    {
        $this->applySqlFromFile($this->getSqlFilePath('reset_db'));
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
        $this->testSymfonyCommand('doctrine:database:drop', ['--if-exists' => true, '--force' => true]);
        $this->testSymfonyCommand('doctrine:database:create');
        $this->testSymfonyCommand('doctrine:schema:create');
        $this->testSymfonyCommand('doctrine:migration:sync-metadata-storage');
    }

    private function generateResetDatabaseSql(string $file): void
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
        $command  = "mysqldump --skip-triggers --compact --no-create-info --skip-opt --single-transaction --opt -h{$this->connection->getParams()['host']} -P{$this->connection->getParams()['port']} -u{$this->connection->getParams()['user']}$password {$this->connection->getParams()['dbname']} | grep -v \"LOCK TABLE\" | grep -v \"ALTER TABLE\"";

        $content .= shell_exec($command);
        $content .= 'COMMIT;'.PHP_EOL;
        $content .= 'SET unique_checks=1;'.PHP_EOL;
        $content .= 'SET FOREIGN_KEY_CHECKS=1;'.PHP_EOL;

        file_put_contents($file, $content);
    }

    /**
     * @throws \Exception
     */
    private function dumpToFile(string $sqlDumpFile): void
    {
        $connectionParams = $this->connection->getParams();
        $password         = $connectionParams['password'] ? '-p'.escapeshellarg($connectionParams['password']) : '';
        $command          = sprintf(
            'mysqldump --opt -h%s -P%s -u%s %s %s > %s',
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
        $prefix = $this->getTablePrefix();
        $result = $this->connection->fetchAllAssociative(sprintf('SELECT alias, object FROM %slead_fields WHERE date_added IS NOT NULL', $prefix));

        foreach ($result as $data) {
            $table = 'company' === $data['object'] ? 'companies' : 'leads';
            try {
                $this->connection->executeStatement(sprintf('ALTER TABLE %s%s DROP COLUMN %s', $prefix, $table, $data['alias']));
            } catch (\Exception) {
            }
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
        $this->connection->executeStatement("INSERT INTO {$this->getTablePrefix()}ip_addresses (ip_address) VALUES ('127.0.0.1')");
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
        $cacheProvider = static::getContainer()->get('mautic.cache.provider');
        \assert($cacheProvider instanceof CacheItemPoolInterface);
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
}
