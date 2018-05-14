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
     * https://sendgrid.com/docs/API_Reference/Web_API_v3/Mail/errors.html#personalizations_headers.
     *
     * @var array
     */
    private $reservedKeys = [
        'x-sg-id',
        'x-sg-eid',
        'received',
        'dkim-signature',
        'Content-Type',
        'Content-Transfer-Encoding',
        'To',
        'From',
        'Subject',
        'Reply-To',
        'CC',
        'BCC',
    ];

    /**
     * @param Mail                $mail
     * @param \Swift_Mime_Message $message
     */
    public function addHeadersToMail(Mail $mail, \Swift_Mime_Message $message)
    {
        $headers = $message->getHeaders()->getAll();
        /** @var \Swift_Mime_Header $header */
        foreach ($headers as $header) {
            $headerName = $header->getFieldName();
            if ($header->getFieldType() == \Swift_Mime_Header::TYPE_TEXT && !in_array($headerName, $this->reservedKeys)) {
                $mail->addHeader($headerName, $header->getFieldBodyModel());
            }
        }
    }
}
