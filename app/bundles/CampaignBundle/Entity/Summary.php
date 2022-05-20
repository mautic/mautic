<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

class Summary
{
    public const TABLE_NAME = 'campaign_summary';

    /**
     * @var int|null
     */
    private $id;

    /**
     * @var \DateTimeInterface|null
     **/
    private $dateTriggered;

    /**
     * @var int|null
     */
    private $scheduledCount = 0;

    /**
     * @var int|null
     */
    private $triggeredCount = 0;

    /**
     * @var int|null
     */
    private $nonActionPathTakenCount = 0;

    /**
     * @var int|null
     */
    private $failedCount = 0;

    /**
     * @var Event|null
     */
    private $event;

    /**
     * @var Campaign|null
     */
    private $campaign;

    /**
     * @var int|null
     */
    private $logCountsProcessed = 0;

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable(self::TABLE_NAME)
            ->setCustomRepositoryClass(SummaryRepository::class)
            ->addUniqueConstraint(['campaign_id', 'event_id', 'date_triggered'], 'campaign_event_date_triggered');

        $builder->addId();

        $builder->createManyToOne('campaign', Campaign::class)
            ->addJoinColumn('campaign_id', 'id')
            ->fetchExtraLazy()
            ->build();

        $builder->createManyToOne('event', Event::class)
            ->addJoinColumn('event_id', 'id', false, false, 'CASCADE')
            ->fetchExtraLazy()
            ->build();

        $builder->addNullableField('dateTriggered', Types::DATETIME_IMMUTABLE, 'date_triggered');
        $builder->addNamedField('scheduledCount', Types::INTEGER, 'scheduled_count');
        $builder->addNamedField('triggeredCount', Types::INTEGER, 'triggered_count');
        $builder->addNamedField('nonActionPathTakenCount', Types::INTEGER, 'non_action_path_taken_count');
        $builder->addNamedField('failedCount', Types::INTEGER, 'failed_count');
        $builder->addNamedField('logCountsProcessed', Types::INTEGER, 'log_counts_processed', true);
    }

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

    public function setDateTriggered(\DateTimeInterface $dateTriggered = null): void
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
