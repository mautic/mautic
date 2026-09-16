<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Helper\Transport;

use Mautic\EmailBundle\Mailer\Message\MauticMessage;
use Mautic\EmailBundle\Mailer\Transport\TokenTransportInterface;
use Mautic\EmailBundle\Mailer\Transport\TokenTransportTrait;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Header\MailboxListHeader;

/**
 * A tokenized transport that rejects a message whose encoded To address is longer than
 * the given limit, as batch providers such as Amazon SES do.
 */
final class AddressLengthEnforcingTransport extends AbstractTransport implements TokenTransportInterface
{
    use TokenTransportTrait;

    private int $sendCount = 0;

    /**
     * @var array<string, mixed>
     */
    private array $lastMetadata = [];

    /**
     * @var \Symfony\Component\Mime\Address[]
     */
    private array $lastTo = [];

    /**
     * MailHelper::getTransport() reads this by reflection, as it does for the round
     * robin and failover transports Symfony ships.
     *
     * @var array<string, mixed>
     */
    private array $transports = []; // @phpstan-ignore-line

    public function __construct(
        private int $addressLengthLimit,
        private int $maxRecipients = 10,
    ) {
        $this->transports['main'] = $this;

        parent::__construct();
    }

    public function __toString(): string
    {
        return 'address-length-enforcing://';
    }

    public function getMaxBatchLimit(): int
    {
        return $this->maxRecipients;
    }

    /**
     * How many times the transport was actually asked to send, so a test can prove it
     * exercised the path rather than passing because nothing happened.
     */
    public function getSendCount(): int
    {
        return $this->sendCount;
    }

    /**
     * @return array<string, mixed>
     */
    public function getLastMetadata(): array
    {
        return $this->lastMetadata;
    }

    /**
     * @return \Symfony\Component\Mime\Address[]
     */
    public function getLastTo(): array
    {
        return $this->lastTo;
    }

    protected function doSend(SentMessage $message): void
    {
        ++$this->sendCount;

        $original = $message->getOriginalMessage();
        \assert($original instanceof Email, 'This transport only ever receives an Email.');

        $this->lastTo       = $original->getTo();
        $this->lastMetadata = $original instanceof MauticMessage ? $original->getMetadata() : [];

        foreach ($original->getTo() as $address) {
            $encodedLength = strlen((new MailboxListHeader('To', [$address]))->getBodyAsString());

            if ($encodedLength > $this->addressLengthLimit) {
                throw new TransportException(sprintf('Address too long: %d bytes', $encodedLength));
            }
        }
    }
}
