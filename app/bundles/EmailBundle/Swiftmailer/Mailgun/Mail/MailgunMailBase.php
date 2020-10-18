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

use Mailgun\Message\MessageBuilder;
use  Mautic\EmailBundle\Helper\PlainTextMessageHelper;

class MailgunMailBase
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
     * @return Mail
     */
    public function getMailgunMail(\Swift_Mime_SimpleMessage $message)
    {
        $mail        = new MessageBuilder();
        $froms       = $message->getFrom();
        $name        = explode(' ', current($froms));
        $mail->setFromAddress(key($froms), ['first' => $name[0], 'last' => $name[1]]);
        $mail->setSubject($message->getSubject());
        $type = $this->getContentType($message);

        if ('text/html' == $type) {
            $mail->setHtmlBody($message->getBody());
            $plainText = $this->plainTextMessageHelper->getPlainTextFromMessageNotStatic($message);
            if ($plainText) {
                $mail->setTextBody($plainText);
            }
        } elseif ('text/plain' == $type) {
            $mail->setTextBody($message->getBody());
        }

        return $mail;
    }

    /**
     * @return string
     */
    private function getContentType(\Swift_Mime_SimpleMessage $message)
    {
        return 'text/plain' === $message->getContentType() ? $message->getContentType() : 'text/html';
    }
}
