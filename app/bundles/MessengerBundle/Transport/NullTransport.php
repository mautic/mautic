<?php

declare(strict_types=1);

namespace Mautic\MessengerBundle\Transport;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\TransportInterface;

class NullTransport implements TransportInterface
{
    public function get(): iterable
    {
        return [];
    }

    public function ack(Envelope $envelope): void
    {
    }

    public function reject(Envelope $envelope): void
    {
    }

    public function send(Envelope $envelope): Envelope
    {
        return $envelope;
    }
}
