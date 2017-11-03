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
use Mautic\ReportBundle\Scheduler\Model\SendSchedule;
use Symfony\Component\Translation\TranslatorInterface;

class SendScheduleTest extends \PHPUnit_Framework_TestCase
{
    public function testSendSchedule()
    {
        $mailHelperMock = $this->getMockBuilder(MailHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mailHelperMock->expects($this->once())
            ->method('getMailer')
            ->with()
            ->willReturn($mailHelperMock);

        $translatorMock = $this->getMockBuilder(TranslatorInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $translatorMock->expects($this->at(0))
            ->method('trans')
            ->willReturn('Subject');

        $translatorMock->expects($this->at(1))
            ->method('trans')
            ->willReturn('Message');

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

        $sendSchedule = new SendSchedule($mailHelperMock, $translatorMock);

        $report = new Report();
        $report->setToAddress('john@doe.com, doe@john.com');
        $scheduler = new Scheduler($report, new \DateTime());

        $sendSchedule->send($scheduler, 'path-to-a-file');
    }
}
