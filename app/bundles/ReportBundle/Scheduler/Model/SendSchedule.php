use Mautic\CoreBundle\Form\DataTransformer\ArrayStringTransformer;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PathsHelper; // For file cleanup
use Mautic\EmailBundle\Helper\MailHelper;
use Mautic\EmailBundle\Message\SendEmailMessage;
use Mautic\ReportBundle\Entity\Scheduler as SchedulerEntity; // Alias to avoid conflict
use Mautic\ReportBundle\Event\PermanentReportFileCreatedEvent;
use Mautic\ReportBundle\Exception\FileTooBigException;
use Mautic\ReportBundle\ReportEvents;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Mime\Address as SymfonyAddress;
use Symfony\Component\Mime\Email as SymfonyEmail;
use Symfony\Contracts\Translation\TranslatorInterface;

class SendSchedule
{
    public function __construct(
        private MailHelper $mailHelper,
        private MessageSchedule $messageSchedule,
        private FileHandler $fileHandler,
        private EventDispatcherInterface $eventDispatcher,
        private CoreParametersHelper $coreParametersHelper,
        private MessageBusInterface $messageBus,
        private PathsHelper $pathsHelper,
        private TranslatorInterface $translator,
        private LoggerInterface $logger
    ) {
    }

    public function send(SchedulerEntity $scheduler, string $csvFilePath): bool
    {
        $report      = $scheduler->getReport();
        $transformer = new ArrayStringTransformer();
        $rawEmails   = $transformer->reverseTransform($report->getToAddress());
        $subject     = $this->messageSchedule->getSubject($report);
        $messageHtml = $this->messageSchedule->getMessageForAttachedFile($report);
        $messageText = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $messageHtml));

        $attachmentPath         = null;
        $attachmentName         = null;
        $attachmentMime         = null;
        $publicLink             = null;
        $zipFilePath            = null;
        $fileToCleanupAfterSend = null;

        try {
            $this->fileHandler->fileCanBeAttached($csvFilePath);
            $attachmentPath         = $csvFilePath;
            $attachmentName         = basename($csvFilePath);
            $attachmentMime         = 'text/csv';
            $fileToCleanupAfterSend = $csvFilePath;
        } catch (FileTooBigException) {
            $zipFilePath = $this->fileHandler->zipIt($csvFilePath);
            try {
                $this->fileHandler->fileCanBeAttached($zipFilePath);
                $attachmentPath         = $zipFilePath;
                $attachmentName         = basename($zipFilePath);
                $attachmentMime         = 'application/zip';
                $fileToCleanupAfterSend = $zipFilePath;
            } catch (FileTooBigException) {
                $this->fileHandler->moveZipToPermanentLocation($report, $zipFilePath);
                $messageHtml    = $this->messageSchedule->getMessageForLinkedFile($report);
                $messageText    = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $messageHtml));
                $publicLink     = $this->messageSchedule->getPublicLink($report);
                $event          = new PermanentReportFileCreatedEvent($report, $publicLink);
                $this->eventDispatcher->dispatch($event, ReportEvents::REPORT_PERMANENT_FILE_CREATED);
                $attachmentPath = null;
            }
        }

        $symfonyToAddresses = [];
        foreach ($rawEmails as $recipient) {
            if (!empty($recipient) && filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
                $symfonyToAddresses[] = new SymfonyAddress($recipient);
            } else {
                $this->logger->warning(sprintf('Invalid recipient email address "%s" for report ID %d. Skipping.', $recipient, $report->getId()));
            }
        }

        if (empty($symfonyToAddresses)) {
            $this->logger->error(sprintf('No valid recipient email addresses for report ID %d after filtering. Email not sent.', $report->getId()));
            $this->pathsHelper->deleteTemporaryFile($csvFilePath); // Using PathsHelper for consistency
            if ($zipFilePath && $this->fileHandler->exists($zipFilePath)) {
                $this->pathsHelper->deleteTemporaryFile($zipFilePath);
            }
            return false;
        }

        $email = new SymfonyEmail();
        $email->subject($subject)
              ->html($messageHtml)
              ->text($messageText)
              ->to(...$symfonyToAddresses);

        $fromEmail = $this->coreParametersHelper->get('mailer_from_email');
        $fromName  = $this->coreParametersHelper->get('mailer_from_name');
        if ($fromEmail) {
            $email->from(new SymfonyAddress($fromEmail, $fromName ?: ''));
        } else {
            $email->from(new SymfonyAddress('no-reply@mautic.com', 'Mautic'));
            $this->logger->warning('Mailer from_email not configured. Using default for scheduled report ID '.$report->getId().'.');
        }

        if ($attachmentPath && $attachmentName && $attachmentMime && empty($publicLink)) {
            $email->attachFromPath($attachmentPath, $attachmentName, $attachmentMime);
        }

        $queueMode      = $this->coreParametersHelper->get('mailer_spool_type');
        $isQueueEnabled = ($queueMode && 'sync' !== $queueMode);
        $sent           = false;

        try {
            if ($isQueueEnabled && $this->messageBus) {
                $mauticMessage = new SendEmailMessage($email);
                $this->messageBus->dispatch($mauticMessage);
                $this->logger->info('Report email for scheduler ID ' . $scheduler->getId() . ' dispatched to the message bus.');
                $sent = true;

                // If queued, the original CSV (if a ZIP was made from it and queued) should be cleaned.
                if ($attachmentPath === $zipFilePath && $csvFilePath !== $zipFilePath && $this->fileHandler->exists($csvFilePath)) {
                    $this->pathsHelper->deleteTemporaryFile($csvFilePath);
                }
                // Note: $attachmentPath (if it's a temp file like the zip) will be handled by the Message Handler after successful send.
                // If CSV was directly set as $attachmentPath, it too will be handled by the consumer.

            } else {
                $mh = $this->mailHelper->getMailer(true);
                $mh->setSubject($subject);
                $mh->setBody($messageHtml);
                $mh->setPlainText($messageText);

                if ($fromEmail) {
                    $mh->setFrom($fromEmail, $fromName ?: '');
                } else {
                     $mh->setFrom('no-reply@mautic.com', 'Mautic');
                }

                $mh->setTo($rawEmails); // MailHelper can handle an array of raw email strings

                if ($attachmentPath && $attachmentName && $attachmentMime && empty($publicLink)) {
                     $mh->attachFile($attachmentPath, $attachmentName, $attachmentMime);
                }

                if ($mh->send(true, true)) {
                    $sent = true;
                    $this->logger->info('Report email for scheduler ID ' . $scheduler->getId() . ' sent immediately via MailHelper.');
                } else {
                    $errors = $mh->getErrors(false);
                    $this->logger->error('Report email for scheduler ID '.$scheduler->getId().' failed to send immediately via MailHelper.', ['errors' => $errors]);
                    $sent = false;
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('Error processing report email for scheduler ID '.$scheduler->getId().': '.$e->getMessage(), ['exception' => $e]);
            $sent = false;
        }

        // Cleanup logic for files
        if ($sent && !$isQueueEnabled) {
            if ($fileToCleanupAfterSend && $this->fileHandler->exists($fileToCleanupAfterSend)) {
                $this->pathsHelper->deleteTemporaryFile($fileToCleanupAfterSend);
            }
            if ($fileToCleanupAfterSend === $zipFilePath && $csvFilePath !== $zipFilePath && $this->fileHandler->exists($csvFilePath)) {
                 $this->pathsHelper->deleteTemporaryFile($csvFilePath);
            }
        } elseif ($publicLink) {
            // Original CSV and temp ZIP (if created before permanent move)
            if ($this->fileHandler->exists($csvFilePath)) {
                $this->pathsHelper->deleteTemporaryFile($csvFilePath);
            }
            if ($zipFilePath && $attachmentPath !== $zipFilePath && $this->fileHandler->exists($zipFilePath) ) {
                 $this->pathsHelper->deleteTemporaryFile($zipFilePath);
            }
        }
        // If not sent and not a public link, the original $csvFilePath and potentially $zipFilePath (if it was $fileToCleanupAfterSend) might remain.
        // This is acceptable for inspection on failure. If $isQueueEnabled and $sent is true, files are left for the handler.

        return $sent;
    }
}
