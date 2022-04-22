<?php

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

    public function setIcons(array $icons)
    {
        $this->icons = $icons;
    }
}
