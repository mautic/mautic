<?php

namespace Mautic\EmailBundle\Swiftmailer\SendGrid\Mail;

use SendGrid\BccSettings;
use SendGrid\Mail;
use SendGrid\MailSettings;
use SendGrid\ReplyTo;

class SendGridMailMetadata
{
    public function addMetadataToMail(Mail $mail, \Swift_Mime_SimpleMessage $message)
    {
        $mail_settings = new MailSettings();

        $headers = $message->getHeaders();

        // Some headers headers are reserved and cannot be sent via API
        // -> https://docs.sendgrid.com/api-reference/mail-send/mail-send
        // 'mime-version', 'date' are allowed, but not needed to be when using API, is put by their server
        $skip_headers = [
            'x-sg-id', 'x-sg-eid', 'received', 'dkim-signature', 'content-type',
            'content-transfer-encoding', 'to', 'from', 'subject', 'reply-to', 'cc', 'bcc',
            'mime-version', 'date',
        ];

        foreach ($headers->getAll() as $header) {
            $key   = $header->getFieldName();
            $value = $header->getFieldBody();

            if (in_array(strtolower($key), $skip_headers) || '' === $value) {
                continue;
            }
            $mail->addHeader($key, $value);
        }

        if ($message->getReplyTo()) {
            $replyTo = new ReplyTo(key((array) $message->getReplyTo()));
            $mail->setReplyTo($replyTo);
        }
        if ($message->getBcc()) {
            $bcc_settings = new BccSettings();
            $bcc_settings->setEnable(true);
            $bcc_settings->setEmail(key($message->getBcc()));
            $mail_settings->setBccSettings($bcc_settings);
        }

        $mail->setMailSettings($mail_settings);
    }
}
