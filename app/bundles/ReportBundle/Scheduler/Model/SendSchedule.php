<?php

namespace Mautic\ReportBundle\Scheduler\Model;

use Mautic\CoreBundle\Form\DataTransformer\ArrayStringTransformer;
use Mautic\EmailBundle\Helper\MailHelper;
use Mautic\ReportBundle\Entity\Scheduler;
use Mautic\ReportBundle\Event\PermanentReportFileCreatedEvent;
use Mautic\ReportBundle\Exception\FileTooBigException;
use Mautic\ReportBundle\ReportEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class SendSchedule
{
    private MailHelper $mailer;

    public function __construct(
        MailHelper $mailer,
        private MessageSchedule $messageSchedule,
        private FileHandler $fileHandler,
        private EventDispatcherInterface $eventDispatcher,
    ) {
        $this->mailer = $mailer->getMailer();
    }

    public function send(Scheduler $scheduler, $csvFilePath): void
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
            $this->mailer->attachFile($csvFilePath, basename($csvFilePath), 'text/csv');
        } catch (FileTooBigException) {
            $zipFilePath = $this->fileHandler->zipIt($csvFilePath);
            try {
                // Try to send the ZIP file as an email attachement.
                $this->fileHandler->fileCanBeAttached($zipFilePath);
                $this->mailer->attachFile($zipFilePath, basename($zipFilePath), 'application/zip');
            } catch (FileTooBigException) {
                // Send the ZIP file as link in the email message.
                $this->fileHandler->moveZipToPermanentLocation($report, $zipFilePath);
                $message = $this->messageSchedule->getMessageForLinkedFile($report);
                $event   = new PermanentReportFileCreatedEvent($report);
                $this->eventDispatcher->dispatch($event, ReportEvents::REPORT_PERMANENT_FILE_CREATED);
            }
        }

        $this->mailer->setTo($emails);
        $this->mailer->setSubject($subject);
        $this->mailer->setBody($message);
        $this->mailer->parsePlainText($message);
        $this->mailer->send(true);

        // Attachment file removal will be done in \Mautic\MessengerBundle\MessageHandler\RemoveReportAttachmentHandler
    }
}
