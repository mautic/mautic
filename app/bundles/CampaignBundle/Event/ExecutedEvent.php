<?php

namespace Mautic\CampaignBundle\Event;

use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\AbstractEventAccessor;

class ExecutedEvent extends \Symfony\Contracts\EventDispatcher\Event
{
    private \Mautic\CampaignBundle\EventCollector\Accessor\Event\AbstractEventAccessor $config;

    private \Mautic\CampaignBundle\Entity\LeadEventLog $log;

    public function __construct(AbstractEventAccessor $config, LeadEventLog $log)
    {
        $this->config = $config;
        $this->log    = $log;
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
