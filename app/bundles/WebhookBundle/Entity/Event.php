<?php

namespace Mautic\WebhookBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

class Event
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var Webhook
     */
    private $webhook;

    /**
     * @var ArrayCollection<int, WebhookQueue>
     */
    private $queues;

    /**
     * @var string
     */
    private $eventType;

    public function __construct()
    {
        $this->queues = new ArrayCollection();
    }

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);
        $builder->setTable('webhook_events')
            ->setCustomRepositoryClass(EventRepository::class);

        $builder->addId();

        $builder->createManyToOne('webhook', 'Webhook')
            ->inversedBy('events')
            ->cascadeDetach()
            ->cascadeMerge()
            ->addJoinColumn('webhook_id', 'id', false, false, 'CASCADE')
            ->build();

        $builder->createOneToMany('queues', 'WebhookQueue')
            ->mappedBy('event')
            ->cascadeDetach()
            ->cascadeMerge()
            ->fetchExtraLazy()
            ->build();

        $builder->createField('eventType', 'string')
            ->columnName('event_type')
            ->length(50)
            ->build();
    }

    /**
     * Prepares the metadata for API usage.
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata): void
    {
        $metadata->setGroupPrefix('event')
            ->addListProperties(
                [
                    'eventType',
                ]
            )
            ->build();
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Webhook
     */
    public function getWebhook()
    {
        return $this->webhook;
    }

    /**
     * @return $this
     */
    public function setWebhook(Webhook $webhook)
    {
        $this->webhook = $webhook;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getEventType()
    {
        return $this->eventType;
    }

    /**
     * @param mixed $eventType
     */
    public function setEventType($eventType)
    {
        $this->eventType = $eventType;

        return $this;
    }

    /**
     * @param ArrayCollection $queues
     *
     * @return self
     */
    public function setQueues($queues)
    {
        $this->queues = $queues;

        return $this;
    }

    /**
     * @return ArrayCollection
     */
    public function getQueues()
    {
        return $this->queues;
    }
}
