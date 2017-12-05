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
use SendGrid\Email;
use SendGrid\Mail;
use SendGrid\Personalization;

class SendGridMailPersonalization
{
    /**
     * @param Mail                $mail
     * @param \Swift_Mime_Message $message
     */
    public function addPersonalizedDataToMail(Mail $mail, \Swift_Mime_Message $message)
    {
        if (!$message instanceof MauticMessage) {
            return;
        }

        $metadata = $message->getMetadata();
        foreach ($message->getTo() as $recipientEmail => $recipientName) {
            if (empty($metadata[$recipientEmail])) {
                //Recipient is not in metadata = we do not have tokens for this email.
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
    }
}
