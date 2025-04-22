<?php

declare(strict_types=1);

namespace Mautic\PluginBundle\Event;

use Mautic\PluginBundle\Entity\Plugin;
use Symfony\Contracts\EventDispatcher\Event;

class PluginUninstallEvent extends Event
{
    public function __construct(
        private Plugin $plugin,
    ) {
    }

    public function getPlugin(): Plugin
    {
        return $this->plugin;
    }

    public function checkContext(string $pluginName): bool
    {
        return $pluginName === $this->plugin->getName();
    }
}
