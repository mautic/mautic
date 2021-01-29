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
use SendGrid\Mail\Cc;
use SendGrid\Mail\CustomArg;
use SendGrid\Mail\Mail;
use SendGrid\Mail\Personalization;
use SendGrid\Mail\Substitution;
use SendGrid\Mail\To;

class SendGridMailPersonalization
{
    public function addPersonalizedDataToMail(Mail $mail, \Swift_Mime_SimpleMessage $message)
    {
        $subject = $message->getSubject();

        if (!$message instanceof MauticMessage) { //Used for "Send test email" in settings
            $a = 0;
            foreach ($message->getTo() as $recipientEmail => $recipientName) {
                /* For the first personalization object created on contructor */
                if (0 == $a) {
                    $mail->addTo($recipientEmail, $recipientName, ['subject'=>$subject]);
                } else {
                    $personalization = new Personalization();
                    $personalization->addTo(new To($recipientEmail, $recipientName));
                    $personalization->setSubject($subject);

                    $mail->addPersonalization($personalization);
                    ++$a;
                }
            }

            return;
        }

        $metadata = $message->getMetadata();
        $ccEmail  = $message->getCc();

        if ($ccEmail) {
            $cc = new Cc(key($ccEmail), current($ccEmail));
        }

        $i = 0;
        foreach ($message->getTo() as $recipientEmail => $recipientName) {
            if (empty($metadata[$recipientEmail])) {
                //Recipient is not in metadata = we do not have tokens for this email.
                continue;
            }

            /* For the first personalization object created on contructor */
            if (0 == $i) {
                $mail->addTo($recipientEmail, $recipientName, ['subject' => $subject]);
                if (isset($metadata[$recipientEmail]['hashId']) and strlen($metadata[$recipientEmail]['hashId']) > 0) {
                    $mail->addCustomArg(new CustomArg('hashId', $metadata[$recipientEmail]['hashId']));
                }
            } else {
                $personalization = new Personalization();
                $personalization->addTo(new To($recipientEmail, $recipientName));
                $personalization->setSubject($subject);

                if (isset($metadata[$recipientEmail]['hashId']) and strlen($metadata[$recipientEmail]['hashId']) > 0) {
                    $personalization->addCustomArg(new CustomArg('hashId', $metadata[$recipientEmail]['hashId']));
                }

                $mail->addPersonalization($personalization);
            }
            /* add cc */
            if (isset($cc)) {
                $clone = clone $cc;
                $mail->addCc($clone);
            }

            foreach ($metadata[$recipientEmail]['tokens'] as $token => $value) {
                $mail->addDynamicTemplateData(
                    new Substitution($token, (string) $value)
                );
            }
            unset($metadata[$recipientEmail]);
            ++$i;
        }
    }
}
