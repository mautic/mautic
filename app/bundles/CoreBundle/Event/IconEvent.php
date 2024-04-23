<?php

namespace Mautic\CoreBundle\Event;

use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Symfony\Contracts\EventDispatcher\Event;

class IconEvent extends Event
{
    /**
     * @var array
     */
    protected $icons = [];

    public function __construct(
        protected CorePermissions $security
    ) {
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
    public function addIcon($type, $icon): void
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

    public function setIcons(array $icons): void
    {
        $this->icons = $icons;
    }
}
