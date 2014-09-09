<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Helper;

class EventHelper
{
    public static function engageCampaignAction($lead, $action)
    {
        static $initiated = array();

        $campaignsChange = 0;

        //only initiate once per lead per type
        if (empty($initiated[$lead->getId()][$action['type']])) {
            if (!empty($action['properties']['delta'])) {
                $campaignsChange = $action['properties']['delta'];
            }
        }

        return $campaignsChange;
    }
}