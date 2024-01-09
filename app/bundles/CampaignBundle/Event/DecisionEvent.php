<?php

namespace Mautic\CampaignBundle\Event;

use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\AbstractEventAccessor;

class DecisionEvent extends CampaignExecutionEvent
{
    use ContextTrait;

    private bool $applicable = false;

    /**
     * @param mixed $passthrough
     */
    public function __construct(
        private AbstractEventAccessor $eventConfig,
        private LeadEventLog $eventLog,
        private $passthrough = null
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
        return $this->applicable;
    }

    /**
     * @deprecated 2.13.0 to be removed in 3.0; BC support
     *
     * @param mixed $result
     *
     * @return $this
     */
    public function setResult($result)
    {
        $this->applicable = (bool) $result;

        return $this;
    }
}
