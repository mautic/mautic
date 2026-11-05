<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Event;

use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\AbstractEventAccessor;
use Symfony\Contracts\EventDispatcher\Event;

final class FailedEvent extends Event
{
    public function __construct(
        private readonly AbstractEventAccessor $config,
        private readonly LeadEventLog $log,
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
