<?php

declare(strict_types=1);

namespace Mautic\ReportBundle\Tests\Command;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\ReportBundle\Model\ReportCleanup;
use Mautic\ReportBundle\Model\ReportExporter;
use Mautic\ReportBundle\Scheduler\Command\ExportSchedulerCommand;
use PHPUnit\Framework\Assert;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Process\Process;
use Symfony\Contracts\Translation\TranslatorInterface;

class ExportSchedulerCommandTest extends MauticMysqlTestCase
{
    public function testCommand(): void
    {
        $commandTester = $this->testSymfonyCommand(ExportSchedulerCommand::NAME);

        Assert::assertSame(0, $commandTester->getStatusCode());
        Assert::assertSame("Scheduler has finished\n", $commandTester->getDisplay());
    }

    public function testSchedulerCommandThrowErrorIfAlreadyRunning(): void
    {
        $kernel      = self::bootKernel();
        $application = new Application($kernel);

        $reportExporterMock       = $this->createMock(ReportExporter::class);
        $reportCleanupMock        = $this->createMock(ReportCleanup::class);
        $translatorMock           = $this->createMock(TranslatorInterface::class);
        $pathsHelperMock          = $this->createMock(PathsHelper::class);
        $coreParametersHelperMock = $this->createMock(CoreParametersHelper::class);

        // Use the mock dependencies in the command constructor
        $command = new MockExportSchedulerCommand(
            $reportExporterMock,
            $reportCleanupMock,
            $translatorMock,
            $pathsHelperMock,
            $coreParametersHelperMock
        );
        $application->find(MockExportSchedulerCommand::NAME);

        $process1 = new Process(['php', 'bin/console', MockExportSchedulerCommand::NAME]);
        $process1->start();

        $process2 = new Process(['php', 'bin/console', MockExportSchedulerCommand::NAME]);
        $process2->start();

        $process1->wait();
        $process2->wait();

        $this->assertSame(Command::SUCCESS, $process1->getExitCode());
        $this->assertSame(Command::SUCCESS, $process2->getExitCode());

        $this->assertStringContainsString('Scheduler has finished', $process1->getOutput());
        $this->assertStringContainsString('Script in progress', $process2->getOutput());
    }
}
