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
    private $event_type;
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
            ->setCustomRepositoryClass('Mautic\WebhookBundle\Entity\EventRepository');
        // id columns
        $builder->addId();
        // M:1 for webhook
        $builder->createManyToOne('webhook', 'Webhook')
            ->inversedBy('events')
            ->cascadeDetach()
            ->cascadeMerge()
            ->addJoinColumn('webhook_id', 'id', false, false, 'CASCADE')
            ->build();
        // 1:M for queues
        $builder->createOneToMany('queues', 'WebhookQueue')
            ->mappedBy('event')
            ->cascadeDetach()
            ->cascadeMerge()
            ->fetchExtraLazy()
            ->build();
        // event type field
        $builder->createField('event_type', 'string')
            ->columnName('event_type')
            ->length(50)
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
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }
    /**
     * @return mixed
     */
    public function getWebhook()
    {
        return $this->webhook;
    }
    /**
     * @param mixed $webhook
     */
    public function setWebhook($webhook)
    {
        $this->webhook = $webhook;

        return $this;
    }
    /**
     * @return mixed
     */
    public function getEventType()
    {
        return $this->event_type;
    }
    /**
     * @param mixed $event_type
     */
    public function setEventType($event_type)
    {
        $this->event_type = $event_type;

        return $this;
    }
}
