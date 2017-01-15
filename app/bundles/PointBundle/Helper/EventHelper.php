<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PointBundle\Helper;

use Mautic\LeadBundle\Entity\Lead;

/**
 * Class EventHelper.
 */
class EventHelper
{
    /**
     * @param Lead  $lead
     * @param array $action
     *
     * @return int
     */
    public static function engagePointAction($lead, $action)
    {
        static $initiated = [];

        $pointsChange = 0;

        //only initiate once per lead per type
        if (empty($initiated[$lead->getId()][$action['type']])) {
            if (!empty($action['properties']['delta'])) {
                $pointsChange = $action['properties']['delta'];
            }
        }

        return $pointsChange;
    }
}
