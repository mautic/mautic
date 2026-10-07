<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

final class IconEvent extends Event
{
    /**
     * @var array
     */
    private $icons = [];

    /**
     * @param string $icon
     */
    public function addIcon(string $type, $icon): void
    {
        $this->icons[$type] = $icon;
    }

    /**
     * Return the icons.
     */
    public function getIcons(): array
    {
        return $this->icons;
    }

    public function setIcons(array $icons): void
    {
        $this->icons = $icons;
    }
}
