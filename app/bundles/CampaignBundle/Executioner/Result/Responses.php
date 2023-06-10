<?php

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
     */
    public function setFromLogs(ArrayCollection $logs)
    {
        /** @var LeadEventLog $log */
        foreach ($logs as $log) {
            $metadata = $log->getMetadata();
            $response = $metadata;

            if (isset($metadata['timeline']) && 1 === count($metadata)) {
                // Legacy listeners set a string in CampaignExecutionEvent::setResult that Lead::appendToMetadata put into
                // under a timeline key for BC support. To keep BC for decisions, we have to extract that back out for the bubble
                // up responses

                $response = $metadata['timeline'];
            }

            $this->setResponse($log->getEvent(), $response);
        }
    }

    public function setResponse(Event $event, mixed $response)
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
     * @return array
     */
    public function getActionResponses(?string $type = null)
    {
        if ($type) {
            return (isset($this->actionResponses[$type])) ? $this->actionResponses[$type] : [];
        }

        return $this->actionResponses;
    }

    /**
     * @return array
     */
    public function getConditionResponses(?string $type = null)
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
}
