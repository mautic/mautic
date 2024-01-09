<?php

namespace Mautic\CampaignBundle\Event;

use Mautic\CampaignBundle\Entity\Campaign;
use Symfony\Contracts\EventDispatcher\Event;

class CampaignTriggerEvent extends Event
{
    /**
     * @var bool
     */
    protected $triggerCampaign = true;

    public function __construct(
        protected Campaign $campaign
    ) {
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
    public function doNotTrigger(): void
    {
        $this->triggerCampaign = false;

        $this->stopPropagation();
    }
}
