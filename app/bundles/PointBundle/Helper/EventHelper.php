<?php

namespace Mautic\PointBundle\Helper;

use Mautic\LeadBundle\Entity\Lead;

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

        // only initiate once per lead per type
        if (empty($initiated[$lead->getId()][$action['type']])) {
            if (!empty($action['properties']['delta'])) {
                $pointsChange = $action['properties']['delta'];
            }
        }

        return $pointsChange;
    }
}
