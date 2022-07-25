<?php

namespace Mautic\CampaignBundle\Membership;

use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Event\CampaignLeadChangeEvent;
use Mautic\LeadBundle\Entity\Lead;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class EventDispatcher
{
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * EventDispatcher constructor.
     */
    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param string $action
     */
    public function dispatchMembershipChange(Lead $contact, Campaign $campaign, $action)
    {
        $this->dispatcher->dispatch(
            CampaignEvents::CAMPAIGN_ON_LEADCHANGE,
            new CampaignLeadChangeEvent($campaign, $contact, $action)
        );
    }

    /**
     * @param $action
     */
    public function dispatchBatchMembershipChange(array $contacts, Campaign $campaign, $action)
    {
        $this->dispatcher->dispatch(
            CampaignEvents::LEAD_CAMPAIGN_BATCH_CHANGE,
            new CampaignLeadChangeEvent($campaign, $contacts, $action)
        );
    }
}
