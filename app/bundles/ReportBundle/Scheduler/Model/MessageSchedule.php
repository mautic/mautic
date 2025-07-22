<?php

namespace Mautic\ReportBundle\Scheduler\Model;

use Mautic\ReportBundle\Entity\Report;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class MessageSchedule
{
    public function __construct(
        private TranslatorInterface $translator,
        private UrlGeneratorInterface $router,
    ) {
    }

    public function getMessageForAttachedFile(Report $report): string
    {
        $link = $this->router->generate('mautic_report_view', ['objectId' => $report->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        $date = new \DateTime();

        return $this->translator->trans(
            'mautic.report.schedule.email.message',
            ['%report_name%' => $report->getName(), '%date%' => $date->format('Y-m-d'), '%link%' => $link]
        );
    }

    public function getMessageForLinkedFile(Report $report): string
    {
        $link = $this->router->generate('mautic_report_download', ['reportId' => $report->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return $this->translator->trans(
            'mautic.report.schedule.email.message_file_linked',
            ['%report_name%' => $report->getName(), '%link%' => $link]
        );
    }

    public function getSubject(Report $report): string
    {
        $date = new \DateTime();

        return $this->translator->trans(
            'mautic.report.schedule.email.subject',
            ['%report_name%' => $report->getName(), '%date%' => $date->format('Y-m-d')]
        );
    }
}
