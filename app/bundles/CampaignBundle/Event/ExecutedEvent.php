<?php

namespace Mautic\CampaignBundle\Event;

use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\AbstractEventAccessor;

class ExecutedEvent extends \Symfony\Contracts\EventDispatcher\Event
{
    public function __construct(
        private AbstractEventAccessor $config,
        private LeadEventLog $log,
    ) {
    }

    /**
     * @return AbstractEventAccessor
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @return LeadEventLog
     */
    public function getLog()
    {
        return $this->log;
    }
}
