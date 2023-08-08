<?php

namespace Mautic\EmailBundle\Tests\Helper\Transport;

use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;

class SmtpTransport implements TransportInterface
{
    /**
     * @var array<string, mixed>
     */
    private $transports = []; // @phpstan-ignore-line

    public Email $sentMessage;

    public function __construct()
    {
        $this->transports['main'] = $this;
    }

    public function send(RawMessage $message, Envelope $envelope = null): ?SentMessage
    {
        if ($message instanceof Email) {
            $this->sentMessage = clone $message;
        }

        return null;
    }

    public function __toString(): string
    {
        return 'null://';
    }
}
