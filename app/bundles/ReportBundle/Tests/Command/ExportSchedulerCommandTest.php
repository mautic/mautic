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
}
