<?php

namespace Mautic\CoreBundle\Test;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

abstract class MauticMysqlTestCase extends AbstractMauticTestCase
{
    /**
     * @var string
     */
    private $sqlDumpFile = false;

    /**
     * @throws \Exception
     */
    public function setUp()
    {
        parent::setUp();

        $this->sqlDumpFile = $this->container->getParameter('kernel.cache_dir').'/fresh_db.sql';

        $this->prepareDatabase();
    }

    /**
     * @param       $name
     * @param array $params
     *
     * @throws \Exception
     */
    protected function runCommand($name, array $params = [])
    {
        array_unshift($params, $name);

        $kernel      = $this->container->get('kernel');
        $application = new Application($kernel);
        $application->setAutoExit(false);

        $input = new ArrayInput($params);

        $output = new BufferedOutput();
        $application->run($input, $output);
    }

    /**
     * Reset each test using a SQL file if possible to prevent from having to run the fixtures over and over.
     *
     * @throws \Exception
     */
    private function prepareDatabase()
    {
        if (!function_exists('system')) {
            $this->installDatabase();

            return;
        }

        if (!file_exists($this->sqlDumpFile)) {
            $this->installDatabase();
            $this->dumpToFile();

            return;
        }

        $this->restoreFromFile();
    }

    /**
     * @throws \Exception
     */
    private function installDatabase()
    {
        $this->createDatabase();
        $this->applyMigrations();
        $this->installDatabaseFixtures();
    }

    /**
     * @throws \Exception
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

    private function dumpToFile()
    {
        $connection = $this->container->get('doctrine.dbal.default_connection');
        $command    = "mysqldump --add-drop-table --opt -h{$connection->getHost()} -P{$connection->getPort()} -u{$connection->getUsername()} -p{$connection->getPassword()} {$connection->getDatabase()} > {$this->sqlDumpFile}";
        system($command);
    }

    private function restoreFromFile()
    {
        $connection = $this->container->get('doctrine.dbal.default_connection');
        $command    = "export MYSQL_PWD={$connection->getPassword()}; mysql -h{$connection->getHost()} -P{$connection->getPort()} -u{$connection->getUsername()} {$connection->getDatabase()} < {$this->sqlDumpFile} > /dev/null";
        system($command);
    }
}
