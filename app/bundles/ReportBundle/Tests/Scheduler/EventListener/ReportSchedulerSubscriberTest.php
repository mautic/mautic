<?php

namespace Mautic\ReportBundle\Tests\Scheduler\EventListener;

use Mautic\ReportBundle\Entity\Report;
use Mautic\ReportBundle\Event\ReportEvent;
use Mautic\ReportBundle\Scheduler\EventListener\ReportSchedulerSubscriber;
use Mautic\ReportBundle\Scheduler\Model\SchedulerPlanner;

class ReportSchedulerSubscriberTest extends \PHPUnit_Framework_TestCase
{
    public function testOnReportSave()
    {
        $report = new Report();
        $event  = new ReportEvent($report);

        $schedulerPlanner = $this->getMockBuilder(SchedulerPlanner::class)
            ->disableOriginalConstructor()
            ->getMock();

        $schedulerPlanner->expects($this->once())
            ->method('computeScheduler')
            ->with($report);

        $reportSchedulerSubscriber = new ReportSchedulerSubscriber($schedulerPlanner);
        $reportSchedulerSubscriber->onReportSave($event);
    }
}
