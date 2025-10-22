<?php

namespace Mautic\CampaignBundle\Event;

use Mautic\CampaignBundle\Entity\Event as CampaignEvent;
use Symfony\Contracts\EventDispatcher\Event;

class NotifyOfUnpublishEvent extends Event
{
    public function __construct(private CampaignEvent $failedEvent)
    {
    }

    public function getFailedEvent(): CampaignEvent
    {
        return $this->failedEvent;
    }
}
