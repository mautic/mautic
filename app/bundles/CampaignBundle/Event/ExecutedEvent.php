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

    public function getConfig(): AbstractEventAccessor
    {
        return $this->config;
    }

    public function getLog(): LeadEventLog
    {
        return $this->log;
    }
}
