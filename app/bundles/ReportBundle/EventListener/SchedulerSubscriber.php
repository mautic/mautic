<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ReportBundle\EventListener;

use Mautic\CoreBundle\Form\DataTransformer\ArrayStringTransformer;
use Mautic\EmailBundle\Helper\MailHelper;
use Mautic\ReportBundle\Event\ReportScheduleSendEvent;
use Mautic\ReportBundle\ReportEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class SchedulerSubscriber.
 */
class SchedulerSubscriber implements EventSubscriberInterface
{
    /**
     * @var MailHelper
     */
    private $mailer;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(MailHelper $mailer, TranslatorInterface $translator)
    {
        $this->mailer     = $mailer->getMailer();
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            ReportEvents::REPORT_SCHEDULE_SEND => ['onScheduleSend', 0],
        ];
    }

    public function onScheduleSend(ReportScheduleSendEvent $event)
    {
        $transformer = new ArrayStringTransformer();
        $scheduler   = $event->getScheduler();
        $report      = $scheduler->getReport();
        $file        = $event->getFile();
        $emails      = $transformer->reverseTransform($report->getToAddress());
        $date        = new \DateTime();
        $subject     = $this->translator->trans(
            'mautic.report.schedule.email.subject',
            ['%report_name%' => $report->getName(), '%date%' => $date->format('Y-m-d')]
        );
        $message = $this->translator->trans(
            'mautic.report.schedule.email.message',
            ['%report_name%' => $report->getName(), '%date%' => $date->format('Y-m-d')]
        );

        $this->mailer->setTo($emails);
        $this->mailer->setSubject($subject);
        $this->mailer->setBody($message);
        $this->mailer->parsePlainText($message);
        $this->mailer->attachFile($file);

        $this->mailer->send(true);
    }
}
