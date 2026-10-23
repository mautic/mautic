<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Event;

use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\AbstractEventAccessor;
use Mautic\LeadBundle\Entity\Lead;

final class DecisionEvent extends \Symfony\Contracts\EventDispatcher\Event
{
    use ContextTrait;

    private bool $applicable = false;

    /**
     * @param mixed $passthrough
     */
    public function __construct(
        private readonly AbstractEventAccessor $eventConfig,
        private readonly LeadEventLog $eventLog,
        private $passthrough = null,
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
     * @return mixed
     */
    public function getPassthrough()
    {
        return $this->passthrough;
    }

    /**
     * Note that this decision is a match and the child events should be executed.
     */
    public function setAsApplicable(): void
    {
        $this->applicable = true;
    }

    public function wasDecisionApplicable(): bool
    {
        return $this->applicable;
    }
}
