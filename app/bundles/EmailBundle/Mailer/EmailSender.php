<?php

namespace Mautic\EmailBundle\Mailer;

use Mautic\EmailBundle\Helper\MailHelper;
use Symfony\Component\Mime\Address;
use Symfony\Contracts\Translation\TranslatorInterface;

class EmailSender
{
    /**
     * @var MailHelper
     */
    private $mailer;

    private TranslatorInterface $translator;

    private string $emailFrom;

    private string $nameFrom;

    public function __construct(MailHelper $mailer, TranslatorInterface $translator, string $emailFrom, string $nameFrom)
    {
        $this->mailer     = $mailer;
        $this->translator = $translator;
        $this->emailFrom  = $emailFrom;
        $this->nameFrom   = $nameFrom;
    }

    public function sendTestEmail(Address $address): void
    {
        $this->sendEmail(
            new Address($this->emailFrom, $this->nameFrom ?? ''),
            $address,
            $this->translator->trans('mautic.email.config.mailer.transport.test_send.subject'),
            $this->translator->trans('mautic.email.config.mailer.transport.test_send.body')
        );
    }

    public function sendEmail(Address $from, Address $to, string $subject, string $body, ?string $text = null): void
    {
        $this->mailer->setFrom($from->getAddress(), $from->getName());
        $this->mailer->setSubject($subject);
        $this->mailer->setTo($to->getAddress(), $to->getName());
        $this->mailer->setBody($body);

        if (!empty($text)) {
            $this->mailer->setPlainText($text);
        }

        $this->mailer->send();
    }
}
