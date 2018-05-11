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

use SendGrid\Mail;

class SendGridMailHeader
{
    /**
     * @param Mail                $mail
     * @param \Swift_Mime_Message $message
     */
    public function addHeadersToMail(Mail $mail, \Swift_Mime_Message $message)
    {
        $headers = $message->getHeaders()->getAll();
        /** @var \Swift_Mime_Header $header */
        foreach ($headers as $header) {
            if ($header->getFieldType() == \Swift_Mime_Header::TYPE_TEXT) {
                $mail->addHeader($header->getFieldName(), $header->getFieldBodyModel());
            }
        }
    }
}
