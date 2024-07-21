<?php

namespace Helper;

use Codeception\Module;
use Symfony\Component\Process\Process;

class DbHelper extends Module
{
    private bool $databasePrepared = false;

    public function _beforeSuite($settings = [])
    {
        $this->prepareDatabase();
        $this->generateSqlDump();

        if (!file_exists('tests/_data/dump.sql')) {
            $this->fail('Failed to generate dump.sql');
        } else {
            $this->debug('DbHelper: dump.sql successfully generated');
        }
    }

    private function prepareDatabase(): void
    {
        if ($this->databasePrepared) {
            return;
        }

        $this->createDatabase();
        $this->applyMigrations();
        $this->installFixtures();
        $this->databasePrepared = true;
    }

    private function createDatabase(): void
    {
        $this->runCommand('bin/console doctrine:database:drop --if-exists --force', 'Dropping database');
        $this->runCommand('bin/console doctrine:database:create', 'Creating database');
        $this->runCommand('bin/console doctrine:schema:create', 'Creating database schema');
    }

    private function applyMigrations(): void
    {
        $this->runCommand('bin/console doctrine:migrations:migrate --no-interaction', 'Applying migrations');
    }

    private function installFixtures(): void
    {
        $this->runCommand('bin/console doctrine:fixtures:load --no-interaction', 'Loading fixtures');
    }

    private function generateSqlDump(): void
    {
        $dsn      = 'mysql:host=db;dbname=db';
        $user     = 'db';
        $password = 'db';
        $command  = sprintf(
            'mysqldump --opt -h%s -u%s -p%s %s > tests/_data/dump.sql',
            parse_url($dsn, PHP_URL_HOST),
            $user,
            $password,
            parse_url($dsn, PHP_URL_PATH)
        );

        $this->runCommand($command, 'Generating SQL dump');
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
