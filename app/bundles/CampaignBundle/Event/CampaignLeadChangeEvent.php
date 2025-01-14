<?php

namespace Mautic\CampaignBundle\Event;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\LeadBundle\Entity\Lead;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class CampaignLeadChangeEvent.
 */
class CampaignLeadChangeEvent extends Event
{
    /**
     * @var Campaign
     */
    private $campaign;

    /**
     * @var Lead
     */
    private $lead;

    /**
     * @var array
     */
    private $leads = [];

    /**
     * @var string
     */
    private $action;

    /**
     * CampaignLeadChangeEvent constructor.
     *
     * @param $leads
     * @param $action
     */
    public function __construct(Campaign $campaign, $leads, $action)
    {
        $this->campaign = $campaign;
        if (is_array($leads)) {
            $this->leads = $leads;
        } else {
            $this->lead = $leads;
        }
        $this->action = $action;
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
     *
     * @return bool
     */
    public function wasRemoved()
    {
        return 'removed' == $this->action;
    }

    /**
     * Lead was added to the campaign.
     *
     * @return bool
     */
    public function wasAdded()
    {
        return 'added' == $this->action;
    }
}
