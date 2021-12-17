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
        $skip_headers = [
            'x-sg-id', 'x-sg-eid', 'received', 'dkim-signature', 'content-type',
            'content-transfer-encoding', 'to', 'from', 'subject', 'reply-to', 'cc', 'bcc',
            'mime-version', 'date',
        ];

        // in prod, this is always defined, but not all tests are mocking headers
        if (false === is_null($headers)) {
            /* if ('Bulk' != $headers->get('Precedence')) {
                // IMHO we should also remove list-unsubscribe header when Precedence != Bulk, but this should be done
                // where list-unsubscribe is created at no in each transport!
                // However, mail sent directly should  try to send each time, even if address have active bounces
                // TODO: turn on BypassBounceManagement
                // current version of vendor "sendgrid/sendgrid": "~6.0" do not support this feature
            } */

            foreach ($headers->getAll() as $header) {
                $key   = $header->getFieldName();
                $value = $header->getFieldBody();

                if (in_array(strtolower($key), $skip_headers) || '' === $value) {
                    continue;
                }
                $mail->addHeader($key, $value);
            }
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
