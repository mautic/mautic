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
use SendGrid\BccSettings;
use SendGrid\Mail;
use SendGrid\MailSettings;
use SendGrid\ReplyTo;

class SendGridMailMetadata
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

        if ($message instanceof MauticMessage) {
            $this->addMauticMessageMetadataToMail($mail, $message);
        }
    }

    /**
     * Add mautic-specific metadata to mail as SendGrid "CustomArgs".
     * This includes the following values as set by the MailHelper on the
     * message object:
     *    - hashId
     *    - emailId
     *    - source information: sourceChannel, sourceId.
     */
    private function addMauticMessageMetadataToMail(Mail $mail, MauticMessage $message)
    {
        $metadata = $message->getMetadata();

        // Short-circuit if there's no metadata to be added because the entire
        // metadata array is empty.
        if (empty($metadata)) {
            return;
        }

        $meta = reset($metadata);

        // Nothing to do when the primary metadata entry is empty.
        if (false === $meta || empty($meta)) {
            return;
        }

        if (null != ($hashId = $meta['hashId'] ?? null)) {
            $mail->addCustomArg('hashId', $hashId);
        }

        if (null != ($emailId = $meta['emailId'] ?? null)) {
            $mail->addCustomArg('emailId', $emailId);
        }

        // The MailHelper populates the 'source' as a tuple like
        // ['channelName', 'channelId'] as passed from
        // SendEmailToContact::setEmail(). Since SendGrid expects custom_args
        // values to be strings, the array is split into two args, 'channel'
        // and 'sourceId'.
        if (!empty($meta['source']) && is_array($meta['source'])) {
            list($channel, $sourceId) = $meta['source'];
            $mail->addCustomArg('channel', $channel);
            $mail->addCustomArg('sourceId', $sourceId);
        }
    }
}
