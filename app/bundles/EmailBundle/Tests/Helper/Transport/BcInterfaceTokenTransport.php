<?php

namespace Mautic\EmailBundle\Tests\Helper\Transport;

use Mautic\EmailBundle\Mailer\Message\MauticMessage;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;

class BcInterfaceTokenTransport implements TransportInterface
{
    /**
     * @var array<string, mixed>
     */
    private $transports = []; // @phpstan-ignore-line

    private $fromAddresses = [];

    private $metadatas = [];

    private $validate = false;

    private $numberToFail;

    private $message;

    /**
     * BatchTransport constructor.
     *
     * @param bool $validate
     */
    public function __construct($validate = false, $numberToFail = 1)
    {
        $this->validate           = $validate;
        $this->numberToFail       = (int) $numberToFail;
        $this->transports['main'] = $this;
    }

    public function send(RawMessage $message, Envelope $envelope = null): ?SentMessage
    {
        $address = null;
        if ($message instanceof Email) {
            $address = !empty($message->getFrom()) ? $message->getFrom()[0]->getAddress() : null;
        }

        $this->message         = $message;
        $this->fromAddresses[] = $address;
        $this->metadatas[]     = $this->getMetadata();

        return null;
    }

    public function getFromAddresses(): array
    {
        return $this->fromAddresses;
    }

    public function getMetadatas(): array
    {
        return $this->metadatas;
    }

    public function getMetadata(): array
    {
        return ($this->message instanceof MauticMessage) ? $this->message->getMetadata() : [];
    }

    public function __toString(): string
    {
        return 'BcInterface';
    }
}
