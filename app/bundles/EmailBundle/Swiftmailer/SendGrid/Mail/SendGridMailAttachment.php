<?php

namespace Mautic\EmailBundle\Swiftmailer\SendGrid\Mail;

use Mautic\EmailBundle\Swiftmailer\Message\MauticMessage;
use SendGrid\Attachment;
use SendGrid\Mail;

class SendGridMailAttachment
{
    public function addAttachmentsToMail(Mail $mail, \Swift_Mime_SimpleMessage $message)
    {
        if ($message instanceof MauticMessage && $message->getAttachments()) {
            foreach ($message->getAttachments() as $emailAttachment) {
                $fileContent = @file_get_contents($emailAttachment['filePath']);
                if (false === $fileContent) {
                    continue;
                }
                $base64 = base64_encode($fileContent);

                $attachment = new Attachment();
                $attachment->setContent($base64);
                $attachment->setType($emailAttachment['contentType']);
                $attachment->setFilename($emailAttachment['fileName']);
                $mail->addAttachment($attachment);
            }
        } elseif (is_array($message->getChildren())) {
            foreach ($message->getChildren() as $child) {
                if ($child instanceof \Swift_Attachment) {
                    $attachment = new Attachment();
                    $base64     = base64_encode($child->getBody());
                    $attachment->setContent($base64);
                    $attachment->setType($child->getContentType());
                    $attachment->setFilename($child->getFilename());
                    $mail->addAttachment($attachment);
                }
            }
        }
    }
}
