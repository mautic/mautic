<?php

namespace Mautic\CampaignBundle\Event;

use Mautic\CampaignBundle\Entity\Campaign;
use Symfony\Component\EventDispatcher\Event;

class CampaignTriggerEvent extends Event
{
    /**
     * @var Campaign
     */
    protected $campaign;

    /**
     * @var bool
     */
    protected $triggerCampaign = true;

    public function __construct(Campaign $campaign)
    {
        $this->campaign = $campaign;
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
     * @return bool
     */
    public function shouldTrigger()
    {
        return $this->triggerCampaign;
    }

    /**
     * Do not trigger this campaign.
     */
    public function doNotTrigger()
    {
        $this->triggerCampaign = false;

        $this->stopPropagation();
    }
}
