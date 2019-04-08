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

    /**
     * @var FileHandler
     */
    private $fileHandler;

    /**
     * @param MailHelper      $mailer
     * @param MessageSchedule $messageSchedule
     * @param FileHandler     $fileHandler
     */
    public function __construct(
        MailHelper $mailer,
        MessageSchedule $messageSchedule,
        FileHandler $fileHandler
    ) {
        $this->mailer          = $mailer->getMailer();
        $this->messageSchedule = $messageSchedule;
        $this->fileHandler     = $fileHandler;
    }

    /**
     * @param string $filePath
     */
    public function send(Scheduler $scheduler, $csvFilePath)
    {
        $this->mailer->reset(true);

        $transformer = new ArrayStringTransformer();
        $report      = $scheduler->getReport();
        $emails      = $transformer->reverseTransform($report->getToAddress());
        $subject     = $this->messageSchedule->getSubject($report);
        $message     = $this->messageSchedule->getMessageForAttachedFile($report);

        try {
            // Try to send the CSV file as an email attachement.
            $this->fileHandler->fileCanBeAttached($csvFilePath);
            $this->mailer->attachFile($csvFilePath);
        } catch (FileTooBigException $e) {
            $zipFilePath = $this->fileHandler->zipIt($csvFilePath);
            try {
                // Try to send the ZIP file as an email attachement.
                $this->fileHandler->fileCanBeAttached($zipFilePath);
                $this->mailer->attachFile($zipFilePath);
            } catch (FileTooBigException $e) {
                // Send the ZIP file as link in the email message.
                $this->fileHandler->moveZipToPermanentLocation($zipFilePath);
                $message = $this->messageSchedule->getMessageForLinkedFile($report);
            }
        }

        $this->mailer->setTo($emails);
        $this->mailer->setSubject($subject);
        $this->mailer->setBody($message);
        $this->mailer->parsePlainText($message);
        $this->mailer->send(true);
    }
}
