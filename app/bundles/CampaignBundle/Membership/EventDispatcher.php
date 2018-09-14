<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

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
     *
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param Lead     $contact
     * @param Campaign $campaign
     * @param string   $action
     */
    public function dispatchMembershipChange(Lead $contact, Campaign $campaign, $action)
    {
        $this->dispatcher->dispatch(
            CampaignEvents::CAMPAIGN_ON_LEADCHANGE,
            new CampaignLeadChangeEvent($campaign, $contact, $action)
        );
    }

    /**
     * @param array    $contacts
     * @param Campaign $campaign
     * @param          $action
     */
    public function dispatchBatchMembershipChange(array $contacts, Campaign $campaign, $action)
    {
        $this->dispatcher->dispatch(
            CampaignEvents::LEAD_CAMPAIGN_BATCH_CHANGE,
            new CampaignLeadChangeEvent($campaign, $contacts, $action)
        );
    }
}
