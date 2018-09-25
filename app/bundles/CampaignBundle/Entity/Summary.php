<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

/**
 * Class Summary.
 */
class Summary
{
    /**
     * @var bool
     */
    private $new = true;

    /**
     * @var int
     */
    private $id;

    /**
     * @var \DateTime
     **/
    private $dateTriggered;

    /**
     * @var int
     */
    private $triggeredCount = 0;

    /**
     * @var int
     */
    private $nonActionPathTakenCount = 0;

    /**
     * @var int
     */
    private $failedCount = 0;

    /**
     * @var Event
     */
    private $event;

    /**
     * @var Campaign
     */
    private $campaign;

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('campaign_summary')
            ->setCustomRepositoryClass('Mautic\CampaignBundle\Entity\SummaryRepository')
            ->addUniqueConstraint(['campaign_id', 'event_id', 'date_triggered'], 'campaign_event_date_triggered');

        $builder->addId();

        $builder->createManyToOne('campaign', 'Campaign')
            ->addJoinColumn('campaign_id', 'id')
            ->fetchExtraLazy()
            ->build();

        $builder->createManyToOne('event', 'Event')
            ->addJoinColumn('event_id', 'id', false, false, 'CASCADE')
            ->fetchExtraLazy()
            ->build();

        $builder->createField('dateTriggered', 'datetime')
            ->columnName('date_triggered')
            ->nullable()
            ->build();

        $builder->createField('triggeredCount', 'integer')
            ->columnName('triggered_count')
            ->build();

        $builder->createField('nonActionPathTakenCount', 'integer')
            ->columnName('non_action_path_taken_count')
            ->build();

        $builder->createField('failedCount', 'integer')
            ->columnName('failed_count')
            ->build();
    }

    /**
     * @return int
     */
    public function getTriggeredCount()
    {
        return $this->triggeredCount;
    }

    /**
     * @param int $triggeredCount
     *
     * @return $this
     */
    public function setTriggeredCount($triggeredCount)
    {
        $this->triggeredCount = $triggeredCount;

        return $this;
    }

    /**
     * @return int
     */
    public function getNonActionPathTakenCount()
    {
        return $this->nonActionPathTakenCount;
    }

    /**
     * @param int $nonActionPathTakenCount
     *
     * @return $this
     */
    public function setNonActionPathTakenCount($nonActionPathTakenCount)
    {
        $this->nonActionPathTakenCount = $nonActionPathTakenCount;

        return $this;
    }

    /**
     * @return int
     */
    public function getFailedCount()
    {
        return $this->failedCount;
    }

    /**
     * @param int $failedCount
     *
     * @return $this
     */
    public function setFailedCount($failedCount)
    {
        $this->failedCount = $failedCount;

        return $this;
    }

    /**
     * @return Campaign
     */
    public function getCampaign()
    {
        return $this->campaign;
    }

    /**
     * @param Campaign $campaign
     *
     * @return $this
     */
    public function setCampaign(Campaign $campaign)
    {
        $this->campaign = $campaign;

        return $this;
    }

    /**
     * @return Event
     */
    public function getEvent()
    {
        return $this->event;
    }

    /***
     * @param $event
     *
     * @return $this
     */
    public function setEvent(Event $event)
    {
        $this->event = $event;

        if (!$this->campaign) {
            $this->setCampaign($event->getCampaign());
        }

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDateTriggered()
    {
        return $this->dateTriggered;
    }

    /**
     * @param \DateTime|null $dateTriggered
     *
     * @return $this
     */
    public function setDateTriggered(\DateTime $dateTriggered = null)
    {
        $this->dateTriggered = $dateTriggered;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return bool
     */
    public function getNew()
    {
        return $this->new;
    }

    /**
     * @param $new
     *
     * @return $this
     */
    public function setNew($new)
    {
        $this->new = $new;

        return $this;
    }
}
