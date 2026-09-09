<?php

declare(strict_types=1);

namespace Mautic\PointBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Entity\IpAddress;

#[ORM\Entity(repositoryClass: LeadTriggerLogRepository::class)]
#[ORM\Table(name: self::TABLE_NAME)]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class LeadTriggerLog
{
    public const TABLE_NAME = 'point_lead_event_log';

    /**
     * @var TriggerEvent
     */
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: 'TriggerEvent', inversedBy: 'log')]
    #[ORM\JoinColumn(name: 'event_id', nullable: false, onDelete: 'CASCADE')]
    private $event;

    /**
     * @var \Mautic\LeadBundle\Entity\Lead
     */
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: \Mautic\LeadBundle\Entity\Lead::class)]
    #[ORM\JoinColumn(name: 'lead_id', nullable: false, onDelete: 'CASCADE')]
    private $lead;

    /**
     * @var IpAddress|null
     */
    #[ORM\ManyToOne(targetEntity: \Mautic\CoreBundle\Entity\IpAddress::class, cascade: ['persist', 'merge', 'detach'])]
    #[ORM\JoinColumn(name: 'ip_id', onDelete: 'SET NULL')]
    private $ipAddress;

    /**
     * @var \DateTimeInterface
     */
    #[ORM\Column(name: 'date_fired', type: 'datetime')]
    private $dateFired;

    /**
     * @return \DateTimeInterface|null
     */
    public function getDateFired()
    {
        return $this->dateFired;
    }

    /**
     * @param mixed $dateFired
     */
    public function setDateFired($dateFired): void
    {
        $this->dateFired = $dateFired;
    }

    /**
     * @return IpAddress|null
     */
    public function getIpAddress()
    {
        return $this->ipAddress;
    }

    /**
     * @param IpAddress $ipAddress
     */
    public function setIpAddress($ipAddress): void
    {
        $this->ipAddress = $ipAddress;
    }

    /**
     * @return \Mautic\LeadBundle\Entity\Lead|null
     */
    public function getLead()
    {
        return $this->lead;
    }

    /**
     * @param mixed $lead
     */
    public function setLead($lead): void
    {
        $this->lead = $lead;
    }

    /**
     * @return TriggerEvent|null
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * @param mixed $event
     */
    public function setEvent($event): void
    {
        $this->event = $event;
    }
}
