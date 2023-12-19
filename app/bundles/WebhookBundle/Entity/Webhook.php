<?php

namespace Mautic\WebhookBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CategoryBundle\Entity\Category;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\FormEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Mapping\ClassMetadata;

class Webhook extends FormEntity
{
    public const LOGS_DISPLAY_LIMIT = 100;

    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string|null
     */
    private $description;

    /**
     * @var string
     */
    private $webhookUrl;

    /**
     * @var string
     */
    private $secret;

    /**
     * @var \Mautic\CategoryBundle\Entity\Category|null
     **/
    private $category;

    /**
     * @var ArrayCollection<int, \Mautic\WebhookBundle\Entity\Event>
     */
    private $events;

    /**
     * @var ArrayCollection<int, \Mautic\WebhookBundle\Entity\Log>
     */
    private $logs;

    /**
     * @var array
     */
    private $removedEvents = [];

    /**
     * @var array
     */
    private $payload;

    /**
     * Holds a simplified array of events, just an array of event types.
     * It's used for API serializaiton.
     *
     * @var array
     */
    private $triggers = [];

    /**
     * ASC or DESC order for fetching order of the events when queue mode is on.
     * Null means use the global default.
     *
     * @var string|null
     */
    private $eventsOrderbyDir;

    public function __construct()
    {
        $this->events = new ArrayCollection();
        $this->logs   = new ArrayCollection();
    }

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);
        $builder->setTable('webhooks')
            ->setCustomRepositoryClass(WebhookRepository::class);

        $builder->addIdColumns();

        $builder->addCategory();

        $builder->createOneToMany('events', 'Event')
            ->orphanRemoval()
            ->setIndexBy('event_type')
            ->mappedBy('webhook')
            ->cascadePersist()
            ->cascadeMerge()
            ->cascadeDetach()
            ->build();

        $builder->createOneToMany('logs', 'Log')->setOrderBy(['dateAdded' => Criteria::DESC])
            ->fetchExtraLazy()
            ->mappedBy('webhook')
            ->cascadePersist()
            ->cascadeMerge()
            ->cascadeDetach()
            ->build();

        $builder->addNamedField('webhookUrl', Types::TEXT, 'webhook_url');
        $builder->addField('secret', Types::STRING);
        $builder->addNullableField('eventsOrderbyDir', Types::STRING, 'events_orderby_dir');
    }

    /**
     * Prepares the metadata for API usage.
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata): void
    {
        $metadata->setGroupPrefix('hook')
            ->addListProperties(
                [
                    'id',
                    'name',
                    'description',
                    'webhookUrl',
                    'secret',
                    'eventsOrderbyDir',
                    'category',
                    'triggers',
                ]
            )
            ->build();
    }

    public static function loadValidatorMetadata(ClassMetadata $metadata): void
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

        $metadata->addPropertyConstraint(
            'eventsOrderbyDir',
            new Assert\Choice(
                [
                    null,
                    Criteria::ASC,
                    Criteria::DESC,
                ]
            )
        );
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
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
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
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
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
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
     * @return string
     */
    public function getWebhookUrl()
    {
        return $this->webhookUrl;
    }

    /**
     * @param string $secret
     *
     * @return Webhook
     */
    public function setSecret($secret)
    {
        $this->isChanged('secret', $secret);
        $this->secret = $secret;

        return $this;
    }

    /**
     * @return string
     */
    public function getSecret()
    {
        return $this->secret;
    }

    /**
     * @return Webhook
     */
    public function setCategory(Category $category = null)
    {
        $this->isChanged('category', $category);
        $this->category = $category;

        return $this;
    }

    /**
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
     * This builds a simple array with subscribed events.
     */
    public function buildTriggers(): void
    {
        foreach ($this->events as $event) {
            $this->triggers[] = $event->getEventType();
        }
    }

    /**
     * Takes the array of triggers and builds events from them if they don't exist already.
     */
    public function setTriggers(array $triggers): void
    {
        foreach ($triggers as $key) {
            $this->addTrigger($key);
        }
    }

    /**
     * Takes a trigger (event type) and builds the Event object form it if it doesn't exist already.
     *
     * @param string $key
     */
    public function addTrigger($key): bool
    {
        if ($this->eventExists($key)) {
            return false;
        }

        $event = new Event();
        $event->setEventType($key);
        $event->setWebhook($this);
        $this->addEvent($event);

        return true;
    }

    /**
     * Check if an event exists comared to its type.
     *
     * @param string $key
     */
    public function eventExists($key): bool
    {
        foreach ($this->events as $event) {
            if ($event->getEventType() === $key) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return $this
     */
    public function addEvent(Event $event)
    {
        $this->isChanged('events', $event);

        $this->events[] = $event;

        return $this;
    }

    /**
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
     * @param string $eventsOrderbyDir
     */
    public function setEventsOrderbyDir($eventsOrderbyDir)
    {
        $this->isChanged('eventsOrderbyDir', $eventsOrderbyDir);
        $this->eventsOrderbyDir = $eventsOrderbyDir;

        return $this;
    }

    /**
     * @return string
     */
    public function getEventsOrderbyDir()
    {
        return $this->eventsOrderbyDir;
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
     * @return Collection<int, \Mautic\WebhookBundle\Entity\Log>
     */
    public function getLimitedLogs(): Collection
    {
        $criteria = Criteria::create()
            ->setMaxResults(self::LOGS_DISPLAY_LIMIT);

        return $this->logs->matching($criteria);
    }

    /**
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
     * @return $this
     */
    public function addLog(Log $log)
    {
        $this->logs[] = $log;

        return $this;
    }

    /**
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
     * @return Webhook
     */
    public function setPayload($payload)
    {
        $this->payload = $payload;

        return $this;
    }

    public function wasModifiedRecently(): bool
    {
        $dateModified = $this->getDateModified();

        if (null === $dateModified) {
            return false;
        }

        $aWhileBack = (new \DateTime())->modify('-2 days');

        if ($dateModified < $aWhileBack) {
            return false;
        }

        return true;
    }

    /**
     * @param string $prop
     */
    protected function isChanged($prop, $val)
    {
        $getter  = 'get'.ucfirst($prop);
        $current = $this->$getter();
        if ('category' == $prop) {
            $currentId = ($current) ? $current->getId() : '';
            $newId     = ($val) ? $val->getId() : null;
            if ($currentId != $newId) {
                $this->changes[$prop] = [$currentId, $newId];
            }
        } elseif ('events' == $prop) {
            $this->changes[$prop] = [];
        } elseif ($current != $val) {
            $this->changes[$prop] = [$current, $val];
        } else {
            parent::isChanged($prop, $val);
        }
    }
}
