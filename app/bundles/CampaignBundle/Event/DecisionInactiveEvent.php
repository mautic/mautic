<?php

namespace Mautic\CampaignBundle\Event;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CampaignBundle\Entity\Campaign;
use Symfony\Contracts\EventDispatcher\Event;

class DecisionInactiveEvent extends Event
{
    public function __construct(private Campaign $campaign, private \Mautic\CampaignBundle\Entity\Event $event, private ArrayCollection $contacts)
    {
    }

    public function getCampaign(): Campaign
    {
        return $this->campaign;
    }

    public function getContacts(): ArrayCollection
    {
        return $this->contacts;
    }

    public function getEvent(): \Mautic\CampaignBundle\Entity\Event
    {
        return $this->event;
    }
}
