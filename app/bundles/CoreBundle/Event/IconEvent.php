<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Event;

use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class IconEvent.
 */
class IconEvent extends Event
{
    /**
     * @var array
     */
    protected $icons = [];

    /**
     * @var CorePermissions
     */
    protected $security;

    /**
     * @param CorePermissions $security
     */
    public function __construct(CorePermissions $security)
    {
        $this->security = $security;
    }

    /**
     * @return CorePermissions
     */
    public function getSecurity()
    {
        return $this->security;
    }

    /**
     * @param string $type
     * @param string $icon
     */
    public function addIcon($type, $icon)
    {
        $this->icons[$type] = $icon;
    }

    /**
     * Return the icons.
     *
     * @return array
     */
    public function getIcons()
    {
        return $this->icons;
    }

    /**
     * @param array $icons
     */
    public function setIcons(array $icons)
    {
        $this->icons = $icons;
    }
}
