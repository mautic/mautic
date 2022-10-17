<?php

namespace Mautic\WebhookBundle\Entity;

use DateTime;
use Doctrine\DBAL\Platforms\MySqlPlatform;
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
     * @var Webhook|null
     */
    private $webhook;

    /**
     * @var DateTime|null
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
     * @var Event|null
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
            ->length(MySqlPlatform::LENGTH_LIMIT_MEDIUMBLOB)
            ->build();
        $builder->createManyToOne('event', 'Event')
            ->inversedBy('queues')
            ->addJoinColumn('event_id', 'id', false, false, 'CASCADE')
            ->build();
    }

    /**
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Webhook|null
     */
    public function getWebhook()
    {
        return $this->webhook;
    }

    /**
     * @param Webhook|null $webhook
     *
     * @return WebhookQueue
     */
    public function setWebhook($webhook)
    {
        $this->webhook = $webhook;

        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getDateAdded()
    {
        return $this->dateAdded;
    }

    /**
     * @param DateTime|null $dateAdded
     *
     * @return WebhookQueue
     */
    public function setDateAdded($dateAdded)
    {
        $this->dateAdded = $dateAdded;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPayload()
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

    /**
     * @return Event|null
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * @param Event|null $event
     *
     * @return WebhookQueue
     */
    public function setEvent($event)
    {
        $this->event = $event;

        return $this;
    }
}
