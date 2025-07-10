<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Helper\Transport;

use Mautic\EmailBundle\Mailer\Message\MauticMessage;
use Mautic\EmailBundle\Mailer\Transport\TokenTransportInterface;
use Mautic\EmailBundle\Mailer\Transport\TokenTransportTrait;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;

class BatchTransport extends AbstractTransport implements TokenTransportInterface
{
    use TokenTransportTrait;

    /**
     * @var array<string, mixed>
     */
    private $transports = []; // @phpstan-ignore-line

    private $metadatas  = [];

    /**
     * @var string[]
     */
    private array $fromAddresses = [];

    /**
     * @var string[]
     */
    private array $fromNames = [];

    private ?MauticMessage $message = null;

    public function __construct(private bool $validate = false, private int $maxRecipients = 4, private int $numberToFail = 1)
    {
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

        $this->fromAddresses[] = !empty($message->getFrom()) ? $message->getFrom()[0]->getAddress() : null;
        $this->fromNames[]     = !empty($message->getFrom()) ? $message->getFrom()[0]->getName() : null;
        $this->message         = $message;
    }

    public function getMaxBatchLimit(): int
    {
        return $this->maxRecipients;
    }

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

    public function getMessage(): ?MauticMessage
    {
        return $this->message;
    }
}
