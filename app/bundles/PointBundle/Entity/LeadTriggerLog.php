<?php

namespace Mautic\PointBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

class LeadTriggerLog
{
    /**
     * @var TriggerEvent
     **/
    private $event;

    /**
     * @var \Mautic\LeadBundle\Entity\Lead
     **/
    private $lead;

    /**
     * @var \Mautic\CoreBundle\Entity\IpAddress|null
     **/
    private $ipAddress;

    /**
     * @var \DateTimeInterface
     **/
    private $dateFired;

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('point_lead_event_log')
            ->setCustomRepositoryClass(LeadTriggerLogRepository::class);

        $builder->createManyToOne('event', 'TriggerEvent')
            ->isPrimaryKey()
            ->addJoinColumn('event_id', 'id', false, false, 'CASCADE')
            ->inversedBy('log')
            ->build();

        $builder->addLead(false, 'CASCADE', true);

        $builder->addIpAddress(true);

        $builder->createField('dateFired', 'datetime')
            ->columnName('date_fired')
            ->build();
    }

    public function getDateFired()
    {
        return $this->dateFired;
    }

    public function setDateFired($dateFired): void
    {
        $this->dateFired = $dateFired;
    }

    public function getIpAddress()
    {
        return $this->ipAddress;
    }

    public function setIpAddress($ipAddress): void
    {
        $this->ipAddress = $ipAddress;
    }

    public function getLead()
    {
        return $this->lead;
    }

    public function setLead($lead): void
    {
        $this->lead = $lead;
    }

    public function getEvent()
    {
        return $this->event;
    }

    public function setEvent($event): void
    {
        $this->event = $event;
    }
}
