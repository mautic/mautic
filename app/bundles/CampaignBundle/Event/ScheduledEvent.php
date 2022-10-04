<?php

namespace Mautic\CampaignBundle\Event;

use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\AbstractEventAccessor;

class ScheduledEvent extends CampaignScheduledEvent
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
    private $isReschedule;

    /**
     * ScheduledEvent constructor.
     *
     * @param bool $isReschedule
     */
    public function __construct(AbstractEventAccessor $config, LeadEventLog $log, $isReschedule = false)
    {
        $this->eventConfig  = $config;
        $this->eventLog     = $log;
        $this->isReschedule = $isReschedule;

        // @deprecated support for pre 2.13.0; to be removed in 3.0
        parent::__construct(
            [
                'eventSettings'   => $config->getConfig(),
                'eventDetails'    => null,
                'event'           => $log->getEvent(),
                'lead'            => $log->getLead(),
                'systemTriggered' => true,
                'dateScheduled'   => $log->getTriggerDate(),
            ],
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
     * @return bool
     */
    public function isReschedule()
    {
        return $this->isReschedule;
    }
}
