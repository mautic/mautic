<?php

namespace Mautic\EmailBundle\Tests\Helper\Transport;

use Mautic\EmailBundle\Mailer\Message\MauticMessage;

class BcInterfaceTokenTransport implements TransportInterface
{
    private $fromAddresses = [];
    private $metadatas     = [];
    private $validate      = false;
    private $maxRecipients;
    private $numberToFail;
    private $message;

    /**
     * BatchTransport constructor.
     *
     * @param bool $validate
     */
    public function __construct($validate = false, $maxRecipients = 4, $numberToFail = 1)
    {
        $this->validate      = $validate;
        $this->maxRecipients = $maxRecipients;
        $this->numberToFail  = (int) $numberToFail;
    }

    public function send(RawMessage $message, Envelope $envelope = null): ?SentMessage
    {
        $address = !empty($message->getFrom()) ? $message->getFrom()[0]->getAddress() : null;

        $this->message         = $message;
        $this->fromAddresses[] = $address;
        $this->metadatas[]     = $this->getMetadata();

        return null;
    }

    /**
     * @return array
     */
    public function getFromAddresses()
    {
        return $this->fromAddresses;
    }

    /**
     * @return array
     */
    public function getMetadatas()
    {
        return $this->metadatas;
    }

    public function getMetadata()
    {
        return ($this->message instanceof MauticMessage) ? $this->message->getMetadata() : [];
    }

    public function __toString(): string
    {
        return 'BcInterface';
    }
}
