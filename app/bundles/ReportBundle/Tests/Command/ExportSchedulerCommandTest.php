<?php

declare(strict_types=1);

namespace Mautic\ReportBundle\Tests\Command;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\Attributes\Group;

#[Group('database')]
final class ExportSchedulerCommandTest extends MauticMysqlTestCase
{
    public function testCommand(): void
    {
        $commandTester = $this->testSymfonyCommand('mautic:reports:scheduler');

        $this->assertSame(0, $commandTester->getStatusCode());
        $this->assertSame("Scheduler has finished\n", $commandTester->getDisplay());
    }
}
