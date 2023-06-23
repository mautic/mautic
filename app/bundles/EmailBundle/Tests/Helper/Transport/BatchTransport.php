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

    public function __construct(private bool $validate = false, private int $maxRecipients = 4, private int $numberToFail = 1)
    {
        $this->transports['main'] = $this;
    }

    public function __toString(): string
    {
        return 'batch://';
    }

    protected function doSend(SentMessage $message): void
    {
        $message = $message->getOriginalMessage();
        \assert($message instanceof MauticMessage);
        $this->metadatas[] = $message->getMetadata();

        if ($this->validate && $this->numberToFail) {
            --$this->numberToFail;

            if (!$message->getSubject()) {
                throw new TransportException('Subject empty');
            }
        }
    }

    public function getMaxBatchLimit(): int
    {
        return $this->maxRecipients;
    }

    public function getMetadatas(): array
    {
        return $this->metadatas;
    }
}
