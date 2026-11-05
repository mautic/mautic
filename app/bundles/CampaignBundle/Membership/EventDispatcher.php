<?php

namespace Mautic\CampaignBundle\Membership;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Event\CampaignBatchLeadChangeEvent;
use Mautic\CampaignBundle\Event\CampaignSingleLeadChangeEvent;
use Mautic\LeadBundle\Entity\Lead;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final readonly class EventDispatcher
{
    public function __construct(
        private EventDispatcherInterface $dispatcher,
    ) {
    }

    /**
     * @param string $action
     */
    public function dispatchMembershipChange(Lead $contact, Campaign $campaign, $action): void
    {
        $this->dispatcher->dispatch(
            new CampaignSingleLeadChangeEvent($campaign, $contact, $action)
        );
    }

    public function dispatchBatchMembershipChange(array $contacts, Campaign $campaign, $action): void
    {
        $this->dispatcher->dispatch(
            new CampaignBatchLeadChangeEvent($campaign, $contacts, $action)
        );
    }
}
