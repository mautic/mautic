<?php

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Swiftmailer\Mailgun\Mail;

class MailgunMailMetadata
{
    public function addMetadataToMail(Mail $mail, \Swift_Mime_SimpleMessage $message)
    {
        $mail_settings = new MailSettings();

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
