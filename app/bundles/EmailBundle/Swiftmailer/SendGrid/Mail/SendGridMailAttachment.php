<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Swiftmailer\SendGrid\Mail;

use Mautic\EmailBundle\Swiftmailer\Message\MauticMessage;
use SendGrid\Attachment;
use SendGrid\Mail;

class SendGridMailAttachment
{
    /**
     * @param Mail                $mail
     * @param \Swift_Mime_Message $message
     */
    public function addAttachmentsToMail(Mail $mail, \Swift_Mime_Message $message)
    {
        if (!$message instanceof MauticMessage || !$message->getAttachments()) {
            return;
        }

        foreach ($message->getAttachments() as $emailAttachment) {
            $fileContent = @file_get_contents($emailAttachment['filePath']);
            if ($fileContent === false) {
                continue;
            }
            $base64 = base64_encode($fileContent);

            $attachment = new Attachment();
            $attachment->setContent($base64);
            $attachment->setType($emailAttachment['contentType']);
            $attachment->setFilename($emailAttachment['fileName']);
            $mail->addAttachment($attachment);
        }
    }
}
