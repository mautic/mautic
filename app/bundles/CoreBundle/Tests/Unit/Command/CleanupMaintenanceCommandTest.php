<?php

namespace Mautic\CoreBundle\Tests\Unit\Command;

use Mautic\CoreBundle\Command\CleanupMaintenanceCommand;
use Mautic\CoreBundle\Event\MaintenanceEvent;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Contracts\Translation\TranslatorInterface;

class CleanupMaintenanceCommandTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var AuditLogModel|mixed|\PHPUnit\Framework\MockObject\MockObject
     */
    private $auditLogModel;

    /**
     * @var IpLookupHelper|mixed|\PHPUnit\Framework\MockObject\MockObject
     */
    private $ipLookupHelper;

    /**
     * @var mixed|\PHPUnit\Framework\MockObject\MockObject|TranslatorInterface
     */
    private $translator;

    /**
     * @var mixed|\PHPUnit\Framework\MockObject\MockObject|EventDispatcher
     */
    private $eventDispatcher;

    /**
     * @var CoreParametersHelper|mixed|\PHPUnit\Framework\MockObject\MockObject
     */
    private $coreParametersHelper;

    public function getCommandTester(): CommandTester
    {
        $this->eventDispatcher->method('dispatch')->willReturnCallback(function () {
            $maintenanceEvent = $this->createMock(MaintenanceEvent::class);
            $maintenanceEvent->expects($this->once())->method('getStats')->willReturn(
                [
                    'Visitors'          => 10,
                    'Visitor page hits' => 99,
                ]
            );

            return $maintenanceEvent;
        });

        $command = new CleanupMaintenanceCommand(
            $this->auditLogModel,
            $this->ipLookupHelper,
            $this->translator,
            $this->eventDispatcher,
            $this->coreParametersHelper
        );

        $application = new Application();

        $application->add($command);

        // env is global option. Must be defined.
        $application->getDefinition()->addOption(
            new InputOption(
                '--no-interaction',
                '-n',
                InputOption::VALUE_NONE,
                'The environment to operate in.'
            )
        );

        return new CommandTester(
            $application->find(CleanupMaintenanceCommand::NAME)
        );
    }

    protected function setUp(): void
    {
        parent::setUp();

        if (!defined('MAUTIC_TABLE_PREFIX')) {
            define('MAUTIC_TABLE_PREFIX', '');
        }

        if (!defined('MAUTIC_ENV')) {
            define('MAUTIC_ENV', 'PROD');
        }

        $this->auditLogModel        = $this->createMock(AuditLogModel::class);
        $this->ipLookupHelper       = $this->createMock(IpLookupHelper::class);
        $this->translator           = $this->createTranslatorMock();
        $this->eventDispatcher      = $this->createMock(EventDispatcher::class);
        $this->coreParametersHelper = $this->createMock(CoreParametersHelper::class);
    }

    public function testCommandDryRun()
    {
        $commandTester = $this->getCommandTester();
        $commandTester->execute(['--dry-run' => true]);
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Visitor page hits', $output);
    }

    public function testCommandInteraction()
    {
        $commandTester = $this->getCommandTester();
        $commandTester->execute([]);
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('mautic.maintenance.confirm_data_purge', $output);
    }

    public function testCommandNoInteraction()
    {
        $commandTester = $this->getCommandTester();
        $commandTester->execute(['--no-interaction' => true]);
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Visitor page hits', $output);
    }

    private function createTranslatorMock(): TranslatorInterface
    {
        return new class() implements TranslatorInterface {
            public function trans($id, array $parameters = [], $domain = null, $locale = null)
            {
                return '[trans]'.$id.'[/trans]';
            }

            public function transChoice($id, $number, array $parameters = [], $domain = null, $locale = null)
            {
                return '[trans]'.$id.'[/trans]';
            }

            public function setLocale($locale)
            {
            }

            public function getLocale()
            {
            }
        };
    }
}
