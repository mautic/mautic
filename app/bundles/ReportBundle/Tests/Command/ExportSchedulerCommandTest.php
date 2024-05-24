<?php

declare(strict_types=1);

namespace Mautic\ReportBundle\Tests\Command;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\ReportBundle\Scheduler\Command\ExportSchedulerCommand;
use PHPUnit\Framework\Assert;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Process\Process;

class ExportSchedulerCommandTest extends MauticMysqlTestCase
{
    public function testCommand(): void
    {
        $commandTester = $this->testSymfonyCommand(ExportSchedulerCommand::NAME);

        Assert::assertSame(0, $commandTester->getStatusCode());
        Assert::assertSame("Scheduler has finished\n", $commandTester->getDisplay());
    }

    public function testSchedulerCommandThrowErrorIfAlreadyRunning()
    {
        $kernel      = self::bootKernel();
        $application = new \Symfony\Bundle\FrameworkBundle\Console\Application($kernel);

        $command = $application->find(ExportSchedulerCommand::NAME);

        // Start the first command in a process
        $process1 = new Process(['php', 'bin/console', ExportSchedulerCommand::NAME]);
        $process1->start();

        usleep(500);

        // Start the second command in another process
        $process2 = new Process(['php', 'bin/console', ExportSchedulerCommand::NAME]);
        $process2->start();

        $process1->wait();
        $process2->wait();

        $this->assertSame(Command::SUCCESS, $process1->getExitCode());
        $this->assertNotSame(Command::SUCCESS, $process2->getExitCode());

        $this->assertStringContainsString('Scheduler has finished', $process1->getOutput());
        $this->assertStringContainsString('Script in progress', $process2->getOutput());
    }
}
