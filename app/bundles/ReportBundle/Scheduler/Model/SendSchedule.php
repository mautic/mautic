<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ReportBundle\Scheduler\Model;

use Mautic\CoreBundle\Form\DataTransformer\ArrayStringTransformer;
use Mautic\EmailBundle\Helper\MailHelper;
use Mautic\ReportBundle\Entity\Scheduler;
use Symfony\Component\Translation\TranslatorInterface;

class SendSchedule
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
     * @param Scheduler $scheduler
     * @param string    $filePath
     */
    public function send(Scheduler $scheduler, $filePath)
    {
        $transformer = new ArrayStringTransformer();
        $report      = $scheduler->getReport();
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
        $this->mailer->attachFile($filePath);

        $this->mailer->send(true);
    }
}
