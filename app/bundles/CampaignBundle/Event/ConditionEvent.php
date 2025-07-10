<?php

namespace Mautic\CampaignBundle\Event;

use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\AbstractEventAccessor;

class ConditionEvent extends CampaignExecutionEvent
{
    use ContextTrait;

    private bool $passed = false;

    public function __construct(
        private AbstractEventAccessor $eventConfig,
        private LeadEventLog $eventLog,
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

    /**
     * @return AbstractEventAccessor
     */
    public function getEventConfig()
    {
        return $this->eventConfig;
    }

    /**
     * @return LeadEventLog
     */
    public function getLog()
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

    /**
     * @param string   $channel
     * @param int|null $channelId
     */
    public function setChannel($channel, $channelId = null): void
    {
        $this->log->setChannel($this->channel);
        $this->log->setChannelId($this->channelId);
    }

    /**
     * @deprecated 2.13.0 to be removed in 3.0; BC support
     */
    public function getResult(): bool
    {
        return $this->passed;
    }

    /**
     * @deprecated 2.13.0 to be removed in 3.0; BC support
     *
     * @return $this
     */
    public function setResult($result)
    {
        $this->passed = (bool) $result;

        return $this;
    }
}
