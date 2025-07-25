<?php

declare(strict_types=1);

namespace Mautic\PluginBundle\Event;

use Doctrine\ORM\Mapping\ClassMetadata;
use Mautic\PluginBundle\Entity\Plugin;
use Symfony\Contracts\EventDispatcher\Event;

class PluginInstallEvent extends Event
{
    /**
     * @param array<class-string, ClassMetadata>|null $metadata
     */
    public function __construct(
        private Plugin $plugin,
        private ?array $metadata,
        private ?bool $installedSchema,
    ) {
    }

    public function getPlugin(): Plugin
    {
        return $this->plugin;
    }

    /**
     * @return array<class-string, ClassMetadata>|null
     */
    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function getInstalledSchema(): ?bool
    {
        return $this->installedSchema;
    }

    public function checkContext(string $pluginName): bool
    {
        return $pluginName === $this->plugin->getName();
    }
}
