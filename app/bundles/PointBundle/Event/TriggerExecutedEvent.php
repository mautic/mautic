<?php

namespace Mautic\PointBundle\Event;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\PointBundle\Entity\TriggerEvent as TriggerEventEntity;
use Symfony\Contracts\EventDispatcher\Event;

class TriggerExecutedEvent extends Event
{
    private ?bool $result = null;

    public function __construct(
        private readonly TriggerEventEntity $triggerEvent,
        private readonly Lead $lead,
    ) {
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
