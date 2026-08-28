<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Event;

use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\AbstractEventAccessor;

final class DecisionEvent extends CampaignExecutionEvent
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
        // @deprecated support for pre 2.13.0; to be removed in 3.0
        parent::__construct(
            [
                'eventSettings'   => $eventConfig->getConfig(),
                'eventDetails'    => $passthrough,
                'event'           => $eventLog->getEvent(),
                'lead'            => $eventLog->getLead(),
                'systemTriggered' => defined('MAUTIC_CAMPAIGN_SYSTEM_TRIGGERED'),
                'dateScheduled'   => $eventLog->getTriggerDate(),
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
