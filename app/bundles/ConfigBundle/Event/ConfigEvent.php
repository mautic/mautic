<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ConfigBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;

/**
 * Class ConfigEvent
 *
 * @package Mautic\ConfigBundle\Event
 */
class ConfigEvent extends CommonEvent
{
    /**
     * @param arry $values
     */
    public function __construct(&$values)
    {
        $this->values  =& $values;
    }

    /**
     * Returns the values array
     *
     * @return array
     */
    public function getValues()
    {
        return $this->values;
    }
}
