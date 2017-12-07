<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ReportBundle\Tests\EventListener;

use Mautic\ReportBundle\Entity\Report;
use Mautic\ReportBundle\Entity\Scheduler;
use Mautic\ReportBundle\Event\ReportScheduleSendEvent;
use Mautic\ReportBundle\EventListener\SchedulerSubscriber;
use Mautic\ReportBundle\Scheduler\Model\SendSchedule;

class SchedulerSubscriberTest extends \PHPUnit_Framework_TestCase
{
    public function testNoEmailsProvided()
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
