<?php

namespace Mautic\CampaignBundle\Membership;

use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Event\CampaignLeadChangeEvent;
use Mautic\LeadBundle\Entity\Lead;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class EventDispatcher
{
    public function __construct(
        private EventDispatcherInterface $dispatcher
    ) {
    }

    /**
     * @param string $action
     */
    public function dispatchMembershipChange(Lead $contact, Campaign $campaign, $action): void
    {
        $this->dispatcher->dispatch(
            new CampaignLeadChangeEvent($campaign, $contact, $action),
            CampaignEvents::CAMPAIGN_ON_LEADCHANGE
        );
    }

    public function dispatchBatchMembershipChange(array $contacts, Campaign $campaign, $action): void
    {
        $this->dispatcher->dispatch(
            new CampaignLeadChangeEvent($campaign, $contacts, $action),
            CampaignEvents::LEAD_CAMPAIGN_BATCH_CHANGE
        );
    }
}
