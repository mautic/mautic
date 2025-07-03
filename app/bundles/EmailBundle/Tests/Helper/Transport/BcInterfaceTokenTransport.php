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

    /**
     * @var string[]
     */
    private $fromAddresses = [];

    /**
     * @var string[]
     */
    private $fromNames = [];

    private $numberToFail;

    /**
     * @var mixed[]
     */
    private array $metadatas = [];

    /**
     * @var RawMessage
     */
    private $message;

    /**
     * @param bool $validate
     */
    public function __construct(
        private $validate = false,
        $numberToFail = 1,
    ) {
        $this->numberToFail       = (int) $numberToFail;
        $this->transports['main'] = $this;
    }

    public function send(RawMessage $message, Envelope $envelope = null): ?SentMessage
    {
        if ($message instanceof Email) {
            $this->fromAddresses[] = !empty($message->getFrom()) ? $message->getFrom()[0]->getAddress() : null;
            $this->fromNames[]     = !empty($message->getFrom()) ? $message->getFrom()[0]->getName() : null;
        }

        $this->message     = $message;
        $this->metadatas[] = $this->getMetadata();

        return null;
    }

    /**
     * @return string[]
     */
    public function getFromAddresses(): array
    {
        return $this->fromAddresses;
    }

    /**
     * @return string[]
     */
    public function getFromNames(): array
    {
        return $this->fromNames;
    }

    /**
     * @return mixed[]
     */
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
