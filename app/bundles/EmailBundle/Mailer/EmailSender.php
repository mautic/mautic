<?php

namespace Mautic\EmailBundle\Mailer;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\Translation\TranslatorInterface;

class EmailSender
{
    private MailerInterface $mailer;
    private TranslatorInterface $translator;
    private string $emailFrom;
    private string $nameFrom;

    public function __construct(MailerInterface $mailer, TranslatorInterface $translator, string $emailFrom, string $nameFrom)
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

    public function sendEmail(Address $from, Address $to, string $subject, string $body): void
    {
        $emailMessage = (new Email())
            ->from($from)
            ->to($to)
            ->subject($subject)
            ->html($body);

        $this->mailer->send($emailMessage);
    }
}
