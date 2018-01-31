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
    /**
     * @param Mail                $mail
     * @param \Swift_Mime_Message $message
     */
    public function addMetadataToMail(Mail $mail, \Swift_Mime_Message $message)
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
