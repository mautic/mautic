<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Helper\Transport;

use Mautic\EmailBundle\Mailer\Message\MauticMessage;
use Mautic\EmailBundle\Mailer\Transport\TokenTransportInterface;
use Mautic\EmailBundle\Mailer\Transport\TokenTransportTrait;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Header\MailboxListHeader;

/**
 * A tokenized transport that rejects a recipient whose encoded address is longer than the
 * given limit, the way Amazon SES does.
 *
 * The recipient it measures is built the way a real batch transport builds it, from the
 * message metadata rather than from the To header the message carries. That is what
 * etailors/mautic-amazon-ses does at Mailer/Transport/AmazonSesTransport.php:308 in tag
 * 1.0.37, where each tokenized recipient becomes
 * `$sentMessage->to(new Address($recipient, $mailData['name'] ?? ''))` before :320 turns
 * it into Destination.ToAddresses. Measuring the header instead would make this double
 * agree with whatever the code under test put there, which tests nothing.
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
     * @var Address[]
     */
    private array $lastTo = [];

    /**
     * The recipients this transport actually measured, built from the metadata.
     *
     * @var Address[]
     */
    private array $lastEnforcedRecipients = [];

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
     * @return Address[]
     */
    public function getLastTo(): array
    {
        return $this->lastTo;
    }

    /**
     * @return Address[]
     */
    public function getLastEnforcedRecipients(): array
    {
        return $this->lastEnforcedRecipients;
    }

    protected function doSend(SentMessage $message): void
    {
        ++$this->sendCount;

        $original = $message->getOriginalMessage();
        \assert($original instanceof Email, 'This transport only ever receives an Email.');

        $this->lastTo                 = $original->getTo();
        $this->lastMetadata           = $original instanceof MauticMessage ? $original->getMetadata() : [];
        $this->lastEnforcedRecipients = $this->buildRecipients($original);

        foreach ($this->lastEnforcedRecipients as $address) {
            $encodedLength = strlen((new MailboxListHeader('To', [$address]))->getBodyAsString());

            if ($encodedLength > $this->addressLengthLimit) {
                throw new TransportException(sprintf('Address too long: %d bytes', $encodedLength));
            }
        }
    }

    /**
     * A tokenized send hands the provider one recipient per metadata entry, built from the
     * name that entry carries. Only a message without metadata ships the To header as is.
     *
     * @return Address[]
     */
    private function buildRecipients(Email $message): array
    {
        $metadata = $message instanceof MauticMessage ? $message->getMetadata() : [];

        if ([] === $metadata) {
            return $message->getTo();
        }

        $recipients = [];

        foreach ($metadata as $email => $mailData) {
            $name = $mailData['name'] ?? '';

            $recipients[] = new Address($email, is_string($name) ? $name : '');
        }

        return $recipients;
    }
}
