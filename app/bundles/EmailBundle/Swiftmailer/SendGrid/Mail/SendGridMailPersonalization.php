<?php

namespace Mautic\EmailBundle\Swiftmailer\SendGrid\Mail;

use Mautic\EmailBundle\Swiftmailer\Message\MauticMessage;
use SendGrid\Mail\Mail;
use SendGrid\Mail\Personalization;
use SendGrid\Mail\To;

class SendGridMailPersonalization
{
    public function addPersonalizedDataToMail(Mail $mail, \Swift_Mime_SimpleMessage $message)
    {
        if (!$message instanceof MauticMessage) { // Used for "Send test email" in settings
            foreach ($message->getTo() as $recipientEmail => $recipientName) {
                $personalization = new Personalization();
                $to              = new To($recipientEmail, $recipientName);
                $personalization->addTo($to);
                $mail->addPersonalization($personalization);
            }

            return;
        }

        $metadata = $message->getMetadata();
        $ccEmail  = $message->getCc();
        if ($ccEmail) {
            $cc = new To(key($ccEmail), current($ccEmail));
        }
        foreach ($message->getTo() as $recipientEmail => $recipientName) {
            if (empty($metadata[$recipientEmail])) {
                // Recipient is not in metadata = we do not have tokens for this email.
                continue;
            }
            $personalization = new Personalization();
            $to              = new To($recipientEmail, $recipientName);
            $personalization->addTo($to);

            if (isset($cc)) {
                $clone = clone $cc;
                $personalization->addCc($clone);
            }

            foreach ($metadata[$recipientEmail]['tokens'] as $token => $value) {
                $personalization->addSubstitution($token, (string) $value);
            }

            $mail->addPersonalization($personalization);
            unset($metadata[$recipientEmail]);
        }
    }
}
