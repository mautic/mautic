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

class SendSchedule
{
    /**
     * @var MailHelper
     */
    private $mailer;

    /**
     * @var MessageSchedule
     */
    private $messageSchedule;

    public function __construct(MailHelper $mailer, MessageSchedule $messageSchedule)
    {
        $this->mailer          = $mailer->getMailer();
        $this->messageSchedule = $messageSchedule;
    }

    /**
     * @param Scheduler $scheduler
     * @param string    $filePath
     */
    public function send(Scheduler $scheduler, $filePath)
    {
        $this->mailer->reset(true);

        $transformer = new ArrayStringTransformer();
        $report      = $scheduler->getReport();
        $emails      = $transformer->reverseTransform($report->getToAddress());
        $subject     = $this->messageSchedule->getSubject($report);
        $message     = $this->messageSchedule->getMessage($report, $filePath);

        $this->mailer->setTo($emails);
        $this->mailer->setSubject($subject);
        $this->mailer->setBody($message);
        $this->mailer->parsePlainText($message);
        if ($this->messageSchedule->fileCouldBeSend($filePath)) {
            $this->mailer->attachFile($filePath);
        }
        $this->mailer->send(true);
    }
}
