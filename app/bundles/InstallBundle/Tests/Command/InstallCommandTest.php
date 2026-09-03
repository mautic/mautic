<?php

declare(strict_types=1);

namespace Mautic\InstallBundle\Tests\Command;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Mautic\CoreBundle\Configurator\Step\StepInterface;
use Mautic\CoreBundle\Doctrine\Connection\ConnectionWrapper;
use Mautic\InstallBundle\Command\InstallCommand;
use Mautic\InstallBundle\Install\InstallService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Output\BufferedOutput;

#[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
final class InstallCommandTest extends TestCase
{
    /**
     * @var MockObject&InstallService
     */
    private MockObject $installer;

    /**
     * @var MockObject&Registry
     */
    private MockObject $doctrineRegistry;

    private InstallCommand $command;

    protected function setUp(): void
    {
        parent::setUp();
        $this->installer        = $this->createMock(InstallService::class);
        $this->doctrineRegistry = $this->createMock(Registry::class);
        $application            = $this->createMock(Application::class);
        $inputDefinition        = $this->createMock(InputDefinition::class);
        $command                = $this->createMock(Command::class);

        $inputDefinition->method('getOptions')->willReturn([]);
        $inputDefinition->method('getArguments')->willReturn([]);

        $application->method('getHelperSet')->willReturn($this->createStub(HelperSet::class));
        $application->method('getDefinition')->willReturn($inputDefinition);
        $application->method('find')->willReturn($command);

        $this->command = new InstallCommand($this->installer, $this->doctrineRegistry);
        $this->command->setApplication($application);
    }

    public function testCommandWhenSiteInstalled(): void
    {
        $this->installer->method('checkIfInstalled')->willReturn(true);

        $input  = new ArrayInput(['site_url' => 'localhost']);
        $output = new BufferedOutput();
        $this->command->run($input, $output);

        $this->assertSame('Mautic already installed'.PHP_EOL, $output->fetch());
    }

    public function testCommandWhenSiteNotInstalled(): void
    {
        $this->installer->method('checkIfInstalled')->willReturn(false);

        $this->doctrineRegistry->method('getConnection')->willReturn($this->createStub(ConnectionWrapper::class));

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

    public function testCommandStripsHtmlFromOptionalInstallerMessages(): void
    {
        $this->installer->method('checkIfInstalled')->willReturn(false);
        $this->installer->method('getStep')->willReturn($this->createStub(StepInterface::class));
        $this->installer->method('checkRequirements')->willReturn([]);
        $this->installer->method('checkOptionalSettings')->willReturn([
            'The <strong>memory_limit</strong> setting in your PHP configuration is lower than the suggested minimum limit of 512M. Mautic can have performance issues with large datasets without sufficient memory.',
        ]);
        $this->doctrineRegistry->method('getConnection')->willReturn($this->createStub(ConnectionWrapper::class));

        $input = new ArrayInput(
            [
                'site_url'          => 'localhost',
                '--force'           => true,
                '--admin_firstname' => 'Admin',
                '--admin_lastname'  => 'Mautic',
                '--admin_username'  => 'admin',
                '--admin_email'     => 'admin@example.com',
                '--admin_password'  => 'password',
            ]
        );
        $output = new BufferedOutput();
        $this->command->run($input, $output);
        $display = $output->fetch();

        $this->assertStringContainsString('Missing optional settings:', $display);
        $this->assertStringContainsString('memory_limit', $display);
        $this->assertStringContainsString('512M', $display);
        $this->assertStringNotContainsString('<strong>', $display);
        $this->assertStringNotContainsString('</strong>', $display);
        $this->assertStringNotContainsString('%min_memory_limit%', $display);
    }
}
