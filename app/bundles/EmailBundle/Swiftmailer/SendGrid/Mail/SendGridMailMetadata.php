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
        $skip_headers = array(
            'x-sg-id', 'x-sg-eid', 'received', 'dkim-signature', 'content-type',
            'content-transfer-encoding', 'to', 'from', 'subject', 'reply-to', 'cc', 'bcc',
            'mime-version', 'date',
        );

        // remove list-unsubscribe header when not Bulk
        if ('Bulk' != $headers->get('Precedence')) {
            $skip_headers[] = 'list-unsubscribe';
            // Mail sent directly should  try to send each time, IMHO, eg: password reset forms!
            // TODO: turn on BypassBounceManagement
            // however, current version of vendor SendGrid does not support this object yet
        }

        foreach ($headers->getAll() as $header) {
            $key = $header->getFieldName();
            $value = $header->getFieldBody();

            if (in_array(strtolower($key), $skip_headers) || $value === '') {
                continue;
            }
            $mail->addHeader($key, $value);
        }

        if ($message->getReplyTo()) {
            $replyTo = new ReplyTo(key($message->getReplyTo()));
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
