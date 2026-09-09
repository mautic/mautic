<?php

declare(strict_types=1);

namespace Mautic\ChannelBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\LeadBundle\Entity\Lead;

#[ORM\Entity(repositoryClass: MessageQueueRepository::class)]
#[ORM\Table(name: 'message_queue')]
#[ORM\Index(columns: ['status'], name: 'message_status_search')]
#[ORM\Index(columns: ['date_sent'], name: 'message_date_sent')]
#[ORM\Index(columns: ['scheduled_date'], name: 'message_scheduled_date')]
#[ORM\Index(columns: ['priority'], name: 'message_priority')]
#[ORM\Index(columns: ['success'], name: 'message_success')]
#[ORM\Index(columns: ['channel', 'channel_id'], name: 'message_channel_search')]
#[ORM\Index(columns: ['date_published'], name: 'message_queue_date_published')]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class MessageQueue
{
    public const STATUS_RESCHEDULED = 'rescheduled';

    public const STATUS_PENDING     = 'pending';

    public const STATUS_SENT        = 'sent';

    public const STATUS_CANCELLED   = 'cancelled';

    public const PRIORITY_NORMAL = 2;

    public const PRIORITY_HIGH   = 1;

    /**
     * @var string
     */
    #[ORM\Id]
    #[ORM\Column(type: 'bigint', options: ['unsigned' => true])]
    #[ORM\GeneratedValue]
    private $id;

    /**
     * @var string
     */
    #[ORM\Column(type: 'string', length: 191)]
    private $channel;

    #[ORM\Column(name: 'channel_id', type: 'integer')]
    private $channelId;

    #[ORM\ManyToOne(targetEntity: Event::class)]
    #[ORM\JoinColumn(name: 'event_id', onDelete: 'CASCADE')]
    private ?\Mautic\CampaignBundle\Entity\Event $event = null;

    #[ORM\ManyToOne(targetEntity: \Mautic\LeadBundle\Entity\Lead::class)]
    #[ORM\JoinColumn(name: 'lead_id', nullable: false, onDelete: 'CASCADE')]
    private ?\Mautic\LeadBundle\Entity\Lead $lead = null;

    /**
     * @var int
     */
    #[ORM\Column(type: 'smallint')]
    private $priority = 2;

    /**
     * @var int
     */
    #[ORM\Column(name: 'max_attempts', type: 'smallint')]
    private $maxAttempts = 3;

    /**
     * @var int
     */
    #[ORM\Column(type: 'smallint')]
    private $attempts = 0;

    #[ORM\Column(type: 'boolean')]
    private bool $success = false;

    /**
     * @var string
     */
    #[ORM\Column(type: 'string', length: 191)]
    private $status = self::STATUS_PENDING;

    /**
     * @var \DateTimeInterface
     */
    #[ORM\Column(name: 'date_published', type: 'datetime', nullable: true)]
    private $datePublished;

    /**
     * @var \DateTimeInterface|null
     */
    #[ORM\Column(name: 'scheduled_date', type: 'datetime', nullable: true)]
    private $scheduledDate;

    /**
     * @var \DateTimeInterface|null
     */
    #[ORM\Column(name: 'last_attempt', type: 'datetime', nullable: true)]
    private $lastAttempt;

    /**
     * @var \DateTimeInterface|null
     */
    #[ORM\Column(name: 'date_sent', type: 'datetime', nullable: true)]
    private $dateSent;

    /**
     * @var mixed[][]
     */
    #[ORM\Column(type: 'array', nullable: true)]
    private array $options = [];

    /**
     * Used by listeners to note if the message had been processed in bulk.
     */
    private bool $processed = false;

    /**
     * Used by listeners to tell the event dispatcher the message needs to be retried in 15 minutes.
     */
    private bool $failed = false;

    private bool $metadataUpdated = false;

    public function getId(): int
    {
        return (int) $this->id;
    }

    /**
     * @return int
     */
    public function getAttempts()
    {
        return $this->attempts;
    }

    /**
     * @param int $attempts
     */
    public function setAttempts($attempts): void
    {
        $this->attempts = $attempts;
    }

    /**
     * @return mixed[][]
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @param array $options
     */
    public function setOptions($options): void
    {
        $this->options[] = $options;
    }

    /**
     * @return string|null
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * @param string $channel
     */
    public function setChannel($channel): void
    {
        $this->channel = $channel;
    }

    /**
     * @return int|null
     */
    public function getChannelId()
    {
        return $this->channelId;
    }

    /**
     * @param mixed $channelId
     */
    public function setChannelId($channelId): static
    {
        $this->channelId = $channelId;

        return $this;
    }

    public function getEvent(): ?\Mautic\CampaignBundle\Entity\Event
    {
        return $this->event;
    }

    public function setEvent(Event $event): static
    {
        $this->event = $event;

        return $this;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getDatePublished()
    {
        return $this->datePublished;
    }

    /**
     * @param \DateTime $datePublished
     */
    public function setDatePublished($datePublished): void
    {
        $this->datePublished = $datePublished;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getDateSent()
    {
        return $this->dateSent;
    }

    /**
     * @param \DateTime $dateSent
     */
    public function setDateSent($dateSent): void
    {
        $this->dateSent = $dateSent;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getLastAttempt()
    {
        return $this->lastAttempt;
    }

    /**
     * @param \DateTime $lastAttempt
     */
    public function setLastAttempt($lastAttempt): void
    {
        $this->lastAttempt = $lastAttempt;
    }

    public function getLead(): ?\Mautic\LeadBundle\Entity\Lead
    {
        return $this->lead;
    }

    public function setLead(Lead $lead): void
    {
        $this->lead = $lead;
    }

    /**
     * @return int
     */
    public function getMaxAttempts()
    {
        return $this->maxAttempts;
    }

    /**
     * @param int $maxAttempts
     */
    public function setMaxAttempts($maxAttempts): void
    {
        $this->maxAttempts = $maxAttempts;
    }

    /**
     * @return int
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * @param int $priority
     */
    public function setPriority($priority): void
    {
        $this->priority = $priority;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getScheduledDate()
    {
        return $this->scheduledDate;
    }

    /**
     * @param mixed $scheduledDate
     */
    public function setScheduledDate($scheduledDate): void
    {
        $this->scheduledDate = $scheduledDate;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param string $status
     */
    public function setStatus($status): void
    {
        $this->status = $status;
    }

    public function getSuccess(): bool
    {
        return $this->success;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function setSuccess(bool $success = true): void
    {
        $this->success = $success;
    }

    public function isFailed(): bool
    {
        return $this->failed;
    }

    public function setFailed(bool $failed = true): static
    {
        $this->failed = $failed;

        return $this;
    }

    public function isProcessed(): bool
    {
        return $this->processed;
    }

    public function setProcessed(bool $processed = true): static
    {
        $this->processed = $processed;

        return $this;
    }

    /**
     * @return array<array-key, mixed>
     */
    public function getMetadata()
    {
        return $this->options['metadata'] ?? [];
    }

    public function setMetadata(array $metadata = []): void
    {
        $this->metadataUpdated     = true;
        $this->options['metadata'] = $metadata;
    }

    public function wasMetadataUpdated(): bool
    {
        return $this->metadataUpdated;
    }
}
