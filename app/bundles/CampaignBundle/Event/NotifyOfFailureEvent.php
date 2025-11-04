<?php

namespace Mautic\CampaignBundle\Event;

use Mautic\CampaignBundle\Entity\Event as CampaignEvent;
use Mautic\LeadBundle\Entity\Lead;
use Symfony\Contracts\EventDispatcher\Event;

class NotifyOfFailureEvent extends Event
{
    public function __construct(private Lead $lead, private CampaignEvent $failedEvent)
    {
    }

    public function getLead(): Lead
    {
        return $this->lead;
    }

    public function getFailedEvent(): CampaignEvent
    {
        return $this->failedEvent;
    }
}
