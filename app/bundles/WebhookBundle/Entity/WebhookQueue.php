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

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

/**
 * Class WebhookQueue.
 */
class WebhookQueue
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
     * @var \DateTime
     */
    private $dateAdded;

    /**
     * @var string
     */
    private $payload;

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
        $builder->addNullableField('dateAdded', Type::DATETIME, 'date_added');
        $builder->addField('payload', Type::TEXT);
        $builder->createManyToOne('event', 'Event')
            ->inversedBy('queues')
            ->addJoinColumn('event_id', 'id', false, false, 'CASCADE')
            ->build();
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
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
    public function getDateAdded()
    {
        return $this->dateAdded;
    }

    /**
     * @param mixed $dateAdded
     */
    public function setDateAdded($dateAdded)
    {
        $this->dateAdded = $dateAdded;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPayload()
    {
        return $this->payload;
    }

    /**
     * @param mixed $payload
     */
    public function setPayload($payload)
    {
        $this->payload = $payload;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * @param mixed $event
     */
    public function setEvent($event)
    {
        $this->event = $event;

        return $this;
    }
}
