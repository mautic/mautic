<?php

namespace Mautic\ReportBundle\Tests\EventListener;

use Mautic\ReportBundle\Entity\Report;
use Mautic\ReportBundle\Entity\Scheduler;
use Mautic\ReportBundle\Event\ReportScheduleSendEvent;
use Mautic\ReportBundle\EventListener\SchedulerSubscriber;
use Mautic\ReportBundle\Scheduler\Model\SendSchedule;

class SchedulerSubscriberTest extends \PHPUnit\Framework\TestCase
{
    public function testNoEmailsProvided(): void
    {
        $sendScheduleMock = $this->getMockBuilder(SendSchedule::class)
            ->disableOriginalConstructor()
            ->getMock();

        $schedulerSubscriber = new SchedulerSubscriber($sendScheduleMock);

        $report                  = new Report();
        $date                    = new \DateTime();
        $scheduler               = new Scheduler($report, $date);
        $file                    = 'path-to-a-file';
        $reportScheduleSendEvent = new ReportScheduleSendEvent($scheduler, $file);

        $sendScheduleMock->expects($this->once())
            ->method('send')
            ->with($scheduler, $file);

        $schedulerSubscriber->onScheduleSend($reportScheduleSendEvent);
    }
}
