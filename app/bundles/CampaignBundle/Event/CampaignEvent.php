<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\CampaignBundle\Entity\Campaign;

/**
 * Class CampaignEvent
 *
 * @package Mautic\CampaignBundle\Event
 */
class CampaignEvent extends CommonEvent
{
    /**
     * @param Campaign $campaign
     * @param bool $isNew
     */
    public function __construct(Campaign &$campaign, $isNew = false)
    {
        $this->entity  =& $campaign;
        $this->isNew = $isNew;
    }

    /**
     * Returns the Campaign entity
     *
     * @return Campaign
     */
    public function getCampaign()
    {
        return $this->entity;
    }

    /**
     * Sets the Campaign entity
     *
     * @param Campaign $campaign
     */
    public function setCampaign(Campaign $campaign)
    {
        $this->entity = $campaign;
    }
}