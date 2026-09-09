<?php

namespace Mautic\WebhookBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Order;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CategoryBundle\Entity\Category;
use Mautic\CoreBundle\Entity\FormEntity;
use Mautic\CoreBundle\Entity\SkipModifiedInterface;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\NotBlank;

#[ApiResource(
    shortName: 'Webhooks',
    operations: [
        new GetCollection(uriTemplate: '/webhooks', security: "is_granted('webhook:webhooks:viewown')"),
        new Post(uriTemplate: '/webhooks', security: "is_granted('webhook:webhooks:create')"),
        new Get(uriTemplate: '/webhooks/{id}', security: "is_granted('webhook:webhooks:viewown', object)"),
        new Put(uriTemplate: '/webhooks/{id}', security: "is_granted('webhook:webhooks:editown', object)"),
        new Patch(uriTemplate: '/webhooks/{id}', security: "is_granted('webhook:webhooks:editother', object)"),
        new Delete(uriTemplate: '/webhooks/{id}', security: "is_granted('webhook:webhooks:deleteown', object)"),
    ],
    normalizationContext: [
        'groups'                  => ['webhook:read'],
        'swagger_definition_name' => 'Read',
        'api_included'            => ['category'],
    ],
    denormalizationContext: [
        'groups'                  => ['webhook:write'],
        'swagger_definition_name' => 'Write',
    ]
)]
#[ORM\Entity(repositoryClass: WebhookRepository::class)]
#[ORM\Table(name: 'webhooks')]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class Webhook extends FormEntity implements SkipModifiedInterface
{
    public const LOGS_DISPLAY_LIMIT = 100;

    /**
     * @var ?int
     */
    #[Groups(['webhook:read'])]
    #[ORM\Id]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    #[ORM\GeneratedValue]
    private $id;

    /**
     * @var ?string
     */
    #[Groups(['webhook:read', 'webhook:write'])]
    #[NotBlank(message: 'mautic.core.name.required')]
    #[ORM\Column(type: 'string', length: 191)]
    private $name;

    /**
     * @var string|null
     */
    #[Groups(['webhook:read', 'webhook:write'])]
    #[ORM\Column(type: 'text', nullable: true)]
    private $description;

    /**
     * @var ?string
     */
    #[Groups(['webhook:read', 'webhook:write'])]
    #[NotBlank(message: 'mautic.core.valid_url_required')]
    #[Assert\Url(message: 'mautic.core.valid_url_required')]
    #[ORM\Column(name: 'webhook_url', type: Types::TEXT)]
    private $webhookUrl;

    /**
     * @var ?string
     */
    #[Groups(['webhook:read', 'webhook:write'])]
    #[ORM\Column(type: Types::STRING, length: 191)]
    private $secret;

    #[Groups(['webhook:read', 'webhook:write'])]
    #[ORM\ManyToOne(targetEntity: \Mautic\CategoryBundle\Entity\Category::class, cascade: ['merge', 'detach'])]
    #[ORM\JoinColumn(name: 'category_id', onDelete: 'SET NULL')]
    private ?\Mautic\CategoryBundle\Entity\Category $category = null;

    /**
     * @var Collection<int, Event>
     */
    #[Groups(['webhook:read', 'webhook:write'])]
    #[ORM\OneToMany(mappedBy: 'webhook', targetEntity: 'Event', cascade: ['persist', 'merge', 'detach'], orphanRemoval: true, indexBy: 'eventType')]
    private $events;

    /**
     * @var ArrayCollection<int, Log>
     */
    #[ORM\OneToMany(mappedBy: 'webhook', targetEntity: 'Log', cascade: ['persist', 'merge', 'detach'], fetch: 'EXTRA_LAZY')]
    #[ORM\OrderBy(['dateAdded' => Order::Descending->value])]
    private $logs;

    /**
     * @var Event[]
     */
    private array $removedEvents = [];

    /**
     * @var mixed[]
     */
    #[Groups(['webhook:read', 'webhook:write'])]
    private $payload;

    /**
     * Holds a simplified array of events, just an array of event types.
     * It's used for API serializaiton.
     *
     * @var string[]|null[]
     */
    #[Groups(['webhook:read', 'webhook:write'])]
    private array $triggers = [];

    /**
     * ASC or DESC order for fetching order of the events when queue mode is on.
     * Null means use the global default.
     *
     * @var string|null
     */
    #[Groups(['webhook:read', 'webhook:write'])]
    #[Assert\Choice([
        null,
        Order::Ascending->value,
        Order::Descending->value,
    ])]
    #[ORM\Column(name: 'events_orderby_dir', type: Types::STRING, length: 191, nullable: true)]
    private $eventsOrderbyDir;

    #[ORM\Column(name: 'marked_unhealthy_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $markedUnhealthyAt      = null;

    #[ORM\Column(name: 'unhealthy_since', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $unHealthySince         = null;

    #[ORM\Column(name: 'last_notification_sent_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $lastNotificationSentAt = null;

    public function __construct()
    {
        $this->events = new ArrayCollection();
        $this->logs   = new ArrayCollection();
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

    /**
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $name
     */
    public function setName($name): static
    {
        $this->isChanged('name', $name);
        $this->name = $name;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $description
     */
    public function setDescription($description): static
    {
        $this->isChanged('description', $description);
        $this->description = $description;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $webhookUrl
     */
    public function setWebhookUrl($webhookUrl): static
    {
        $this->isChanged('webhookUrl', $webhookUrl);
        $this->webhookUrl = $webhookUrl;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getWebhookUrl()
    {
        return $this->webhookUrl;
    }

    /**
     * @param ?string $secret
     */
    public function setSecret($secret): static
    {
        $this->isChanged('secret', $secret);
        $this->secret = $secret;

        return $this;
    }

    /**
     * @return ?string
     */
    public function getSecret()
    {
        return $this->secret;
    }

    public function setCategory(?Category $category = null): static
    {
        $this->isChanged('category', $category);
        $this->category = $category;

        return $this;
    }

    public function getCategory(): ?\Mautic\CategoryBundle\Entity\Category
    {
        return $this->category;
    }

    /**
     * @return Collection<int, Event>
     */
    public function getEvents()
    {
        return $this->events;
    }

    /**
     * @param Collection<int, Event> $events
     */
    public function setEvents($events): static
    {
        $this->isChanged('events', $events);

        $this->events = $events;

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

    public function addEvent(Event $event): static
    {
        $this->isChanged('events', $event);

        $this->events[] = $event;

        return $this;
    }

    public function removeEvent(Event $event): static
    {
        $this->isChanged('events', $event);
        $this->removedEvents[] = $event;
        $this->events->removeElement($event);

        return $this;
    }

    /**
     * @param string $eventsOrderbyDir
     */
    public function setEventsOrderbyDir($eventsOrderbyDir): static
    {
        $this->isChanged('eventsOrderbyDir', $eventsOrderbyDir);
        $this->eventsOrderbyDir = $eventsOrderbyDir;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getEventsOrderbyDir()
    {
        return $this->eventsOrderbyDir;
    }

    /**
     * Get log entities.
     *
     * @return ArrayCollection<int,Log>
     */
    public function getLogs()
    {
        return $this->logs;
    }

    /**
     * @return Collection<int,Log>
     */
    public function getLimitedLogs(): Collection
    {
        $criteria = Criteria::create()
            ->setMaxResults(self::LOGS_DISPLAY_LIMIT);

        return $this->logs->matching($criteria);
    }

    /**
     * @param ArrayCollection<int,Log> $logs
     */
    public function addLogs($logs): static
    {
        $this->logs = $logs;

        /** @var Log $log */
        foreach ($logs as $log) {
            $log->setWebhook($this);
        }

        return $this;
    }

    public function addLog(Log $log): static
    {
        $this->logs[] = $log;

        return $this;
    }

    public function removeLog(Log $log): static
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

    public function setPayload($payload): static
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

        $aWhileBack = new \DateTime()->modify('-2 days');

        return $dateModified >= $aWhileBack;
    }

    /**
     * @param string $prop
     */
    protected function isChanged($prop, $val): void
    {
        $getter  = 'get'.ucfirst($prop);
        $current = $this->{$getter}();
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

    public function getMarkedUnhealthyAt(): ?\DateTimeImmutable
    {
        return $this->markedUnhealthyAt;
    }

    public function setMarkedUnhealthyAt(?\DateTimeImmutable $markedUnhealthyAt): self
    {
        $this->isChanged('markedUnhealthyAt', $markedUnhealthyAt);
        $this->markedUnhealthyAt = $markedUnhealthyAt;

        return $this;
    }

    public function getUnHealthySince(): ?\DateTimeImmutable
    {
        return $this->unHealthySince;
    }

    public function setUnHealthySince(?\DateTimeImmutable $unHealthySince): self
    {
        $this->unHealthySince = $unHealthySince;

        return $this;
    }

    public function getLastNotificationSentAt(): ?\DateTimeImmutable
    {
        return $this->lastNotificationSentAt;
    }

    public function setLastNotificationSentAt(?\DateTimeImmutable $lastNotificationSentAt): self
    {
        $this->lastNotificationSentAt = $lastNotificationSentAt;

        return $this;
    }

    /**
     * Do not update modified_by and date_modified fields if only DNC or manipulator was changed.
     * Avoid unnecessary update queries.
     */
    public function shouldSkipSettingModifiedProperties(): bool
    {
        $changes = $this->changes;

        unset($changes['markedUnhealthyAt']);
        unset($changes['unHealthySince']);
        unset($changes['lastNotificationSentAt']);

        return 0 === count($changes);
    }
}
