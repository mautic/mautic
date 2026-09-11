<?php

declare(strict_types=1);

namespace Mautic\WebhookBundle\Entity;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

#[ORM\Entity(repositoryClass: WebhookQueueRepository::class)]
#[ORM\Table(name: self::TABLE_NAME)]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class WebhookQueue
{
    public const TABLE_NAME = 'webhook_queue';

    private ?string $id = null;

    #[ORM\ManyToOne(targetEntity: Webhook::class)]
    #[ORM\JoinColumn(name: 'webhook_id', nullable: false, onDelete: 'CASCADE')]
    private ?Webhook $webhook = null;

    private ?\DateTime $dateAdded = null;

    private ?\DateTimeImmutable $dateModified = null;

    /**
     * @var string|resource|null
     */
    private $payloadCompressed;

    #[ORM\ManyToOne(targetEntity: Event::class, inversedBy: 'queues')]
    #[ORM\JoinColumn(name: 'event_id', nullable: false, onDelete: 'CASCADE')]
    private ?Event $event = null;

    #[ORM\Column(type: Types::SMALLINT, options: ['unsigned' => true, 'default' => 0])]
    private int $retries = 0;

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);
        $builder->addBigIntIdField();
        $builder->addNullableField('dateAdded', Types::DATETIME_MUTABLE, 'date_added');
        $builder->addNullableField('dateModified', Types::DATETIME_IMMUTABLE, 'date_modified');
        $builder->createField('payloadCompressed', Types::BLOB)
            ->columnName('payload_compressed')
            ->nullable()
            ->length(MySQLPlatform::LENGTH_LIMIT_MEDIUMBLOB)
            ->build();
    }

    public function getId(): ?string
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
