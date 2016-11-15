<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\StageBundle\Helper;

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
    public static function engageStageAction($lead, $action)
    {
        static $initiated = [];

        $stagesChange = 0;

        return $stagesChange;
    }
}
