<?php

declare(strict_types=1);

namespace Mautic\PluginBundle\Event;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\ORM\Mapping\ClassMetadata;
use Mautic\PluginBundle\Entity\Plugin;
use Symfony\Contracts\EventDispatcher\Event;

class PluginUpdateEvent extends Event
{
    /**
     * @param array<class-string, ClassMetadata>|null $metadata null value is when the plugin does not have Entities (an Entity directory)
     */
    public function __construct(
        private Plugin $plugin,
        private string $oldVersion,
        private ?array $metadata,
        private ?Schema $installedSchema,
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

    /**
     * @return array<class-string, ClassMetadata>|null
     */
    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function getInstalledSchema(): ?Schema
    {
        return $this->installedSchema;
    }

    public function checkContext(string $pluginName): bool
    {
        return $pluginName === $this->plugin->getName();
    }
}
