<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Event;

use Mautic\CampaignBundle\Entity\Event;

/**
 * Trait EventArrayTrait.
 *
 * @deprecated 2.13.0; used for BC support. To be removed in 3.0
 */
trait EventArrayTrait
{
    /**
     * @param Event $event
     *
     * @return array
     */
    private function getEventArray(Event $event)
    {
        $eventArray = $event->convertToArray();
        $campaign   = $event->getCampaign();

        $eventArray['campaign'] = [
            'id'        => $campaign->getId(),
            'name'      => $campaign->getName(),
            'createdBy' => $campaign->getCreatedBy(),
        ];

        return $eventArray;
    }
}
