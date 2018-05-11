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
     * @param ArrayCollection $logs
     */
    public function setFromLogs(ArrayCollection $logs)
    {
        /** @var LeadEventLog $log */
        foreach ($logs as $log) {
            $metadata = $log->getMetadata();
            $response = $metadata;

            if (isset($metadata['timeline']) && count($metadata) === 1) {
                // Legacy listeners set a string in CampaignExecutionEvent::setResult that Lead::appendToMetadata put into
                // under a timeline key for BC support. To keep BC for decisions, we have to extract that back out for the bubble
                // up responses

                $response = $metadata['timeline'];
            }

            $this->setResponse($log->getEvent(), $response);
        }
    }

    /**
     * @param Event $event
     * @param mixed $response
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
     * @param string|null $type
     *
     * @return array
     */
    public function getActionResponses($type = null)
    {
        if ($type) {
            return (isset($this->actionResponses[$type])) ? $this->actionResponses[$type] : [];
        }

        return $this->actionResponses;
    }

    /**
     * @param string|null $type
     *
     * @return array
     */
    public function getConditionResponses($type = null)
    {
        if ($type) {
            return (isset($this->conditionResponses[$type])) ? $this->conditionResponses[$type] : [];
        }

        return $this->conditionResponses;
    }

    /**
     * @return int
     */
    public function containsResponses()
    {
        return count($this->actionResponses) + count($this->conditionResponses);
    }

    /**
     * @deprecated 2.13.0 to be removed in 3.0; used for BC EventModel::triggerEvent()
     *
     * @return array
     */
    public function getResponseArray()
    {
        return [
            Event::TYPE_ACTION    => $this->actionResponses,
            Event::TYPE_CONDITION => $this->conditionResponses,
        ];
    }
}
