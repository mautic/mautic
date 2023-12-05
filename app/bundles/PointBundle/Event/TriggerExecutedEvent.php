<?php

namespace Mautic\PointBundle\Event;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\PointBundle\Entity\TriggerEvent as TriggerEventEntity;
use Symfony\Contracts\EventDispatcher\Event;

class TriggerExecutedEvent extends Event
{
    private TriggerEventEntity $triggerEvent;

    private \Mautic\LeadBundle\Entity\Lead $lead;

    /** @var bool */
    private $result;

    public function __construct(TriggerEventEntity $triggerEvent, Lead $lead)
    {
        $this->triggerEvent = $triggerEvent;
        $this->lead         = $lead;
    }

    /**
     * @return TriggerEventEntity
     */
    public function getTriggerEvent()
    {
        return $this->triggerEvent;
    }

    /**
     * @return Lead
     */
    public function getLead()
    {
        return $this->lead;
    }

    /**
     * @return bool
     */
    public function getResult()
    {
        return $this->result;
    }

    public function setSucceded(): void
    {
        $this->result = true;
    }

    public function setFailed(): void
    {
        $this->result = false;
    }
}
