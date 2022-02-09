<?php

namespace Mautic\EmailBundle\Mailer;

use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Transport;

class MailerFactory
{
    private MailerInterface $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    public function getMailerByDsn(string $dsnString, EventDispatcherInterface $dispatcher = null): MailerInterface
    {
        return new Mailer(
            Transport::fromDsn($dsnString),
            null,
            $dispatcher
        );
    }
}
