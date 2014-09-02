<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PointBundle\Helper;

class EventHelper
{
    public static function engagePointAction($lead, $action)
    {
        static $initiated = array();

        $scoreChange = 0;

        //only initiate once per lead per type
        if (empty($initiated[$lead->getId()][$action['type']])) {
            if (!empty($action['properties']['delta'])) {
                $scoreChange = $action['properties']['delta'];
            }
        }

        return $scoreChange;
    }
}