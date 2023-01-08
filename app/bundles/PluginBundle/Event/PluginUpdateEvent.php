<?php

declare(strict_types=1);

namespace Mautic\PluginBundle\Event;

use Mautic\PluginBundle\Entity\Plugin;
use Symfony\Contracts\EventDispatcher\Event;

class PluginUpdateEvent extends Event
{
    private Plugin $plugin;
    private string $oldVersion;

    public function __construct(Plugin $plugin, string $oldVersion)
    {
        $this->plugin     = $plugin;
        $this->oldVersion = $oldVersion;
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
