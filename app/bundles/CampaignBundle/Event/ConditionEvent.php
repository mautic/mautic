<?php

namespace Mautic\CampaignBundle\Event;

use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\AbstractEventAccessor;

class ConditionEvent extends CampaignExecutionEvent
{
    use ContextTrait;

    /**
     * @var AbstractEventAccessor
     */
    private $eventConfig;

    /**
     * @var LeadEventLog
     */
    private $eventLog;

    /**
     * @var bool
     */
    private $passed = false;

    /**
     * DecisionEvent constructor.
     */
    public function __construct(AbstractEventAccessor $config, LeadEventLog $log)
    {
        $this->eventConfig = $config;
        $this->eventLog    = $log;

        // @deprecated support for pre 2.13.0; to be removed in 3.0
        parent::__construct(
            [
                'eventSettings'   => $config->getConfig(),
                'eventDetails'    => null,
                'event'           => $log->getEvent(),
                'lead'            => $log->getLead(),
                'systemTriggered' => $log->getSystemTriggered(),
            ],
            null,
            $log
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
    public function pass()
    {
        $this->passed = true;
    }

    /**
     * Fail this condition.
     */
    public function fail()
    {
        $this->passed = false;
    }

    /**
     * @return bool
     */
    public function wasConditionSatisfied()
    {
        return $this->passed;
    }

    /**
     * @param string   $channel
     * @param int|null $channelId
     */
    public function setChannel($channel, $channelId = null)
    {
        $this->log->setChannel($this->channel)
            ->setChannelId($this->channelId);
    }

    /**
     * @deprecated 2.13.0 to be removed in 3.0; BC support
     *
     * @return bool
     */
    public function getResult()
    {
        return $this->passed;
    }

    /**
     * @deprecated 2.13.0 to be removed in 3.0; BC support
     *
     * @param $result
     *
     * @return $this
     */
    public function setResult($result)
    {
        $this->passed = (bool) $result;

        return $this;
    }
}
