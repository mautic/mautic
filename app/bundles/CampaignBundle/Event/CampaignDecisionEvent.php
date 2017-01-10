<?php

/*
 * @copyright   2015 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Class CampaignDecisionEvent.
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

    /**
     * @param $lead
     * @param $decisionType
     * @param $decisionEventDetails
     * @param $events
     * @param $eventSettings
     * @param $isRootLevel
     */
    public function __construct($lead, $decisionType, $decisionEventDetails, $events, $eventSettings, $isRootLevel = false)
    {
        $this->lead                 = $lead;
        $this->decisionType         = $decisionType;
        $this->decisionEventDetails = $decisionEventDetails;
        $this->events               = $events;
        $this->eventSettings        = $eventSettings;
        $this->isRootLevel          = $isRootLevel;
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
}
