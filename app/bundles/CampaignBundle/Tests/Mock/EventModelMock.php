<?php

namespace Mautic\CampaignBundle\Tests\Mock;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Model\EventModel;

/**
 * Class EventModelMock.
 */
class EventModelMock extends EventModel
{
    protected $repository               = null;
    public $executeEvent                = false;
    public $invokeEventCallbackResponse = true;

    public function getRepository()
    {
        if ($this->repository) {
            return $this->repository;
        }

        return new RepositoryMock();
    }

    public function setRepository($repository)
    {
        $this->repository = $repository;
    }

    public function setCampaignModel($model)
    {
        $this->campaignModel = $model;
    }

    public function setLeadModel($model)
    {
        $this->leadModel = $model;
    }

    public function executeEvent(
        $event,
        $campaign,
        $lead,
        $eventSettings = null,
        $allowNegative = false,
        \DateTime $parentTriggeredDate = null,
        $eventTriggerDate = null,
        $logExists = false,
        &$evaluatedEventCount = 0,
        &$executedEventCount = 0,
        &$totalEventCount = 0
    ) {
        if ($this->executeEvent) {
            return parent::executeEvent($event,
                $campaign,
                $lead,
                $eventSettings,
                $allowNegative,
                $parentTriggeredDate,
                $eventTriggerDate,
                $logExists,
                $evaluatedEventCount,
                $executedEventCount,
                $totalEventCount
            );
        }

        ++$evaluatedEventCount;
        ++$totalEventCount;
        ++$executedEventCount;

        return true;
    }

    public function triggerConditions(Campaign $campaign, &$evaluatedEventCount = 0, &$executedEventCount = 0, &$totalEventCount = 0)
    {
    }

    public function invokeEventCallback($event, $settings, $lead = null, $eventDetails = null, $systemTriggered = false, LeadEventLog $log = null)
    {
        return $this->invokeEventCallbackResponse;
    }
}
