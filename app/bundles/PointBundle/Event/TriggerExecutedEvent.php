<?php

namespace Mautic\PointBundle\Event;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\PointBundle\Entity\TriggerEvent as TriggerEventEntity;
use Symfony\Component\EventDispatcher\Event;

class TriggerExecutedEvent extends Event
{
    /** @var TriggerEventEntity */
    private $triggerEvent;

    /** @var Lead */
    private $lead;

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

    public function setSucceded()
    {
        $this->result = true;
    }

    public function setFailed()
    {
        $this->result = false;
    }
}
