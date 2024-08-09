<?php

declare(strict_types=1);

namespace Mautic\PluginBundle\Event;

use Mautic\PluginBundle\Entity\Plugin;
use Symfony\Contracts\EventDispatcher\Event;

class PluginUpdateEvent extends Event
{
    public function __construct(
        private Plugin $plugin,
        private string $oldVersion
    ) {
    }

    public function getPlugin(): Plugin
    {
        return $this->plugin;
    }

    public function getOldVersion(): string
    {
        return $this->oldVersion;
    }

    public function checkContext(string $pluginName): bool
    {
        return $pluginName === $this->plugin->getName();
    }
}
