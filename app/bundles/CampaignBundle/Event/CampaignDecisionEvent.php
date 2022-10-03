<?php

namespace Mautic\CampaignBundle\Event;

use Mautic\CampaignBundle\Entity\LeadEventLog;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class CampaignDecisionEvent.
 *
 * @deprecated 2.13.0; to be removed in 3.0
 */
class CampaignDecisionEvent extends Event
{
    protected $lead;
    protected $events;
    protected $decisionType;
    protected $decisionEventDetails;
    protected $eventSettings;
    protected $isRootLevel;
    protected $decisionTriggered = false;
    protected $logs;

    /**
     * @param $lead
     * @param $decisionType
     * @param $decisionEventDetails
     * @param $events
     * @param $eventSettings
     * @param $isRootLevel
     * @param LeadEventLog[] $logs
     */
    public function __construct($lead, $decisionType, $decisionEventDetails, $events, $eventSettings, $isRootLevel = false, $logs = [])
    {
        $this->lead                 = $lead;
        $this->decisionType         = $decisionType;
        $this->decisionEventDetails = $decisionEventDetails;
        $this->events               = $events;
        $this->eventSettings        = $eventSettings;
        $this->isRootLevel          = $isRootLevel;
        $this->logs                 = $logs;
    }

    /**
     * @return mixed
     */
    public function getLead()
    {
        return $this->lead;
    }

    /**
     * @return mixed
     */
    public function getEvents()
    {
        return $this->events;
    }

    /**
     * @return mixed
     */
    public function getDecisionType()
    {
        return $this->decisionType;
    }

    /**
     * @return mixed
     */
    public function getDecisionEventDetails()
    {
        return $this->decisionEventDetails;
    }

    /**
     * @param null $eventType
     * @param null $type
     *
     * @return bool
     */
    public function getEventSettings($eventType = null, $type = null)
    {
        if ($type) {
            return (!empty($this->eventSettings[$eventType][$type])) ? $this->eventSettings[$eventType][$type] : false;
        } elseif ($eventType) {
            return (!empty($this->eventSettings[$eventType])) ? $this->eventSettings[$eventType] : false;
        }

        return $this->eventSettings;
    }

    /**
     * Is the decision used as a root level event?
     *
     * @return bool
     */
    public function isRootLevel()
    {
        return $this->isRootLevel;
    }

    /**
     * Set if the decision has already been triggered and if so, child events will be executed.
     *
     * @param bool|true $triggered
     */
    public function setDecisionAlreadyTriggered($triggered = true)
    {
        $this->decisionTriggered = $triggered;
    }

    /**
     * Returns if the decision has already been triggered.
     *
     * @return mixed
     */
    public function wasDecisionTriggered()
    {
        return $this->decisionTriggered;
    }

    /**
     * @return array|LeadEventLog[]
     */
    public function getLogs()
    {
        return $this->logs;
    }
}
