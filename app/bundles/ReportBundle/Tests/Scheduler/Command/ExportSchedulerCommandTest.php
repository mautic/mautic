<?php

declare(strict_types=1);

namespace Mautic\ReportBundle\Tests\Scheduler\Command;

use Mautic\CoreBundle\Helper\ExitCode;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\ReportBundle\Entity\Report;
use Mautic\ReportBundle\Entity\Scheduler;
use Mautic\ReportBundle\Scheduler\Enum\SchedulerEnum;
use PHPUnit\Framework\Assert;

final class ExportSchedulerCommandTest extends MauticMysqlTestCase
{
    /**
     * Test that scheduler command executes normally without lock contention.
     */
    public function testCommandExecutesNormally(): void
    {
        $report = $this->createScheduledReport('Test Report');

        // Create a scheduler entry with past date to ensure it processes
        $scheduler = new Scheduler($report, new \DateTime('-1 minute'));
        $this->em->persist($scheduler);
        $this->em->flush();

        $schedulersBeforeCommand = $this->em->getRepository(Scheduler::class)->findBy(['report' => $report]);
        Assert::assertCount(1, $schedulersBeforeCommand, 'Scheduler should exist before command execution');

        // Execute command normally
        $commandTester = $this->testSymfonyCommand('mautic:reports:scheduler', ['--report' => $report->getId()]);
        Assert::assertEquals(ExitCode::SUCCESS, $commandTester->getStatusCode());
    }

    public function testCleanupOnlyDoesNotProcessExports(): void
    {
        $report   = $this->createScheduledReport('Cleanup only report');
        $reportId = (int) $report->getId();

        $scheduler = new Scheduler($report, new \DateTime('-1 minute'));
        $this->em->persist($scheduler);
        $this->em->flush();

        Assert::assertCount(
            1,
            $this->em->getRepository(Scheduler::class)->findBy(['report' => $report]),
            'Scheduler should exist before command execution'
        );

        $this->em->clear();
        $commandTester = $this->testSymfonyCommand('mautic:reports:scheduler', ['--report' => $reportId, '--cleanup-only' => true]);

        Assert::assertSame('', trim($commandTester->getDisplay()), 'Cleanup-only mode should not execute export processing output.');

        $this->em->clear();
        $reportReference = $this->em->getReference(Report::class, $reportId);
        Assert::assertCount(
            1,
            $this->em->getRepository(Scheduler::class)->findBy(['report' => $reportReference]),
            'Cleanup-only mode should keep due scheduler entries untouched'
        );
    }

    /**
     * Create a published scheduled report for testing.
     */
    private function createScheduledReport(string $name): Report
    {
        $report = new Report();
        $report->setName($name);
        $report->setDescription('Test report for scheduler command');
        $report->setSource('audit.log');
        $report->setColumns(['al.action', 'al.date_added']);
        $report->setToAddress('recipient@example.com');
        $report->setIsPublished(true);
        $report->setIsScheduled(true);
        $report->setScheduleUnit(SchedulerEnum::UNIT_DAILY);

        $this->em->persist($report);
        $this->em->flush();

        return $report;
    }
}
