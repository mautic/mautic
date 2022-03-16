<?php

namespace Mautic\CampaignBundle\Event;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\AbstractEventAccessor;
use Mautic\CampaignBundle\Executioner\Result\EvaluatedContacts;
use Symfony\Component\EventDispatcher\Event;

class DecisionResultsEvent extends Event
{
    /**
     * @var AbstractEventAccessor
     */
    private $eventConfig;

    /**
     * @var ArrayCollection|LeadEventLog[]
     */
    private $eventLogs;

    /**
     * @var EvaluatedContacts
     */
    private $evaluatedContacts;

    /**
     * DecisionResultsEvent constructor.
     */
    public function __construct(AbstractEventAccessor $config, ArrayCollection $logs, EvaluatedContacts $evaluatedContacts)
    {
        $this->eventConfig       = $config;
        $this->eventLogs         = $logs;
        $this->evaluatedContacts = $evaluatedContacts;
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
