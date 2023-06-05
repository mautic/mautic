<?php

namespace Mautic\EmailBundle\Swiftmailer\SendGrid\Mail;

use Mautic\EmailBundle\Helper\PlainTextMessageHelper;
use SendGrid\Mail\Content;
use SendGrid\Mail\EmailAddress as Email;
use SendGrid\Mail\From;
use SendGrid\Mail\Mail;
use SendGrid\Mail\Subject;
use SendGrid\Mail\To;

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
        $froms       = $message->getFrom();
        $from        = new From(key($froms), current($froms));
        $subject     = new Subject($message->getSubject());

        $contentMain   = new Content($this->getContentType($message), $message->getBody());
        $contentSecond = null;

        // Plain text message must be first if present
        if ('text/plain' !== $contentMain->getType()) {
            $plainText = $this->plainTextMessageHelper->getPlainTextFromMessageNotStatic($message);
            if ($plainText) {
                $contentSecond = $contentMain;
                $contentMain   = new Content('text/plain', $plainText);
            }
        }

        // Sendgrid class requires to pass an TO email even if we do not have any general one
        // Pass a dummy email and clear it in the next 2 lines
        $to                    = new To('dummy-email-to-be-deleted@example.com');
        $mail                  = new Mail($from, $to, $subject, $contentMain);

        if ($contentSecond) {
            $mail->addContent($contentSecond);
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
