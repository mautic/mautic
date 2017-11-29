<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Swiftmailer\SendGrid;

use Mautic\EmailBundle\Helper\PlainTextMassageHelper;
use Mautic\EmailBundle\Swiftmailer\Message\MauticMessage;
use SendGrid\Attachment;
use SendGrid\BccSettings;
use SendGrid\Content;
use SendGrid\Email;
use SendGrid\Mail;
use SendGrid\MailSettings;
use SendGrid\Personalization;
use SendGrid\ReplyTo;

class SendGridApiMessage
{
    /**
     * @var PlainTextMassageHelper
     */
    private $plainTextMassageHelper;

    public function __construct(PlainTextMassageHelper $plainTextMassageHelper)
    {
        $this->plainTextMassageHelper = $plainTextMassageHelper;
    }

    /**
     * @param \Swift_Mime_Message $message
     *
     * @return Mail
     */
    public function getMessage(\Swift_Mime_Message $message)
    {
        $from        = new Email(current($message->getFrom()), key($message->getFrom()));
        $subject     = $message->getSubject();
        $contentHtml = new Content('text/html', $message->getBody());
        $contentText = new Content('text/plain', $this->plainTextMassageHelper->getPlainTextFromMessage($message));

        // Sendgrid class requires to pass an TO email even if we do not have any general one
        // Pass a dummy email and clear it in the next 2 lines
        $to                    = 'dummy-email-to-be-deleted@example.com';
        $mail                  = new Mail($from, $subject, $to, $contentText);
        $mail->personalization = [];

        $mail->addContent($contentHtml);

        $mail_settings = new MailSettings();

        $metadata = ($message instanceof MauticMessage) ? $message->getMetadata() : [];
        foreach ($message->getTo() as $recipientEmail => $recipientName) {
            if (empty($metadata[$recipientEmail])) {
                //Recipient is not in metadata = we do not have tokens for this emil. Not sure if this can happen?
                continue;
            }
            $personalization = new Personalization();
            $to              = new Email($recipientName, $recipientEmail);
            $personalization->addTo($to);

            foreach ($metadata[$recipientEmail]['tokens'] as $token => $value) {
                $personalization->addSubstitution($token, $value);
            }

            $mail->addPersonalization($personalization);
            unset($metadata[$recipientEmail]);
        }

        if ($message->getReplyTo()) {
            $replyTo = new ReplyTo(key($message->getReplyTo()));
            $mail->setReplyTo($replyTo);
        }
        if ($message->getCc()) {
            $bcc_settings = new BccSettings();
            $bcc_settings->setEnable(true);
            $bcc_settings->setEmail(key($message->getCc()));
            $mail_settings->setBccSettings($bcc_settings);
        }
        if ($message->getAttachments()) {
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

        $mail->setMailSettings($mail_settings);

        return $mail;
    }
}
