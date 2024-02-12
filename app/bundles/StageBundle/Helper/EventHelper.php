<?php

namespace Mautic\StageBundle\Helper;

use Mautic\LeadBundle\Entity\Lead;

class EventHelper
{
    /**
     * @param Lead  $lead
     * @param array $action
     */
    public static function engageStageAction($lead, $action): int
    {
        static $initiated = [];

        return 0;
    }
}
