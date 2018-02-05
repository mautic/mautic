<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Executioner\Result;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\LeadEventLog;

class Responses
{
    /**
     * @var array
     */
    private $actionResponses = [];

    /**
     * @var array
     */
    private $conditionResponses = [];

    /**
     * DecisionResponses constructor.
     *
     * @param ArrayCollection|null $logs
     */
    public function setFromLogs(ArrayCollection $logs)
    {
        /** @var LeadEventLog $log */
        foreach ($logs as $log) {
            $this->setResponse($log->getEvent(), $log->getMetadata());
        }
    }

    /**
     * @param Event $event
     * @param       $response
     */
    public function setResponse(Event $event, $response)
    {
        switch ($event->getEventType()) {
            case Event::TYPE_ACTION:
                if (!isset($this->actionResponses[$event->getType()])) {
                    $this->actionResponses[$event->getType()] = [];
                }
                $this->actionResponses[$event->getType()][$event->getId()] = $response;
                break;
            case Event::TYPE_CONDITION:
                if (!isset($this->conditionResponses[$event->getType()])) {
                    $this->conditionResponses[$event->getType()] = [];
                }
                $this->conditionResponses[$event->getType()][$event->getId()] = $response;
                break;
        }
    }

    /**
     * @param null $type
     *
     * @return array|mixed
     */
    public function getActionResponses($type = null)
    {
        if ($type) {
            return (isset($this->actionResponses[$type])) ? $this->actionResponses[$type] : [];
        }

        return $this->actionResponses;
    }

    /**
     * @param null $type
     *
     * @return array|mixed
     */
    public function getConditionResponses($type = null)
    {
        if ($type) {
            return (isset($this->conditionResponses[$type])) ? $this->conditionResponses[$type] : [];
        }

        return $this->conditionResponses;
    }

    /**
     * @deprecated 2.13.0 to be removed in 3.0; used for BC EventModel::triggerEvent()
     *
     * @return array
     */
    public function getResponseArray()
    {
        return array_merge($this->actionResponses, $this->conditionResponses);
    }
}
