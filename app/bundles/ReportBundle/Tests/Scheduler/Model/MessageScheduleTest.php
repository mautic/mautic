<?php

namespace Mautic\ReportBundle\Tests\Scheduler\Model;

use Mautic\ReportBundle\Entity\Report;
use Mautic\ReportBundle\Scheduler\Model\MessageSchedule;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Router;
use Symfony\Contracts\Translation\TranslatorInterface;

class MessageScheduleTest extends \PHPUnit\Framework\TestCase
{
    private MockObject&Router $router;

    private MockObject&TranslatorInterface $translatorMock;

    private MessageSchedule $messageSchedule;

    protected function setUp(): void
    {
        $this->router          = $this->createMock(Router::class);
        $this->translatorMock  = $this->createMock(TranslatorInterface::class);
        $this->messageSchedule = new MessageSchedule(
            $this->translatorMock,
            $this->router
        );
    }

    public function testGetMessageForAttachedFile(): void
    {
        $report = $this->createMock(Report::class);

        $report->expects($this->once())
            ->method('getId')
            ->willReturn(33);

        $this->router->expects($this->once())
            ->method('generate')
            ->with('mautic_report_view', ['objectId' => 33], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('absolute/link');

        $this->translatorMock->expects($this->once())
            ->method('trans')
            ->with('mautic.report.schedule.email.message')
            ->willReturn('The message');

        $this->assertSame('The message', $this->messageSchedule->getMessageForAttachedFile($report));
    }

    public function testGetMessageForLinkedFile(): void
    {
        $report = $this->createMock(Report::class);

        $report->expects($this->once())
            ->method('getId')
            ->willReturn(33);

        $report->expects($this->once())
            ->method('getName')
            ->willReturn('Report ABC');

        $this->router->expects($this->once())
            ->method('generate')
            ->with('mautic_report_download', ['reportId' => 33], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('absolute/link');

        $this->translatorMock->expects($this->once())
            ->method('trans')
            ->with('mautic.report.schedule.email.message_file_linked')
            ->willReturn('The message');

        $this->assertSame('The message', $this->messageSchedule->getMessageForLinkedFile($report));
    }

    public function testGetSubject(): void
    {
        $report = $this->createMock(Report::class);

        $report->expects($this->once())
            ->method('getName')
            ->willReturn('Report ABC');

        $this->translatorMock->expects($this->once())
            ->method('trans')
            ->with('mautic.report.schedule.email.subject')
            ->willReturn('The subject');

        $this->assertSame('The subject', $this->messageSchedule->getSubject($report));
    }
}
