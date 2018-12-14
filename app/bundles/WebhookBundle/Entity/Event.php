<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\WebhookBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

/**
 * Class Event.
 */
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
     * @var ArrayCollection
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

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata(ORM\ClassMetadata $metadata)
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
     *
     * @param $metadata
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata)
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
     * @param Webhook $webhook
     *
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
}
