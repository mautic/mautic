<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Event;

use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\AbstractEventAccessor;

final class ConditionEvent extends CampaignExecutionEvent
{
    use ContextTrait;

    private bool $passed = false;

    public function __construct(
        private readonly AbstractEventAccessor $eventConfig,
        private readonly LeadEventLog $eventLog,
    ) {
        // @deprecated support for pre 2.13.0; to be removed in 3.0
        parent::__construct(
            [
                'eventSettings'   => $eventConfig->getConfig(),
                'eventDetails'    => null,
                'event'           => $eventLog->getEvent(),
                'lead'            => $eventLog->getLead(),
                'systemTriggered' => $eventLog->getSystemTriggered(),
            ],
            null,
            $eventLog
        );
    }

    public function getEventConfig(): AbstractEventAccessor
    {
        return $this->eventConfig;
    }

    public function getLog(): LeadEventLog
    {
        return $this->eventLog;
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
