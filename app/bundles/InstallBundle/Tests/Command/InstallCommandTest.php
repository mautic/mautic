<?php

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\InstallBundle\Tests\Command;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\InstallBundle\Command\InstallCommand;
use Mautic\InstallBundle\Install\InstallService;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class InstallCommandTest extends \PHPUnit\Framework\TestCase
{
    private $coreParametersHelper;
    private $dispatcher;
    private $container;
    private $transport;
    private $application;
    private $installer;

    private $command;

    protected function setUp()
    {
        parent::setUp();

        $this->dispatcher           = $this->createMock(EventDispatcherInterface::class);
        $this->coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $this->container            = $this->createMock(Container::class);
        $this->transport            = $this->createMock(\Swift_Transport::class);
        $this->application          = $this->createMock(Application::class);
        $this->installer            = $this->createMock(InstallService::class);

        $this->application->method('getHelperSet')
            ->willReturn($this->createMock(HelperSet::class));

        $inputDefinition = $this->createMock(InputDefinition::class);

        $this->application->method('getDefinition')
            ->willReturn($inputDefinition);

        $inputDefinition->method('getOptions')
            ->willReturn([]);

        $this->command = new InstallCommand();
        $this->command->setContainer($this->container);
        $this->command->setApplication($this->application);

        $this->container->method('get')
            ->withConsecutive(
                ['mautic.install.service']
            )->willReturnOnConsecutiveCalls(
                $this->installer
            );
    }

    public function testCommandWhenSiteInstalled()
    {
        $this->installer->method('checkIfInstalled')
            ->willReturnOnConsecutiveCalls(true);

        $input  = new ArrayInput([
            'site_url'          => 'localhost',
        ]);
        $output = new BufferedOutput();
        $this->command->run($input, $output);

        $this->assertSame('Mautic already installed'.PHP_EOL, $output->fetch());
    }

    public function testCommandWhenSiteNotInstalled()
    {
        $this->installer->method('checkIfInstalled')
            ->willReturnOnConsecutiveCalls(false);

        $input  = new ArrayInput([
            'site_url'          => 'localhost',
            '--admin_firstname' => 'Admin',
            '--admin_lastname'  => 'Mautic',
            '--admin_username'  => 'admin',
            '--admin_email'     => 'admin@example.com',
            '--admin_password'  => 'password',
        ]);
        $output = new BufferedOutput();
        $this->command->run($input, $output);

        $this->assertContains('Install complete'.PHP_EOL, $output->fetch());
    }
}
