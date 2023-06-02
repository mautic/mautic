<?php

namespace Mautic\EmailBundle\Mailer\Transport;

use Mautic\EmailBundle\Mailer\Message\MauticMessage;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\Smtp\SmtpTransport;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;

/**
 * Class AbstractBatchTransport.
 */
abstract class AbstractTokenSmtpTransport extends SmtpTransport implements TokenTransportInterface
{
    /**
     * @var MauticMessage|RawMessage|Email
     */
    protected $message;

    /**
     * Do whatever is necessary to $this->message in order to deliver a batched payload. i.e. add custom headers, etc.
     */
    abstract protected function prepareMessage(): void;

    /**
     * @param Envelope $envelope
     *
     * @return SentMessage
     *
     * @throws \Exception
     */
    public function send(RawMessage $message, Envelope $envelope = null): ?SentMessage
    {
        $this->message = $message;

        $this->prepareMessage();

        return parent::send($message, $envelope);
    }

    /**
     * Get the metadata from a MauticMessage.
     *
     * @return array<string, mixed>
     */
    public function getMetadata()
    {
        return ($this->message instanceof MauticMessage) ? $this->message->getMetadata() : [];
    }

    /**
     * Get attachments from a MauticMessage.
     *
     * @return array<string, mixed>
     */
    public function getAttachments()
    {
        return ($this->message instanceof MauticMessage) ? $this->message->getAttachments() : [];
    }
}
