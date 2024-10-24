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
            if (!empty($action['points'])) {
                $pointsChange                               = $action['points'];
                $initiated[$lead->getId()][$action['type']] = true;
            }
        }

        return $pointsChange;
    }
}
