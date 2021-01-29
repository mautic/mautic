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
use SendGrid\Mail\Mail;

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
     * @return Mail
     */
    public function getSendGridMail(\Swift_Mime_SimpleMessage $message)
    {
        /* For 7.9 create Mail instance */
        $email = new Mail();

        $froms = $message->getFrom();

        $email->setFrom(key($froms), current($froms));

        $subject = $message->getSubject();
        $email->setSubject($subject);
        $email->addContent($this->getContentType($message), $message->getBody());

        return $email;
    }

    /**
     * @return string
     */
    private function getContentType(\Swift_Mime_SimpleMessage $message)
    {
        return 'text/plain' === $message->getContentType() ? $message->getContentType() : 'text/html';
    }
}
