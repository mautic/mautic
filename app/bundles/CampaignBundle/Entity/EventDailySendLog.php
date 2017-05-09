<?php

namespace Mautic\CampaignBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

class EventDailySendLog
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var Date
     */
    private $date;

    /**
     * @var int
     */
    private $sentCount;

    /**
     * @var Event
     */
    private $event;

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder
            ->setTable('campaign_event_daily_send_log')
            ->setCustomRepositoryClass('Mautic\CampaignBundle\Entity\LeadEventLogRepository')
        ;

        $builder->addId();

        $builder->createManyToOne('event', 'Event')
            ->inversedBy('dailySendLog')
            ->addJoinColumn('event_id', 'id', false, false, 'CASCADE')
            ->build();

        $builder->createField('sentCount', 'integer')
            ->columnName('sent_count')
            ->build();

        $builder->createField('date', 'date')
            ->nullable()
            ->build();
    }

    public function __construct()
    {
        $this->sentCount = 0;
        $this->date      = new \DateTime();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
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
    public function setEvent($event)
    {
        $this->event = $event;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @param \DateTime $date
     *
     * @return EventOption
     */
    public function setDate(\DateTime $date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * @return int
     */
    public function getSentCount()
    {
        return $this->sentCount;
    }

    /**
     * @param $sentCount
     *
     * @return $this
     */
    public function setSentCount($sentCount)
    {
        $this->sentCount = $sentCount;

        return $this;
    }

    public function increaseSentCount()
    {
        return $this->sentCount++;
    }
}
