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
 * Class ModuleDetailEvent
 *
 * @package Mautic\DashboardBundle\Event
 */
class ModuleDetailEvent extends CommonEvent
{
    protected $config;
    protected $type;

    /**
     * Set the module type
     *
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * Get the module type
     *
     * @return string $type
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set the module config
     *
     * @param array $config
     */
    public function setConfig(array $form)
    {
        $this->config = $config;
    }

    /**
     * Returns the module detail configuration
     *
     * @param array $config
     */
    public function getConfig()
    {
        return $this->config;
    }
}
