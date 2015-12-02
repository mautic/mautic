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
use Mautic\DashboardBundle\Entity\Module;

/**
 * Class ModuleTypeListEvent
 *
 * @package Mautic\DashboardBundle\Event
 */
class ModuleTypeListEvent extends CommonEvent
{
    protected $moduleTypes = array();

    /**
     * Adds a new module type to the module types list
     *
     * @param  string $moduleType
     * @param  string $bundle name (module category)
     */
    public function addType($moduleType, $bundle = 'others')
    {
        $bundle = 'mautic.' . $bundle . '.dashboard.modules';

        if (!isset($this->moduleTypes[$bundle])) {
            $this->moduleTypes[$bundle] = array();
        }

        $this->moduleTypes[$bundle][$moduleType] = $bundle . '.' . $moduleType;
    }

    /**
     * Returns the array of module types
     *
     * @return array $moduleTypes
     */
    public function getTypes()
    {
        return $this->moduleTypes;
    }
}
