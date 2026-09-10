<?php

declare(strict_types=1);

namespace Mautic\NotificationBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;

#[ORM\Entity(repositoryClass: StatRepository::class)]
#[ORM\Table(name: self::TABLE_NAME)]
#[ORM\Index(columns: ['notification_id', 'lead_id'], name: 'stat_notification_search')]
#[ORM\Index(columns: ['is_clicked'], name: 'stat_notification_clicked_search')]
#[ORM\Index(columns: ['tracking_hash'], name: 'stat_notification_hash_search')]
#[ORM\Index(columns: ['source', 'source_id'], name: 'stat_notification_source_search')]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class Stat
{
    public const TABLE_NAME = 'push_notification_stats';

    /**
     * @var string
     */
    #[ORM\Id]
    #[ORM\Column(type: 'bigint', options: ['unsigned' => true])]
    #[ORM\GeneratedValue]
    private $id;

    #[ORM\ManyToOne(targetEntity: Notification::class, inversedBy: 'stats')]
    #[ORM\JoinColumn(name: 'notification_id', onDelete: 'SET NULL')]
    private ?\Mautic\NotificationBundle\Entity\Notification $notification = null;

    #[ORM\ManyToOne(targetEntity: \Mautic\LeadBundle\Entity\Lead::class)]
    #[ORM\JoinColumn(name: 'lead_id', onDelete: 'SET NULL')]
    private ?\Mautic\LeadBundle\Entity\Lead $lead = null;

    /**
     * @var LeadList|null
     */
    #[ORM\ManyToOne(targetEntity: LeadList::class)]
    #[ORM\JoinColumn(name: 'list_id', onDelete: 'SET NULL')]
    private $list;

    #[ORM\ManyToOne(targetEntity: \Mautic\CoreBundle\Entity\IpAddress::class, cascade: ['persist', 'merge', 'detach'])]
    #[ORM\JoinColumn(name: 'ip_id', onDelete: 'SET NULL')]
    private ?IpAddress $ipAddress = null;

    /**
     * @var \DateTimeInterface
     */
    #[ORM\Column(name: 'date_sent', type: 'datetime')]
    private $dateSent;

    /**
     * @var \DateTimeInterface
     */
    #[ORM\Column(name: 'date_read', type: 'datetime', nullable: true)]
    private $dateRead;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'is_clicked', type: 'boolean')]
    private $isClicked = false;

    /**
     * @var \DateTimeInterface
     */
    #[ORM\Column(name: 'date_clicked', type: 'datetime', nullable: true)]
    private $dateClicked;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'tracking_hash', type: 'string', length: 191, nullable: true)]
    private $trackingHash;

    /**
     * @var int|null
     */
    #[ORM\Column(name: 'retry_count', type: 'integer', nullable: true)]
    private $retryCount = 0;

    /**
     * @var string|null
     */
    #[ORM\Column(type: 'string', length: 191, nullable: true)]
    private $source;

    #[ORM\Column(name: 'source_id', type: 'integer', nullable: true)]
    private ?int $sourceId = null;

    /**
     * @var array
     */
    #[ORM\Column(type: 'array', nullable: true)]
    private $tokens = [];

    /**
     * @var int|null
     */
    #[ORM\Column(name: 'click_count', type: 'integer', nullable: true)]
    private $clickCount;

    /**
     * @var array
     */
    #[ORM\Column(name: 'click_details', type: 'array', nullable: true)]
    private $clickDetails = [];

    #[ORM\Column(name: 'last_clicked', type: 'datetime', nullable: true)]
    private ?\DateTime $lastClicked = null;

    /**
     * Prepares the metadata for API usage.
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata): void
    {
        $metadata->setGroupPrefix('stat')
            ->addProperties(
                [
                    'id',
                    'ipAddress',
                    'dateSent',
                    'isClicked',
                    'dateClicked',
                    'retryCount',
                    'source',
                    'clickCount',
                    'lastClicked',
                    'sourceId',
                    'trackingHash',
                    'lead',
                    'notification',
                ]
            )
            ->build();
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getDateClicked()
    {
        return $this->dateClicked;
    }

    /**
     * @param mixed $dateClicked
     */
    public function setDateClicked($dateClicked): void
    {
        $this->dateClicked = $dateClicked;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getDateSent()
    {
        return $this->dateSent;
    }

    /**
     * @param mixed $dateSent
     */
    public function setDateSent($dateSent): void
    {
        $this->dateSent = $dateSent;
    }

    public function getNotification(): ?\Mautic\NotificationBundle\Entity\Notification
    {
        return $this->notification;
    }

    public function setNotification(?Notification $notification = null): void
    {
        $this->notification = $notification;
    }

    public function getId(): int
    {
        return (int) $this->id;
    }

    public function getIpAddress(): ?IpAddress
    {
        return $this->ipAddress;
    }

    public function setIpAddress(?IpAddress $ip): void
    {
        $this->ipAddress = $ip;
    }

    /**
     * @return bool
     */
    public function getIsClicked()
    {
        return $this->isClicked;
    }

    /**
     * @param mixed $isClicked
     */
    public function setIsClicked($isClicked): void
    {
        $this->isClicked = $isClicked;
    }

    public function getLead(): ?\Mautic\LeadBundle\Entity\Lead
    {
        return $this->lead;
    }

    public function setLead(?Lead $lead = null): void
    {
        $this->lead = $lead;
    }

    /**
     * @return string|null
     */
    public function getTrackingHash()
    {
        return $this->trackingHash;
    }

    /**
     * @param mixed $trackingHash
     */
    public function setTrackingHash($trackingHash): void
    {
        $this->trackingHash = $trackingHash;
    }

    /**
     * @return LeadList|null
     */
    public function getList()
    {
        return $this->list;
    }

    /**
     * @param mixed $list
     */
    public function setList($list): void
    {
        $this->list = $list;
    }

    /**
     * @return int|null
     */
    public function getRetryCount()
    {
        return $this->retryCount;
    }

    /**
     * @param mixed $retryCount
     */
    public function setRetryCount($retryCount): void
    {
        $this->retryCount = $retryCount;
    }

    public function upRetryCount(): void
    {
        ++$this->retryCount;
    }

    /**
     * @return string|null
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @param mixed $source
     */
    public function setSource($source): void
    {
        $this->source = $source;
    }

    public function getSourceId(): ?int
    {
        return $this->sourceId;
    }

    /**
     * @param mixed $sourceId
     */
    public function setSourceId($sourceId): void
    {
        $this->sourceId = (int) $sourceId;
    }

    /**
     * @return array<array-key, mixed>
     */
    public function getTokens()
    {
        return $this->tokens;
    }

    /**
     * @param mixed $tokens
     */
    public function setTokens($tokens): void
    {
        $this->tokens = $tokens;
    }

    /**
     * @return int|null
     */
    public function getClickCount()
    {
        return $this->clickCount;
    }

    /**
     * @param mixed $clickCount
     */
    public function setClickCount($clickCount): static
    {
        $this->clickCount = $clickCount;

        return $this;
    }

    public function addClickDetails($details): void
    {
        $this->clickDetails[] = $details;

        ++$this->clickCount;
    }

    /**
     * Up the sent count.
     */
    public function upClickCount(): static
    {
        $count            = (int) $this->clickCount + 1;
        $this->clickCount = $count;

        return $this;
    }

    public function getLastClicked(): ?\DateTime
    {
        return $this->lastClicked;
    }

    public function setLastClicked(\DateTime $lastClicked): static
    {
        $this->lastClicked = $lastClicked;

        return $this;
    }

    /**
     * @return array<array-key, mixed>
     */
    public function getClickDetails()
    {
        return $this->clickDetails;
    }

    /**
     * @param mixed $clickDetails
     */
    public function setClickDetails($clickDetails): static
    {
        $this->clickDetails = $clickDetails;

        return $this;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getDateRead()
    {
        return $this->dateRead;
    }

    /**
     * @param \DateTime $dateRead
     */
    public function setDateRead($dateRead): static
    {
        $this->dateRead = $dateRead;

        return $this;
    }
}
