<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
namespace Mautic\WebhookBundle\Entity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Entity\FormEntity;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotBlank;
use Mautic\WebhookBundle\Entity\Event;
/**
 * Class Webhook
 *
 * @package Mautic\WebhookBundle\Entity
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

    private $removedEvents = array();
    /*
     * Constructor
     */
    public function __construct() {
        $this->events  = new ArrayCollection();
        $this->queues  = new ArrayCollection();
        $this->logs    = new ArrayCollection();
    }
    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata (ORM\ClassMetadata $metadata)
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
        $builder->createOneToMany('logs', 'Log')->setOrderBy(array('dateAdded' => 'DESC'))
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
        $metadata->addPropertyConstraint('name', new NotBlank(array(
            'message' => 'mautic.core.name.required'
        )));
        $metadata->addPropertyConstraint('webhookUrl',  new Assert\Url(
                array(
                    'message' => 'mautic.core.value.required',
                    'groups'  => array('webhookUrl')
                )
            )
        );
    }
    /**
     * @param \Symfony\Component\Form\Form $form
     *
     * @return array
     */
    public static function determineValidationGroups(\Symfony\Component\Form\Form $form)
    {
        $data   = $form->getData();
        $groups = array('Webhook');
        $webhookUrl = $data->getWebhookUrl();
        if ($webhookUrl) {
            $groups[] = 'webhookUrl';
        }
        return $groups;
    }
    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }
    /**
     * Set name
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
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
    /**
     * Set description
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
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }
    /**
     * Set webhookUrl
     *
     * @param string $webhookUrl
     *
     * @return Webhook
     */
    public function setWebhookUrl($webhookUrl) {
        $this->isChanged('webhookUrl', $webhookUrl);
        $this->webhookUrl = $webhookUrl;
        return $this;
    }
    /**
     * Get webhookUrl
     *
     * @return string
     */
    public function getWebhookUrl() {
        return $this->webhookUrl;
    }
    /**
     * Set category
     *
     * @param \Mautic\CategoryBundle\Entity\Category $category
     *
     * @return Webhook
     */
    public function setCategory(\Mautic\CategoryBundle\Entity\Category $category = null)
    {
        $this->isChanged('category', $category);
        $this->category = $category;
        return $this;
    }
    /**
     * Get category
     *
     * @return \Mautic\CategoryBundle\Entity\Category
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
     * @param mixed $events
     */
    public function setEvents($events)
    {
        $this->isChanged('events', $events);
        $this->events = $events;
        /**  @var \Mautic\WebhookBundle\Entity\Event $event */
        foreach ($events as $event) {
            $event->setWebhook($this);
        }
        return $this;
    }
    public function addEvent(Event $event)
    {
        $this->isChanged('events', $event);
        $this->events[] = $event;

        return $this;
    }

    public function removeEvent(Event $event)
    {
        $this->isChanged('events', $event);
        $this->removedEvents[] = $event;
        $this->events->removeElement($event);

        return $this;
    }
    public function getQueues()
    {
        return $this->queues;
    }
    /**
     * @param mixed $events
     */
    public function addQueues($queues)
    {
        $this->queues = $queues;
        /**  @var \Mautic\WebhookBundle\Entity\WebhookQueue $queue */
        foreach ($queues as $queue) {
            $queue->setWebhook($this);
        }

        return $this;
    }
    public function addQueue(WebhookQueue $queue)
    {
        $this->queues[] = $queue;

        return $this;
    }
    public function removeQueue(WebhookQueue $queue)
    {
        $this->queues->removeElement($queue);

        return $this;
    }
    /*
     * Get log entities
     */
    public function getLogs()
    {
        return $this->logs;
    }
    /**
     * @param mixed $events
     */
    public function addLogs($logs)
    {
        $this->logs = $logs;
        /**  @var \Mautic\WebhookBundle\Entity\Log $log */
        foreach ($logs as $log) {
            $log->setWebhook($this);
        }

        return $this;
    }
    public function addLog(Log $log)
    {
        $this->logs[] = $log;

        return $this;
    }
    public function removeLog(Log $log)
    {
        $this->logs->removeElement($log);

        return $this;
    }

    /**
     * @param string $prop
     * @param mixed  $val
     */
    protected function isChanged($prop, $val)
    {
        $getter  = "get" . ucfirst($prop);
        $current = $this->$getter();
        if ($prop == 'category') {
            $currentId = ($current) ? $current->getId() : '';
            $newId     = ($val) ? $val->getId() : null;
            if ($currentId != $newId) {
                $this->changes[$prop] = array($currentId, $newId);
            }
        } elseif ($prop == 'events') {
            $this->changes[$prop] = array();
        } elseif ($current != $val) {
            $this->changes[$prop] = array($current, $val);
        }
    }
}