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

use SendGrid\Mail\Mail;

class SendGridMailMetadata
{
    public function addMetadataToMail(Mail $mail, \Swift_Mime_SimpleMessage $message)
    {
        if ($message->getReplyTo()) {
            $mail->setReplyTo(key($message->getReplyTo()), key($message->getReplyTo()));
        }
        if ($message->getBcc()) {
            $mail->addBcc(key($message->getBcc()), key($message->getBcc()));
        }
    }
}
