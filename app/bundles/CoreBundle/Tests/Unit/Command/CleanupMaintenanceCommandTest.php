<?php

namespace Mautic\CoreBundle\Tests\Unit\Command;

use Mautic\CoreBundle\Command\CleanupMaintenanceCommand;
use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\MaintenanceEvent;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
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
     * @var PathsHelper|(PathsHelper&object&\PHPUnit\Framework\MockObject\MockObject)|(PathsHelper&\PHPUnit\Framework\MockObject\MockObject)|(object&\PHPUnit\Framework\MockObject\MockObject)|\PHPUnit\Framework\MockObject\MockObject
     */
    private PathsHelper|\PHPUnit\Framework\MockObject\MockObject $pathsHelper;

    /**
     * @var TranslatorInterface
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
        $cacheDir = __DIR__.'/resource/cache/tmp';

        $this->pathsHelper->expects($this->once())
            ->method('getSystemPath')
            ->with('cache')
            ->willReturn($cacheDir);

        $event = new MaintenanceEvent(100, false, false);
        $this->eventDispatcher->method('dispatch')
        ->willReturnCallback(function ($event, $eventName) {
            $this->assertEquals($eventName, CoreEvents::MAINTENANCE_CLEANUP_DATA);
            $this->assertInstanceOf(MaintenanceEvent::class, $event);
            $event->setStat('Visitor page hits', 10);

            return $event;
        });

        $command = new CleanupMaintenanceCommand(
            $this->translator,
            $this->eventDispatcher,
            $this->pathsHelper,
            $this->coreParametersHelper,
            $this->auditLogModel,
            $this->ipLookupHelper
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

        if (!defined('MAUTIC_ENV')) {
            define('MAUTIC_ENV', 'PROD');
        }

        $this->auditLogModel        = $this->createMock(AuditLogModel::class);
        $this->ipLookupHelper       = $this->createMock(IpLookupHelper::class);
        $this->translator           = $this->createTranslatorMock();
        $this->eventDispatcher      = $this->createMock(EventDispatcher::class);
        $this->coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $this->pathsHelper          = $this->createMock(PathsHelper::class);
    }

    public function testCommandDryRun(): void
    {
        $this->auditLogModel->expects($this->never())->method('writeToLog');

        $commandTester = $this->getCommandTester();
        $commandTester->execute(['--dry-run' => true]);
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Visitor page hits', $output);
    }

    public function testCommandInteraction(): void
    {
        $this->auditLogModel->expects($this->never())->method('writeToLog');

        $commandTester = $this->getCommandTester();
        $commandTester->execute(['']);
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('mautic.maintenance.confirm_data_purge', $output);
    }

    public function testCommandNoInteraction(): void
    {
        $this->auditLogModel->expects($this->once())->method('writeToLog');

        $commandTester = $this->getCommandTester();
        $commandTester->execute(['--no-interaction' => true]);
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Visitor page hits', $output);
    }

    private function createTranslatorMock(): TranslatorInterface
    {
        return new class() implements TranslatorInterface {
            /**
             * @param array<int|string> $parameters
             */
            public function trans($id, array $parameters = [], $domain = null, $locale = null)
            {
                return '[trans]'.$id.'[/trans]';
            }

            public function setLocale(?string $locale): void
            {
            }
        };
    }
}
