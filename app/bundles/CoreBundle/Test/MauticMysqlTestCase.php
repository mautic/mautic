<?php

namespace Mautic\CoreBundle\Test;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

abstract class MauticMysqlTestCase extends AbstractMauticTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->createDatabase();
        $this->applyMigrations();
        $this->installDatabaseFixtures();
    }

    private function createDatabase()
    {
        $this->runCommand('doctrine:database:drop', [
            '--env'   => 'test',
            '--force' => true,
        ]);

        $this->runCommand('doctrine:database:create', [
            '--env' => 'test',
        ]);

        $this->runCommand('doctrine:schema:create', [
            '--env' => 'test',
        ]);
    }

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
}
