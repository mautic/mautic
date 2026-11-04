<?php

declare(strict_types=1);

namespace Mautic\WebhookBundle\Entity;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WebhookQueueRepository::class)]
#[ORM\Table(name: self::TABLE_NAME)]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class WebhookQueue
{
    public const string TABLE_NAME = 'webhook_queue';

    #[ORM\Id]
    #[ORM\Column(type: 'bigint', options: ['unsigned' => true])]
    #[ORM\GeneratedValue]
    private int|string|null $id = null;

    #[ORM\ManyToOne(targetEntity: Webhook::class)]
    #[ORM\JoinColumn(name: 'webhook_id', nullable: false, onDelete: 'CASCADE')]
    private ?Webhook $webhook = null;

    #[ORM\Column(name: 'date_added', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTime $dateAdded = null;

    #[ORM\Column(name: 'date_modified', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $dateModified = null;

    /**
     * @var string|resource|null
     */
    #[ORM\Column(name: 'payload_compressed', type: Types::BLOB, length: MySQLPlatform::LENGTH_LIMIT_MEDIUMBLOB, nullable: true)]
    private $payloadCompressed;

    #[ORM\ManyToOne(targetEntity: Event::class, inversedBy: 'queues')]
    #[ORM\JoinColumn(name: 'event_id', nullable: false, onDelete: 'CASCADE')]
    private ?Event $event = null;

    #[ORM\Column(type: Types::SMALLINT, options: ['unsigned' => true, 'default' => 0])]
    private int $retries = 0;

    /**
     * The id is a bigint, so it comes back as int within PHP's integer range and as
     * string beyond it - the union DBAL's BigIntType itself returns. DBAL 3 always gave
     * a string; normalising back to one here would hide which of the two it is.
     */
    public function getId(): int|string|null
    {
        return $this->id;
    }

    public function getWebhook(): ?Webhook
    {
        return $this->webhook;
    }

    public function setWebhook(?Webhook $webhook): static
    {
        $this->webhook = $webhook;

        return $this;
    }

    public function getDateAdded(): ?\DateTime
    {
        return $this->dateAdded;
    }

    public function setDateAdded(?\DateTime $dateAdded): static
    {
        $this->dateAdded = $dateAdded;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPayload(): string|false|null
    {
        if (null === $this->payloadCompressed) {
            // no payload is set
            return null;
        }

        $payloadCompressed = $this->payloadCompressed;

        if (is_resource($payloadCompressed)) {
            // compressed payload is fetched by ORM
            $payloadCompressed = stream_get_contents($this->payloadCompressed);
        }

        return gzuncompress($payloadCompressed);
    }

    /**
     * @param string $payload
     */
    public function setPayload($payload): static
    {
        $this->payloadCompressed = gzcompress($payload, 9);

        return $this;
    }

    public function getEvent(): ?Event
    {
        return $this->event;
    }

    public function setEvent(?Event $event): static
    {
        $this->event = $event;

        return $this;
    }

    public function getRetries(): int
    {
        return $this->retries;
    }

    public function setRetries(int $retries): self
    {
        $this->retries = $retries;

        return $this;
    }

    public function getDateModified(): ?\DateTimeImmutable
    {
        return $this->dateModified;
    }

    public function setDateModified(?\DateTimeImmutable $dateModified): self
    {
        $this->dateModified = $dateModified;

        return $this;
    }
}
