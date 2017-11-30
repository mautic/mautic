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
use Symfony\Component\EventDispatcher\Event;

/**
 * Class CampaignTriggerEvent.
 */
class CampaignTriggerEvent extends Event
{
    /**
     * @var Campaign
     */
    protected $campaign;

    /**
     * @var
     */
    protected $triggerCampaign = true;

    /**
     * @param Campaign $campaign
     */
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
