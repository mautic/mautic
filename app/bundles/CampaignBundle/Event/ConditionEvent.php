<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Event;

use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\AbstractEventAccessor;
use Mautic\LeadBundle\Entity\Lead;

final class ConditionEvent extends \Symfony\Contracts\EventDispatcher\Event
{
    use ContextTrait;

    private bool $passed = false;

    public function __construct(
        private readonly AbstractEventAccessor $eventConfig,
        private readonly LeadEventLog $eventLog,
    ) {
    }

    public function getEventConfig(): AbstractEventAccessor
    {
        return $this->eventConfig;
    }

    public function getLog(): LeadEventLog
    {
        return $this->eventLog;
    }

    public function getEvent(): Event
    {
        $event = $this->eventLog->getEvent();
        \assert($event instanceof Event);

        return $event;
    }

    public function getLead(): ?Lead
    {
        return $this->eventLog->getLead();
    }

    /**
     * Pass this condition.
     */
    public function pass(): void
    {
        $this->passed = true;
    }

    /**
     * Fail this condition.
     */
    public function fail(): void
    {
        $this->passed = false;
    }

    public function wasConditionSatisfied(): bool
    {
        return $this->passed;
    }
}
