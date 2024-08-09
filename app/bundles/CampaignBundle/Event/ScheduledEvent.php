<?php

namespace Mautic\CampaignBundle\Event;

use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\AbstractEventAccessor;

class ScheduledEvent extends CampaignScheduledEvent
{
    use ContextTrait;

    /**
     * @param bool $isReschedule
     */
    public function __construct(
        private AbstractEventAccessor $eventConfig,
        private LeadEventLog $eventLog,
        private $isReschedule = false
    ) {
        // @deprecated support for pre 2.13.0; to be removed in 3.0
        parent::__construct(
            [
                'eventSettings'   => $eventConfig->getConfig(),
                'eventDetails'    => null,
                'event'           => $eventLog->getEvent(),
                'lead'            => $eventLog->getLead(),
                'systemTriggered' => true,
                'dateScheduled'   => $eventLog->getTriggerDate(),
            ],
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
     * @return bool
     */
    public function isReschedule()
    {
        return $this->isReschedule;
    }
}
