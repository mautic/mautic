<?php

declare(strict_types=1);

namespace Mautic\MessengerBundle\MessageHandler;

use Mautic\CoreBundle\Helper\FilePathResolver;
use Mautic\ReportBundle\Model\ExportHandler;
use Symfony\Component\Mailer\Messenger\SendEmailMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Email;

#[AsMessageHandler(priority: -1000)]
class RemoveReportAttachmentHandler
{
    public function __construct(private ExportHandler $exportHandler, private FilePathResolver $filePathResolver)
    {
    }

    public function __invoke(SendEmailMessage $message): void
    {
        $email = $message->getMessage();

        if (!$email instanceof Email) {
            return;
        }

        $attachments = $email->getAttachments();

        foreach ($attachments as $attachment) {
            $headers = $attachment->getPreparedHeaders();
            if (!$headers->has('Content-Disposition')) {
                continue;
            }

            $filename = $headers->getHeaderParameter('Content-Disposition', 'filename');
            if (null === $filename) {
                continue;
            }

            $attachmentPath = $this->exportHandler->getPath(pathinfo($filename, \PATHINFO_FILENAME));

            $this->filePathResolver->delete($attachmentPath);
            // str_replace as in \Mautic\ReportBundle\Scheduler\Model\FileHandler::zipIt
            $this->filePathResolver->delete(str_replace('.csv', '.zip', $attachmentPath));
        }
    }
}
