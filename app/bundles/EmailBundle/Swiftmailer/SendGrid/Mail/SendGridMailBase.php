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

use Mautic\EmailBundle\Helper\PlainTextMessageHelper;
use SendGrid\Content;
use SendGrid\Email;
use SendGrid\Mail;

class SendGridMailBase
{
    /**
     * @var PlainTextMessageHelper
     */
    private $plainTextMessageHelper;

    public function __construct(PlainTextMessageHelper $plainTextMessageHelper)
    {
        $this->plainTextMessageHelper = $plainTextMessageHelper;
    }

    /**
     * @param \Swift_Mime_Message $message
     *
     * @return Mail
     */
    public function getSendGridMail(\Swift_Mime_Message $message)
    {
        $froms       = $message->getFrom();
        $from        = new Email(current($froms), key($froms));
        $subject     = $message->getSubject();

        $contentMain   = new Content($this->getContentType($message), $message->getBody());
        $contentSecond = null;

        // Plain text message must be first if present
        if ($contentMain->getType() !== 'text/plain') {
            $plainText = $this->plainTextMessageHelper->getPlainTextFromMessageNotStatic($message);
            if ($plainText) {
                $contentSecond = $contentMain;
                $contentMain   = new Content('text/plain', $plainText);
            }
        }

        // Sendgrid class requires to pass an TO email even if we do not have any general one
        // Pass a dummy email and clear it in the next 2 lines
        $to                    = 'dummy-email-to-be-deleted@example.com';
        $mail                  = new Mail($from, $subject, $to, $contentMain);
        $mail->personalization = [];

        if ($contentSecond) {
            $mail->addContent($contentSecond);
        }

        return $mail;
    }

    /**
     * @param \Swift_Mime_Message $message
     *
     * @return string
     */
    private function getContentType(\Swift_Mime_Message $message)
    {
        return $message->getContentType() === 'text/plain' ? $message->getContentType() : 'text/html';
    }
}
