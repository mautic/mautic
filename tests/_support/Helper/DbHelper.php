<?php

namespace Helper;

use Codeception\Module;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Process\Process;

class DbHelper extends Module
{
    private bool $databasePrepared = false;
    private array $dbConfig;
    private string $dumpFilePath = 'tests/_data/dump.sql';

    public function _beforeSuite($settings = [])
    {
        $this->loadEnv();
        $this->dbConfig = [
            'host'     => $_ENV['DB_HOST'],
            'user'     => $_ENV['DB_USER'],
            'password' => $_ENV['DB_PASSWD'],
            'dbname'   => $_ENV['DB_NAME'],
        ];

        if (file_exists($this->dumpFilePath)) {
            $this->populateDatabaseFromDump();
        } else {
            $this->prepareDatabase();
            $this->generateSqlDump();
        }

        if (!file_exists($this->dumpFilePath)) {
            $this->fail('Failed to generate dump.sql');
        } else {
            $this->debug('DbHelper: dump.sql successfully generated');
        }
    }

    private function loadEnv(): void
    {
        $dotenv = new Dotenv();
        $dotenv->load(__DIR__.'/../../../.env.test.local');
    }

    private function prepareDatabase(): void
    {
        if ($this->databasePrepared) {
            return;
        }

        $this->createDatabase();
        // $this->applyMigrations();
        $this->installFixtures();
        $this->databasePrepared = true;
    }

    private function createDatabase(): void
    {
        $this->runCommand('bin/console --env=test doctrine:database:drop --if-exists --force', 'Dropping database');
        $this->runCommand('bin/console --env=test doctrine:database:create', 'Creating database');
        $this->runCommand('bin/console --env=test doctrine:schema:create', 'Creating database schema');
    }

    private function applyMigrations(): void
    {
        $this->runCommand('bin/console --env=test doctrine:migrations:migrate --no-interaction', 'Applying migrations');
    }

    private function installFixtures(): void
    {
        $this->runCommand('bin/console --env=test doctrine:fixtures:load --no-interaction', 'Loading fixtures');
    }

    private function generateSqlDump(): void
    {
        $command = sprintf(
            'mysqldump --opt -h%s -u%s -p%s %s > %s',
            $this->dbConfig['host'],
            $this->dbConfig['user'],
            $this->dbConfig['password'],
            $this->dbConfig['dbname'],
            $this->dumpFilePath
        );

        $this->runCommand($command, 'Generating SQL dump');
    }

    private function populateDatabaseFromDump(): void
    {
        $command = sprintf(
            'mysql -h%s -u%s -p%s %s < %s',
            $this->dbConfig['host'],
            $this->dbConfig['user'],
            $this->dbConfig['password'],
            $this->dbConfig['dbname'],
            $this->dumpFilePath
        );

        $this->runCommand($command, 'Populating database from dump.sql');
    }

    private function runCommand($command, $description): void
    {
        $process = Process::fromShellCommandline($command);
        $process->run();

        if (!$process->isSuccessful()) {
            $this->fail("$description failed with error: ".$process->getErrorOutput());
        } else {
            $this->debug("$description completed successfully");
        }
    }
}
