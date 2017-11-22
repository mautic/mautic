<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ReportBundle\Tests\Model;

use Mautic\EmailBundle\Helper\MailHelper;
use Mautic\ReportBundle\Entity\Report;
use Mautic\ReportBundle\Entity\Scheduler;
use Mautic\ReportBundle\Scheduler\Model\MessageSchedule;
use Mautic\ReportBundle\Scheduler\Model\SendSchedule;

class SendScheduleTest extends \PHPUnit_Framework_TestCase
{
    public function testSendScheduleWithFile()
    {
        $report = new Report();
        $report->setToAddress('john@doe.com, doe@john.com');
        $scheduler = new Scheduler($report, new \DateTime());

        $mailHelperMock = $this->getMockBuilder(MailHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mailHelperMock->expects($this->once())
            ->method('getMailer')
            ->with()
            ->willReturn($mailHelperMock);

        $messageSchedule = $this->getMockBuilder(MessageSchedule::class)
            ->disableOriginalConstructor()
            ->getMock();

        $messageSchedule->expects($this->once())
            ->method('getSubject')
            ->with($report)
            ->willReturn('Subject');

        $messageSchedule->expects($this->once())
            ->method('getMessage')
            ->with($report, 'path-to-a-file')
            ->willReturn('Message');

        $messageSchedule->expects($this->once())
            ->method('fileCouldBeSend')
            ->with('path-to-a-file')
            ->willReturn(true);

        $mailHelperMock->expects($this->once())
            ->method('setTo')
            ->with(['john@doe.com', 'doe@john.com']);

        $mailHelperMock->expects($this->once())
            ->method('setSubject')
            ->with('Subject');

        $mailHelperMock->expects($this->once())
            ->method('setBody')
            ->with('Message');

        $mailHelperMock->expects($this->once())
            ->method('parsePlainText')
            ->with('Message');

        $mailHelperMock->expects($this->once())
            ->method('attachFile')
            ->with('path-to-a-file');

        $mailHelperMock->expects($this->once())
            ->method('send')
            ->with(true);

        $sendSchedule = new SendSchedule($mailHelperMock, $messageSchedule);

        $sendSchedule->send($scheduler, 'path-to-a-file');
    }

    public function testSendScheduleWithoutFile()
    {
        $report = new Report();
        $report->setToAddress('john@doe.com, doe@john.com');
        $scheduler = new Scheduler($report, new \DateTime());

        $mailHelperMock = $this->getMockBuilder(MailHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mailHelperMock->expects($this->once())
            ->method('getMailer')
            ->with()
            ->willReturn($mailHelperMock);

        $messageSchedule = $this->getMockBuilder(MessageSchedule::class)
            ->disableOriginalConstructor()
            ->getMock();

        $messageSchedule->expects($this->once())
            ->method('getSubject')
            ->with($report)
            ->willReturn('Subject');

        $messageSchedule->expects($this->once())
            ->method('getMessage')
            ->with($report, 'path-to-a-file')
            ->willReturn('Message');

        $messageSchedule->expects($this->once())
            ->method('fileCouldBeSend')
            ->with('path-to-a-file')
            ->willReturn(false);

        $mailHelperMock->expects($this->once())
            ->method('setTo')
            ->with(['john@doe.com', 'doe@john.com']);

        $mailHelperMock->expects($this->once())
            ->method('setSubject')
            ->with('Subject');

        $mailHelperMock->expects($this->once())
            ->method('setBody')
            ->with('Message');

        $mailHelperMock->expects($this->once())
            ->method('parsePlainText')
            ->with('Message');

        $mailHelperMock->expects($this->never())
            ->method('attachFile');

        $mailHelperMock->expects($this->once())
            ->method('send')
            ->with(true);

        $sendSchedule = new SendSchedule($mailHelperMock, $messageSchedule);

        $sendSchedule->send($scheduler, 'path-to-a-file');
    }
}
