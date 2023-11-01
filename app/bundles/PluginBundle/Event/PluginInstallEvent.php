<?php

declare(strict_types=1);

namespace Mautic\PluginBundle\Event;

use Mautic\PluginBundle\Entity\Plugin;
use Symfony\Contracts\EventDispatcher\Event;

class PluginInstallEvent extends Event
{
    private Plugin $plugin;

    public function __construct(Plugin $plugin)
    {
        $this->plugin = $plugin;
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
