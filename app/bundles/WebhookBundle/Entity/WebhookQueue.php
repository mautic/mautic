<?php

namespace Mautic\WebhookBundle\Entity;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

class WebhookQueue
{
    /**
     * @var int|null
     */
    private $id;

    /**
     * @var Webhook
     */
    private $webhook;

    /**
     * @var \DateTimeInterface|null
     */
    private $dateAdded;

    /**
     * @var string|null
     */
    private $payload; // @phpstan-ignore-line (BC: plain payload is fetched by ORM)

    /**
     * @var string|resource|null
     */
    private $payloadCompressed;

    /**
     * @var Event
     **/
    private $event;

    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);
        $builder->setTable('webhook_queue')
            ->setCustomRepositoryClass(WebhookQueueRepository::class);
        $builder->addId();
        $builder->createManyToOne('webhook', 'Webhook')
            ->addJoinColumn('webhook_id', 'id', false, false, 'CASCADE')
            ->build();
        $builder->addNullableField('dateAdded', Types::DATETIME_MUTABLE, 'date_added');
        $builder->addNullableField('payload', Types::TEXT);
        $builder->createField('payloadCompressed', Types::BLOB)
            ->columnName('payload_compressed')
            ->nullable()
            ->length(MySQLPlatform::LENGTH_LIMIT_MEDIUMBLOB)
            ->build();
        $builder->createManyToOne('event', 'Event')
            ->inversedBy('queues')
            ->addJoinColumn('event_id', 'id', false, false, 'CASCADE')
            ->build();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getWebhook(): ?Webhook
    {
        return $this->webhook;
    }

    /**
     * @return WebhookQueue
     */
    public function setWebhook(?Webhook $webhook)
    {
        $this->webhook = $webhook;

        return $this;
    }

    public function getDateAdded(): ?\DateTimeInterface
    {
        return $this->dateAdded;
    }

    /**
     * @return WebhookQueue
     */
    public function setDateAdded(?\DateTime $dateAdded)
    {
        $this->dateAdded = $dateAdded;

        return $this;
    }

    public function getPayload(): ?string
    {
        if (null !== $this->payload) {
            // BC: plain payload is fetched by ORM
            return $this->payload;
        }

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
     *
     * @return WebhookQueue
     */
    public function setPayload($payload)
    {
        $this->payloadCompressed = gzcompress($payload, 9);

        return $this;
    }

    public function getEvent(): ?Event
    {
        return $this->event;
    }

    /**
     * @return WebhookQueue
     */
    public function setEvent(?Event $event)
    {
        $this->event = $event;

        return $this;
    }
}
