<?php

declare(strict_types=1);

namespace Mautic\WebhookBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

#[ORM\Entity(repositoryClass: EventRepository::class)]
#[ORM\Table(name: 'webhook_events')]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class Event
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var Webhook
     */
    #[ORM\ManyToOne(targetEntity: Webhook::class, cascade: ['detach', 'merge'], inversedBy: 'events')]
    #[ORM\JoinColumn(name: 'webhook_id', nullable: false, onDelete: 'CASCADE')]
    private $webhook;

    /**
     * @var ArrayCollection<int, WebhookQueue>
     */
    #[ORM\OneToMany(mappedBy: 'event', targetEntity: WebhookQueue::class, cascade: ['detach', 'merge'], fetch: 'EXTRA_LAZY')]
    private $queues;

    /**
     * @var string
     */
    #[ORM\Column(name: 'event_type', type: 'string', length: 50)]
    private $eventType;

    public function __construct()
    {
        $this->queues = new ArrayCollection();
    }

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->addId();
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

    public function setWebhook(Webhook $webhook): static
    {
        $this->webhook = $webhook;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getEventType()
    {
        return $this->eventType;
    }

    /**
     * @param mixed $eventType
     */
    public function setEventType($eventType): static
    {
        $this->eventType = $eventType;

        return $this;
    }

    /**
     * @param ArrayCollection $queues
     */
    public function setQueues($queues): static
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
