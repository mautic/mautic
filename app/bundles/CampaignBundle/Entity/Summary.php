<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SummaryRepository::class)]
#[ORM\Table(name: self::TABLE_NAME)]
#[ORM\UniqueConstraint(columns: ['campaign_id', 'event_id', 'date_triggered'], name: 'campaign_event_date_triggered')]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class Summary
{
    public const TABLE_NAME = 'campaign_summary';

    /**
     * @var int|null
     */
    #[ORM\Id]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    #[ORM\GeneratedValue]
    private $id;

    /**
     * @var \DateTimeImmutable|null
     */
    #[ORM\Column(name: 'date_triggered', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private \DateTimeImmutable|\DateTimeInterface|null $dateTriggered = null;

    /**
     * @var int
     */
    #[ORM\Column(name: 'scheduled_count', type: Types::INTEGER)]
    private $scheduledCount = 0;

    /**
     * @var int
     */
    #[ORM\Column(name: 'triggered_count', type: Types::INTEGER)]
    private $triggeredCount = 0;

    /**
     * @var int
     */
    #[ORM\Column(name: 'non_action_path_taken_count', type: Types::INTEGER)]
    private $nonActionPathTakenCount = 0;

    /**
     * @var int
     */
    #[ORM\Column(name: 'failed_count', type: Types::INTEGER)]
    private $failedCount = 0;

    /**
     * @var Event|null
     */
    #[ORM\ManyToOne(targetEntity: Event::class, fetch: 'EXTRA_LAZY')]
    #[ORM\JoinColumn(name: 'event_id', nullable: false, onDelete: 'CASCADE')]
    private $event;

    #[ORM\ManyToOne(targetEntity: Campaign::class, fetch: 'EXTRA_LAZY')]
    #[ORM\JoinColumn(name: 'campaign_id')]
    private ?Campaign $campaign = null;

    /**
     * @var int|null
     */
    #[ORM\Column(name: 'log_counts_processed', type: Types::INTEGER, nullable: true)]
    private $logCountsProcessed = 0;

    public function getScheduledCount(): ?int
    {
        return $this->scheduledCount;
    }

    public function setScheduledCount(int $scheduledCount): void
    {
        $this->scheduledCount = $scheduledCount;
    }

    public function getTriggeredCount(): ?int
    {
        return $this->triggeredCount;
    }

    public function setTriggeredCount(int $triggeredCount): void
    {
        $this->triggeredCount = $triggeredCount;
    }

    public function getNonActionPathTakenCount(): ?int
    {
        return $this->nonActionPathTakenCount;
    }

    public function setNonActionPathTakenCount(int $nonActionPathTakenCount): void
    {
        $this->nonActionPathTakenCount = $nonActionPathTakenCount;
    }

    public function getFailedCount(): ?int
    {
        return $this->failedCount;
    }

    public function setFailedCount(int $failedCount): void
    {
        $this->failedCount = $failedCount;
    }

    public function getCampaign(): ?Campaign
    {
        return $this->campaign;
    }

    public function setCampaign(Campaign $campaign): void
    {
        $this->campaign = $campaign;
    }

    public function getEvent(): ?Event
    {
        return $this->event;
    }

    public function setEvent(Event $event): void
    {
        $this->event = $event;

        if (!$this->campaign) {
            $this->setCampaign($event->getCampaign());
        }
    }

    public function getDateTriggered(): ?\DateTimeInterface
    {
        return $this->dateTriggered;
    }

    public function setDateTriggered(?\DateTimeImmutable $dateTriggered = null): void
    {
        $this->dateTriggered = $dateTriggered;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLogCountsProcessed(): ?int
    {
        return $this->logCountsProcessed;
    }

    public function setLogCountsProcessed(?int $logCountsProcessed): void
    {
        $this->logCountsProcessed = $logCountsProcessed;
    }
}
