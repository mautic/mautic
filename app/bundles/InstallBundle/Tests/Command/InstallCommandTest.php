<?php

namespace Mautic\InstallBundle\Tests\Command;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Exception;
use Mautic\CoreBundle\Doctrine\Connection\ConnectionWrapper;
use Mautic\InstallBundle\Command\InstallCommand;
use Mautic\InstallBundle\Install\InstallService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\DependencyInjection\Container;

class InstallCommandTest extends TestCase
{
    private $container;
    private $installer;
    private InstallCommand $command;

    protected function setUp(): void
    {
        parent::setUp();
        $this->container = $this->createMock(Container::class);
        $this->installer = $this->createMock(InstallService::class);
        $application     = $this->createMock(Application::class);
        $inputDefinition = $this->createMock(InputDefinition::class);
        $command         = $this->createMock(Command::class);

        $inputDefinition->method('getOptions')->willReturn([]);

        $application->method('getHelperSet')->willReturn($this->createMock(HelperSet::class));
        $application->method('getDefinition')->willReturn($inputDefinition);
        $application->method('find')->willReturn($command);

        $this->command = new InstallCommand();
        $this->command->setContainer($this->container);
        $this->command->setApplication($application);
    }

    /**
     * @throws Exception
     */
    public function testCommandWhenSiteInstalled(): void
    {
        $this->installer->method('checkIfInstalled')->willReturnOnConsecutiveCalls(true);
        $this->container->method('get')->with('mautic.install.service')->willReturn($this->installer);

        $input  = new ArrayInput(['site_url' => 'localhost']);
        $output = new BufferedOutput();
        $this->command->run($input, $output);

        $this->assertSame('Mautic already installed'.PHP_EOL, $output->fetch());
    }

    /**
     * @throws Exception
     */
    public function testCommandWhenSiteNotInstalled(): void
    {
        $this->installer->method('checkIfInstalled')->willReturnOnConsecutiveCalls(false);

        $registryMock = $this->createMock(Registry::class);
        $registryMock->method('getConnection')->willReturn($this->createMock(ConnectionWrapper::class));

        $this->container->method('get')
            ->withConsecutive(['mautic.install.service'], ['doctrine'])
            ->willReturnOnConsecutiveCalls($this->installer, $registryMock);

        $input = new ArrayInput(
            [
                'site_url'          => 'localhost',
                '--admin_firstname' => 'Admin',
                '--admin_lastname'  => 'Mautic',
                '--admin_username'  => 'admin',
                '--admin_email'     => 'admin@example.com',
                '--admin_password'  => 'password',
            ]
        );
        $output = new BufferedOutput();
        $this->command->run($input, $output);

        $this->assertStringContainsString('Install complete'.PHP_EOL, $output->fetch());
    }
}
