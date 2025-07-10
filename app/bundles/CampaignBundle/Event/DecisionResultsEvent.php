<?php

namespace Mautic\CampaignBundle\Event;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\AbstractEventAccessor;
use Mautic\CampaignBundle\Executioner\Result\EvaluatedContacts;
use Symfony\Contracts\EventDispatcher\Event;

class DecisionResultsEvent extends Event
{
    /**
     * @param ArrayCollection<int, LeadEventLog> $eventLogs
     */
    public function __construct(
        private readonly AbstractEventAccessor $eventConfig,
        private readonly ArrayCollection $eventLogs,
        private readonly EvaluatedContacts $evaluatedContacts,
    ) {
    }

    /**
     * @return AbstractEventAccessor
     */
    public function getEventConfig()
    {
        return $this->eventConfig;
    }

    /**
     * @return ArrayCollection|LeadEventLog[]
     */
    public function getLogs()
    {
        return $this->eventLogs;
    }

    /**
     * @return EvaluatedContacts
     */
    public function getEvaluatedContacts()
    {
        return $this->evaluatedContacts;
    }
}
