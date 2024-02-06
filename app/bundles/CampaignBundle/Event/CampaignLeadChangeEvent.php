<?php

namespace Mautic\CampaignBundle\Event;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\LeadBundle\Entity\Lead;
use Symfony\Contracts\EventDispatcher\Event;

class CampaignLeadChangeEvent extends Event
{
    /**
     * @var Lead
     */
    private $lead;

    private array $leads = [];

    /**
     * @param string $action
     */
    public function __construct(
        private Campaign $campaign,
        $leads,
        private $action
    ) {
        if (is_array($leads)) {
            $this->leads = $leads;
        } else {
            $this->lead = $leads;
        }
    }

    /**
     * Returns the Campaign entity.
     *
     * @return Campaign
     */
    public function getCampaign()
    {
        return $this->campaign;
    }

    /**
     * Returns the Lead entity.
     *
     * @return Lead
     */
    public function getLead()
    {
        return $this->lead;
    }

    /**
     * If this is a batch event, return array of leads.
     *
     * @return array
     */
    public function getLeads()
    {
        return $this->leads;
    }

    /**
     * Returns added or removed.
     *
     * @return mixed
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Lead was removed from the campaign.
     */
    public function wasRemoved(): bool
    {
        return 'removed' == $this->action;
    }

    /**
     * Lead was added to the campaign.
     */
    public function wasAdded(): bool
    {
        return 'added' == $this->action;
    }
}
