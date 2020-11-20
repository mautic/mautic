<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

class Summary
{
    public const TABLE_NAME = 'campaign_summary';

    /**
     * @var int|null
     */
    private $id;

    /**
     * @var \DateTime|null
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

    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable(self::TABLE_NAME)
            ->setCustomRepositoryClass(SummaryRepository::class)
            ->addUniqueConstraint(['campaign_id', 'event_id', 'date_triggered'], 'campaign_event_date_triggered');

        $builder->addId();

        $builder->createManyToOne('campaign', 'Campaign')
            ->addJoinColumn('campaign_id', Types::INTEGER)
            ->fetchExtraLazy()
            ->build();

        $builder->createManyToOne('event', Event::class)
            ->addJoinColumn('event_id', Types::INTEGER, false, false, 'CASCADE')
            ->fetchExtraLazy()
            ->build();

        $builder->addNullableField('dateTriggered', Types::DATETIME_IMMUTABLE, 'date_triggered');
        $builder->addNamedField('scheduledCount', Types::INTEGER, 'scheduled_count');
        $builder->addNamedField('triggeredCount', Types::INTEGER, 'triggered_count');
        $builder->addNamedField('nonActionPathTakenCount', Types::INTEGER, 'non_action_path_taken_count');
        $builder->addNamedField('failedCount', Types::INTEGER, 'failed_count');
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

    public function getDateTriggered(): ?\DateTime
    {
        return $this->dateTriggered;
    }

    public function setDateTriggered(\DateTime $dateTriggered = null): void
    {
        $this->dateTriggered = $dateTriggered;
    }

    public function getId(): ?int
    {
        return $this->id;
    }
}
