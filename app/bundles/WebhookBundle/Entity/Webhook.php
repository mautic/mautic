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
use Mautic\CategoryBundle\Entity\Category;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\FormEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * Class Webhook.
 */
class Webhook extends FormEntity
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $description;

    /**
     * @var string
     */
    private $webhookUrl;

    /**
     * @var \Mautic\CategoryBundle\Entity\Category
     **/
    private $category;

    /**
     * @var ArrayCollection
     */
    private $events;

    /**
     * @var ArrayCollection
     */
    private $queues;

    /**
     * @var ArrayCollection
     */
    private $logs;

    /**
     * @var array
     */
    private $removedEvents = [];

    /**
     * @var
     */
    private $payload;

    /*
     * Constructor
     */
    public function __construct()
    {
        $this->events = new ArrayCollection();
        $this->queues = new ArrayCollection();
        $this->logs   = new ArrayCollection();
    }

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);
        $builder->setTable('webhooks')
            ->setCustomRepositoryClass('Mautic\WebhookBundle\Entity\WebhookRepository');
        // id columns
        $builder->addIdColumns();
        // categories
        $builder->addCategory();
        // 1:M for events
        $builder->createOneToMany('events', 'Event')
            ->orphanRemoval()
            ->setIndexBy('event_type')
            ->mappedBy('webhook')
            ->cascadePersist()
            ->build();
        // 1:M for queues
        $builder->createOneToMany('queues', 'WebhookQueue')
            ->mappedBy('webhook')
            ->fetchExtraLazy()
            ->cascadePersist()
            ->build();
        // 1:M for logs
        $builder->createOneToMany('logs', 'Log')->setOrderBy(['dateAdded' => 'DESC'])
            ->fetchExtraLazy()
            ->mappedBy('webhook')
            ->cascadePersist()
            ->build();

        // status code
        $builder->createField('webhookUrl', 'string')
            ->columnName('webhook_url')
            ->length(255)
            ->build();
    }

    /**
     * @param ClassMetadata $metadata
     */
    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraint(
            'name',
            new NotBlank(
                [
                    'message' => 'mautic.core.name.required',
                ]
            )
        );

        $metadata->addPropertyConstraint(
            'webhookUrl',
            new Assert\Url(
                [
                    'message' => 'mautic.core.valid_url_required',
                ]
            )
        );

        $metadata->addPropertyConstraint(
            'webhookUrl',
            new Assert\NotBlank(
                [
                    'message' => 'mautic.core.valid_url_required',
                ]
            )
        );
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
     * Set name.
     *
     * @param string $name
     *
     * @return Webhook
     */
    public function setName($name)
    {
        $this->isChanged('name', $name);
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set description.
     *
     * @param string $description
     *
     * @return Webhook
     */
    public function setDescription($description)
    {
        $this->isChanged('description', $description);
        $this->description = $description;

        return $this;
    }

    /**
     * Get description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set webhookUrl.
     *
     * @param string $webhookUrl
     *
     * @return Webhook
     */
    public function setWebhookUrl($webhookUrl)
    {
        $this->isChanged('webhookUrl', $webhookUrl);
        $this->webhookUrl = $webhookUrl;

        return $this;
    }

    /**
     * Get webhookUrl.
     *
     * @return string
     */
    public function getWebhookUrl()
    {
        return $this->webhookUrl;
    }

    /**
     * Set category.
     *
     * @param Category $category
     *
     * @return Webhook
     */
    public function setCategory(Category $category = null)
    {
        $this->isChanged('category', $category);
        $this->category = $category;

        return $this;
    }

    /**
     * Get category.
     *
     * @return Category
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @return mixed
     */
    public function getEvents()
    {
        return $this->events;
    }

    /**
     * @param $events
     *
     * @return $this
     */
    public function setEvents($events)
    {
        $this->isChanged('events', $events);

        $this->events = $events;
        /** @var \Mautic\WebhookBundle\Entity\Event $event */
        foreach ($events as $event) {
            $event->setWebhook($this);
        }

        return $this;
    }

    /**
     * @param \Mautic\WebhookBundle\Entity\Event $event
     *
     * @return $this
     */
    public function addEvent(Event $event)
    {
        $this->isChanged('events', $event);

        $this->events[] = $event;

        return $this;
    }

    /**
     * @param \Mautic\WebhookBundle\Entity\Event $event
     *
     * @return $this
     */
    public function removeEvent(Event $event)
    {
        $this->isChanged('events', $event);
        $this->removedEvents[] = $event;
        $this->events->removeElement($event);

        return $this;
    }

    /**
     * @return ArrayCollection
     */
    public function getQueues()
    {
        return $this->queues;
    }

    /**
     * @param $queues
     *
     * @return $this
     */
    public function addQueues($queues)
    {
        $this->queues = $queues;

        /** @var \Mautic\WebhookBundle\Entity\WebhookQueue $queue */
        foreach ($queues as $queue) {
            $queue->setWebhook($this);
        }

        return $this;
    }

    /**
     * @param WebhookQueue $queue
     *
     * @return $this
     */
    public function addQueue(WebhookQueue $queue)
    {
        $this->queues[] = $queue;

        return $this;
    }

    /**
     * @param WebhookQueue $queue
     *
     * @return $this
     */
    public function removeQueue(WebhookQueue $queue)
    {
        $this->queues->removeElement($queue);

        return $this;
    }

    /**
     * Get log entities.
     *
     * @return ArrayCollection
     */
    public function getLogs()
    {
        return $this->logs;
    }

    /**
     * @param $logs
     *
     * @return $this
     */
    public function addLogs($logs)
    {
        $this->logs = $logs;

        /** @var \Mautic\WebhookBundle\Entity\Log $log */
        foreach ($logs as $log) {
            $log->setWebhook($this);
        }

        return $this;
    }

    /**
     * @param Log $log
     *
     * @return $this
     */
    public function addLog(Log $log)
    {
        $this->logs[] = $log;

        return $this;
    }

    /**
     * @param Log $log
     *
     * @return $this
     */
    public function removeLog(Log $log)
    {
        $this->logs->removeElement($log);

        return $this;
    }

    /**
     * @return array
     */
    public function getPayload()
    {
        return $this->payload;
    }

    /**
     * @param mixed $payload
     *
     * @return Webhook
     */
    public function setPayload($payload)
    {
        $this->payload = $payload;

        return $this;
    }

    /**
     * @param string $prop
     * @param mixed  $val
     */
    protected function isChanged($prop, $val)
    {
        $getter  = 'get'.ucfirst($prop);
        $current = $this->$getter();
        if ($prop == 'category') {
            $currentId = ($current) ? $current->getId() : '';
            $newId     = ($val) ? $val->getId() : null;
            if ($currentId != $newId) {
                $this->changes[$prop] = [$currentId, $newId];
            }
        } elseif ($prop == 'events') {
            $this->changes[$prop] = [];
        } else {
            parent::isChanged($prop, $val);
        }
    }
}
