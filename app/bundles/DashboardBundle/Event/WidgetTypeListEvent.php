<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\DashboardBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\DashboardBundle\Entity\Widget;

/**
 * Class WidgetTypeListEvent
 *
 * @package Mautic\DashboardBundle\Event
 */
class WidgetTypeListEvent extends CommonEvent
{
    protected $widgetTypes = array();

    /**
     * Adds a new widget type to the widget types list
     *
     * @param  string $widgetType
     * @param  string $bundle name (widget category)
     */
    public function addType($widgetType, $bundle = 'others')
    {
        $bundle = 'mautic.' . $bundle . '.dashboard.widgets';

        if (!isset($this->widgetTypes[$bundle])) {
            $this->widgetTypes[$bundle] = array();
        }

        $this->widgetTypes[$bundle][$widgetType] = $bundle . '.' . $widgetType;
    }

    /**
     * Returns the array of widget types
     *
     * @return array $widgetTypes
     */
    public function getTypes()
    {
        return $this->widgetTypes;
    }
}
