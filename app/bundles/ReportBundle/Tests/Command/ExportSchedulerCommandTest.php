<?php

declare(strict_types=1);

namespace Mautic\ReportBundle\Tests\Command;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\Assert;

class ExportSchedulerCommandTest extends MauticMysqlTestCase
{
    public function testCommand(): void
    {
        $commandTester = $this->testSymfonyCommand('mautic:reports:scheduler');

        Assert::assertSame(0, $commandTester->getStatusCode());
        Assert::assertSame("Scheduler has finished\n", $commandTester->getDisplay());
    }

    public function testSchedulerCommandThrowErrorIfAlreadyRunning(): void
    {
        // Run the first instance of the command
        $commandTester1 = $this->testSymfonyCommand('mautic:reports:scheduler');

        // Run the second instance of the command
        $commandTester2 = $this->testSymfonyCommand('mautic:reports:scheduler');

        // Assertions
        $this->assertSame(0, $commandTester1->getStatusCode());
        $this->assertSame(1, $commandTester2->getStatusCode());
        $this->assertSame("Scheduler has finished\n", $commandTester1->getDisplay());
        $this->assertSame("Script in progress\n", $commandTester2->getDisplay());
    }
}
