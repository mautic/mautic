<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

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
     * @var string
     */
    private $action;

    /**
     * @param Campaign $campaign
     * @param Lead     $lead
     * @param string   $action
     */
    public function __construct(Campaign &$campaign, Lead $lead, $action)
    {
        $this->campaign = $campaign;
        $this->lead     = $lead;
        $this->action   = $action;
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
        return $this->action == 'removed';
    }

    /**
     * Lead was added to the campaign.
     *
     * @return bool
     */
    public function wasAdded()
    {
        return $this->action == 'added';
    }
}
