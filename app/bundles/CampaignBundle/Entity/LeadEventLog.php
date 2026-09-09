<?php

namespace Mautic\CampaignBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Entity\OptimisticLockInterface;
use Mautic\CoreBundle\Entity\OptimisticLockTrait;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\LeadBundle\Entity\Lead as LeadEntity;

#[ORM\Entity(repositoryClass: LeadEventLogRepository::class)]
#[ORM\Table(name: self::TABLE_NAME)]
#[ORM\Index(columns: ['is_scheduled', 'lead_id'], name: 'campaign_event_upcoming_search')]
#[ORM\Index(columns: ['campaign_id', 'is_scheduled', 'trigger_date'], name: 'campaign_event_schedule_counts')]
#[ORM\Index(columns: ['date_triggered'], name: 'campaign_date_triggered')]
#[ORM\Index(columns: ['campaign_id', 'lead_id', 'rotation'], name: 'campaign_leads')]
#[ORM\Index(columns: ['channel', 'channel_id', 'lead_id'], name: 'campaign_log_channel')]
#[ORM\Index(columns: ['campaign_id', 'event_id', 'date_triggered'], name: 'campaign_actions')]
#[ORM\Index(columns: ['campaign_id', 'date_triggered', 'event_id', 'non_action_path_taken'], name: 'campaign_stats')]
#[ORM\Index(columns: ['trigger_date'], name: 'campaign_trigger_date_order')]
#[ORM\Index(columns: ['is_scheduled', 'event_id', 'trigger_date'], name: 'idx_scheduled_events')]
#[ORM\UniqueConstraint(name: 'campaign_rotation', columns: ['event_id', 'lead_id', 'rotation'])]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class LeadEventLog implements ChannelInterface, OptimisticLockInterface
{
    use OptimisticLockTrait;

    public const TABLE_NAME = 'campaign_lead_event_log';

    /**
     * @var string|null
     */
    #[ORM\Id]
    #[ORM\Column(type: 'bigint', options: ['unsigned' => true])]
    #[ORM\GeneratedValue]
    private $id;

    /**
     * @var Event
     */
    #[ORM\ManyToOne(targetEntity: 'Event', inversedBy: 'log')]
    #[ORM\JoinColumn(name: 'event_id', nullable: false)]
    private $event;

    /**
     * @var LeadEntity
     */
    #[ORM\ManyToOne(targetEntity: \Mautic\LeadBundle\Entity\Lead::class)]
    #[ORM\JoinColumn(name: 'lead_id', nullable: false, onDelete: 'CASCADE')]
    private $lead;

    /**
     * @var Campaign|null
     */
    #[ORM\ManyToOne(targetEntity: 'Campaign')]
    #[ORM\JoinColumn(name: 'campaign_id')]
    private $campaign;

    /**
     * @var IpAddress|null
     */
    #[ORM\ManyToOne(targetEntity: \Mautic\CoreBundle\Entity\IpAddress::class, cascade: ['persist', 'merge', 'detach'])]
    #[ORM\JoinColumn(name: 'ip_id', onDelete: 'SET NULL')]
    private $ipAddress;

    /**
     * @var \DateTimeInterface|null
     */
    #[ORM\Column(name: 'date_triggered', type: 'datetime', nullable: true)]
    private $dateTriggered;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'is_scheduled', type: 'boolean')]
    private $isScheduled = false;

    /**
     * @var \DateTimeInterface|null
     */
    #[ORM\Column(name: 'trigger_date', type: 'datetime', nullable: true)]
    private $triggerDate;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'system_triggered', type: 'boolean')]
    private $systemTriggered = false;

    /**
     * @var array
     */
    #[ORM\Column(type: 'array', nullable: true)]
    private $metadata = [];

    /**
     * @var bool|null
     */
    #[ORM\Column(name: 'non_action_path_taken', type: 'boolean', nullable: true)]
    private $nonActionPathTaken = false;

    /**
     * @var string|null
     */
    #[ORM\Column(type: 'string', length: 191, nullable: true)]
    private $channel;

    /**
     * @var int|null
     */
    #[ORM\Column(name: 'channel_id', type: 'integer', nullable: true)]
    private $channelId;

    /**
     * @var bool|null
     */
    private $previousScheduledState;

    /**
     * @var int
     */
    #[ORM\Column(type: 'integer')]
    private $rotation = 1;

    /**
     * @var FailedLeadEventLog|null
     */
    #[ORM\OneToOne(mappedBy: 'log', targetEntity: 'FailedLeadEventLog', cascade: ['all'], fetch: 'EXTRA_LAZY')]
    private $failedLog;

    /**
     * Subscribers can fail log with custom reschedule interval.
     */
    private ?\DateInterval $rescheduleInterval = null;

    #[ORM\Column(name: 'date_queued', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTime $dateQueued = null;

    /**
     * Prepares the metadata for API usage.
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata): void
    {
        $metadata->setGroupPrefix('campaignEventLog')
            ->addProperties(
                [
                    'ipAddress',
                    'dateTriggered',
                    'isScheduled',
                    'triggerDate',
                    'metadata',
                    'nonActionPathTaken',
                    'channel',
                    'channelId',
                    'rotation',
                ]
            )

            // Add standalone groups
            ->setGroupPrefix('campaignEventStandaloneLog')
            ->addProperties(
                [
                    'event',
                    'lead',
                    'campaign',
                    'ipAddress',
                    'dateTriggered',
                    'isScheduled',
                    'triggerDate',
                    'metadata',
                    'nonActionPathTaken',
                    'channel',
                    'channelId',
                    'rotation',
                ]
            )
            ->build();
    }

    public function getId(): int
    {
        return (int) $this->id;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getDateTriggered()
    {
        return $this->dateTriggered;
    }

    public function setDateTriggered(?\DateTimeInterface $dateTriggered = null): static
    {
        $this->dateTriggered = $dateTriggered;
        if (null !== $dateTriggered) {
            $this->setIsScheduled(false);
        }

        return $this;
    }

    /**
     * @return IpAddress|null
     */
    public function getIpAddress()
    {
        return $this->ipAddress;
    }

    public function setIpAddress(IpAddress $ipAddress): static
    {
        $this->ipAddress = $ipAddress;

        return $this;
    }

    /**
     * @return LeadEntity|null
     */
    public function getLead()
    {
        return $this->lead;
    }

    public function setLead(LeadEntity $lead): static
    {
        $this->lead = $lead;

        return $this;
    }

    /**
     * @return Event|null
     */
    public function getEvent()
    {
        return $this->event;
    }

    public function setEvent(Event $event): static
    {
        $this->event = $event;

        if (!$this->campaign) {
            $this->setCampaign($event->getCampaign());
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function getIsScheduled()
    {
        return $this->isScheduled;
    }

    /**
     * @param bool $isScheduled
     */
    public function setIsScheduled($isScheduled): static
    {
        $this->previousScheduledState ??= $this->isScheduled;

        $this->isScheduled = $isScheduled;

        return $this;
    }

    /**
     * If isScheduled was changed, this will have the previous state.
     *
     * @return bool|null
     */
    public function getPreviousScheduledState()
    {
        return $this->previousScheduledState;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getTriggerDate()
    {
        return $this->triggerDate;
    }

    public function setTriggerDate(?\DateTimeInterface $triggerDate = null, ?string $note = null): static
    {
        $this->triggerDate = $triggerDate;
        $this->setIsScheduled(true);
        $this->logTriggerDateChange($triggerDate, $note);

        return $this;
    }

    private function logTriggerDateChange(?\DateTimeInterface $newTriggerDate, ?string $note): void
    {
        $this->metadata['triggerDateLog'] ??= [];
        $this->metadata['triggerDateLog'][] = [
            'date'      => new \DateTime()->format(DateTimeHelper::FORMAT_DB),
            'changedTo' => $newTriggerDate ? $newTriggerDate->format(DateTimeHelper::FORMAT_DB) : null,
            'note'      => $note,
        ];
    }

    /**
     * @return Campaign|null
     */
    public function getCampaign()
    {
        return $this->campaign;
    }

    public function setCampaign(Campaign $campaign): static
    {
        $this->campaign = $campaign;

        return $this;
    }

    /**
     * @return bool
     */
    public function getSystemTriggered()
    {
        return $this->systemTriggered;
    }

    /**
     * @param bool $systemTriggered
     */
    public function setSystemTriggered($systemTriggered): static
    {
        $this->systemTriggered = $systemTriggered;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function getNonActionPathTaken()
    {
        return $this->nonActionPathTaken;
    }

    /**
     * @param bool $nonActionPathTaken
     */
    public function setNonActionPathTaken($nonActionPathTaken): static
    {
        $this->nonActionPathTaken = $nonActionPathTaken;

        return $this;
    }

    /**
     * @return mixed[]
     */
    public function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * @param mixed[] $metadata
     */
    public function appendToMetadata($metadata): void
    {
        if (!is_array($metadata)) {
            // Assumed output for timeline BC for <2.14
            $metadata = ['timeline' => $metadata];
        }

        $this->metadata = array_merge($this->metadata, $metadata);
    }

    /**
     * @param mixed[] $metadata
     */
    public function setMetadata($metadata): static
    {
        if (!is_array($metadata)) {
            // Assumed output for timeline
            $metadata = ['timeline' => $metadata];
        }

        $this->metadata = $metadata;

        return $this;
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
     * @param int|null $channelId
     */
    public function setChannelId($channelId): void
    {
        $this->channelId = $channelId;
    }

    /**
     * @return int
     */
    public function getRotation()
    {
        return $this->rotation;
    }

    /**
     * @param int $rotation
     */
    public function setRotation($rotation): static
    {
        $this->rotation = (int) $rotation;

        return $this;
    }

    /**
     * @return FailedLeadEventLog|null
     */
    public function getFailedLog()
    {
        return $this->failedLog;
    }

    public function setFailedLog(?FailedLeadEventLog $log = null): static
    {
        $this->failedLog = $log;

        return $this;
    }

    public function isFailed(): bool
    {
        return !empty($this->failedLog);
    }

    public function isSuccess(): bool
    {
        return !$this->isFailed();
    }

    public function setRescheduleInterval(?\DateInterval $interval): void
    {
        $this->rescheduleInterval = $interval;
    }

    public function getRescheduleInterval(): ?\DateInterval
    {
        return $this->rescheduleInterval;
    }

    public function getDateQueued(): ?\DateTime
    {
        return $this->dateQueued;
    }

    public function setDateQueued(?\DateTime $dateQueued): self
    {
        $this->dateQueued = $dateQueued;

        return $this;
    }
}
