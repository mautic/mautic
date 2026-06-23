<?php

namespace Mautic\PointBundle\Event;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\PointBundle\Entity\TriggerEvent as TriggerEventEntity;
use Symfony\Contracts\EventDispatcher\Event;

class TriggerExecutedEvent extends Event
{
    private ?bool $result = null;

    public function __construct(
        private TriggerEventEntity $triggerEvent,
        private Lead $lead,
    ) {
    }

    public function getTriggerEvent(): \Mautic\PointBundle\Entity\TriggerEvent
    {
        return $this->triggerEvent;
    }

    public function getLead(): \Mautic\LeadBundle\Entity\Lead
    {
        return $this->lead;
    }

    /**
     * @return bool
     */
    public function getResult(): ?bool
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
