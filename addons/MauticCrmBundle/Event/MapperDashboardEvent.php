<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticAddon\MauticCrmBundle\Event;

use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class MapperDashboardEvent
 *
 * @package Mautic\CoreBundle\Event
 */
class MapperDashboardEvent extends Event
{
    /**
     * @var
     */
    protected $applications = array();

    protected $security;

    /**
     */
    public function __construct(CorePermissions $security)
    {
        $this->security = $security;
    }

    /**
     * @return mixed
     */
    public function getSecurity()
    {
        return $this->security;
    }

    /**
     * Add icon
     */
    public function addApplication($config)
    {
        $this->applications[] = $config;
    }

    /**
     * Return the icons
     *
     * @return mixed
     */
    public function getApplications()
    {
        return $this->applications;
    }
}
