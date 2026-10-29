<?php

declare(strict_types=1);

namespace Mautic\ReportBundle\Tests\Scheduler\EventListener;

use PHPUnit\Framework\TestCase;
use Mautic\ReportBundle\Entity\Report;
use Mautic\ReportBundle\Event\ReportEvent;
use Mautic\ReportBundle\Scheduler\EventListener\ReportSchedulerSubscriber;
use Mautic\ReportBundle\Scheduler\Model\SchedulerPlanner;

final class ReportSchedulerSubscriberTest extends TestCase
{
    public function testOnReportSave(): void
    {
        $report = new Report();
        $event  = new ReportEvent($report);

        $schedulerPlanner = $this->createMock(SchedulerPlanner::class);

        $schedulerPlanner->expects($this->once())
            ->method('computeScheduler')
            ->with($report);

        $reportSchedulerSubscriber = new ReportSchedulerSubscriber($schedulerPlanner);
        $reportSchedulerSubscriber->onReportSave($event);
    }
}
