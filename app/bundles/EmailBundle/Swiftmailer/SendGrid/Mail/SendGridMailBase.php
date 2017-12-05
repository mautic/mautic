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

use Mautic\EmailBundle\Helper\PlainTextMassageHelper;
use SendGrid\Content;
use SendGrid\Email;
use SendGrid\Mail;

class SendGridMailBase
{
    /**
     * @var PlainTextMassageHelper
     */
    private $plainTextMassageHelper;

    public function __construct(PlainTextMassageHelper $plainTextMassageHelper)
    {
        $this->plainTextMassageHelper = $plainTextMassageHelper;
    }

    /**
     * @param \Swift_Mime_Message $message
     *
     * @return Mail
     */
    public function getSendGridMail(\Swift_Mime_Message $message)
    {
        $from        = new Email(current($message->getFrom()), key($message->getFrom()));
        $subject     = $message->getSubject();
        $contentHtml = new Content('text/html', $message->getBody());
        $contentText = new Content('text/plain', $this->plainTextMassageHelper->getPlainTextFromMessage($message));

        // Sendgrid class requires to pass an TO email even if we do not have any general one
        // Pass a dummy email and clear it in the next 2 lines
        $to                    = 'dummy-email-to-be-deleted@example.com';
        $mail                  = new Mail($from, $subject, $to, $contentText);
        $mail->personalization = [];

        $mail->addContent($contentHtml);

        return $mail;
    }
}
