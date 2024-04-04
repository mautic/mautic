<?php

namespace Mautic\EmailBundle\Tests\MonitoredEmail\Transport;

use Mautic\EmailBundle\Mailer\Transport\BounceProcessorInterface;
use Mautic\EmailBundle\Mailer\Transport\UnsubscriptionProcessorInterface;
use Mautic\EmailBundle\MonitoredEmail\Message;
use Mautic\EmailBundle\MonitoredEmail\Processor\Bounce\BouncedEmail;
use Mautic\EmailBundle\MonitoredEmail\Processor\Unsubscription\UnsubscribedEmail;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\NullTransport;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\RawMessage;

class TestTransport implements TransportInterface, BounceProcessorInterface, UnsubscriptionProcessorInterface
{
    private NullTransport $nullTransport;

    public function __construct()
    {
        $this->nullTransport = new NullTransport();
    }

    public function send(RawMessage $message, Envelope $envelope = null): ?SentMessage
    {
        return $this->nullTransport->send($message, $envelope);
    }

    public function __toString(): string
    {
        return (string) $this->nullTransport;
    }

    public function processBounce(Message $message): BouncedEmail
    {
        return new BouncedEmail();
    }

    public function processUnsubscription(Message $message): UnsubscribedEmail
    {
        return new UnsubscribedEmail('contact@email.com', 'test+unsubscribe_123abc@test.com');
    }
}
