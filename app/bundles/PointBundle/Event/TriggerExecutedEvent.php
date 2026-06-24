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

    public function getTriggerEvent(): TriggerEventEntity
    {
        return $this->triggerEvent;
    }

    public function getLead(): Lead
    {
        return $this->lead;
    }

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
