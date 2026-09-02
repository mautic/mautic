<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Helper\Transport;

use Mautic\EmailBundle\Mailer\Message\MauticMessage;
use Mautic\EmailBundle\Mailer\Transport\TokenTransportInterface;
use Mautic\EmailBundle\Mailer\Transport\TokenTransportTrait;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;

final class BatchTransport extends AbstractTransport implements TokenTransportInterface
{
    use TokenTransportTrait;

    /**
     * @var array<string, mixed>
     */
    private array $transports = []; // @phpstan-ignore-line
    /**
     * @var array<mixed, array<string, array<string, mixed[]>>>
     */
    private array $metadatas  = [];

    /**
     * @var string[]
     */
    private array $fromAddresses = [];

    /**
     * @var string[]
     */
    private array $fromNames = [];

    /**
     * @var string[]
     */
    private array $replyToAddresses = [];

    private ?MauticMessage $message = null;

    public function __construct(
        private readonly bool $validate = false,
        private readonly int $maxRecipients = 4,
        private int $numberToFail = 1,
    ) {
        $this->transports['main'] = $this;

        parent::__construct();
    }

    public function __toString(): string
    {
        return 'batch://';
    }

    protected function doSend(SentMessage $message): void
    {
        $message = $message->getOriginalMessage();

        if (!$message instanceof MauticMessage) {
            return;
        }

        $this->metadatas[] = $message->getMetadata();

        if ($this->validate && $this->numberToFail) {
            --$this->numberToFail;

            if (!$message->getSubject()) {
                throw new TransportException('Subject empty');
            }
        }

        $this->fromAddresses[]    = !empty($message->getFrom()) ? $message->getFrom()[0]->getAddress() : null;
        $this->fromNames[]        = !empty($message->getFrom()) ? $message->getFrom()[0]->getName() : null;
        $this->replyToAddresses[] = !empty($message->getReplyTo()) ? $message->getReplyTo()[0]->getAddress() : null;
        $this->message            = $message;
    }

    public function getMaxBatchLimit(): int
    {
        return $this->maxRecipients;
    }

    /**
     * @return array<mixed, array<string, array<string, mixed[]>>>
     */
    public function getMetadatas(): array
    {
        return $this->metadatas;
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
     * @return string[]
     */
    public function getReplyToAddresses(): array
    {
        return $this->replyToAddresses;
    }

    public function getMessage(): ?MauticMessage
    {
        return $this->message;
    }
}
