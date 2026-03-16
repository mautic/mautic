<?php

namespace Mautic\PluginBundle\Helper;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\ORM\Mapping\ClassMetadata;
use Mautic\PluginBundle\Entity\Plugin;
use Mautic\PluginBundle\Event\PluginInstallEvent;
use Mautic\PluginBundle\Event\PluginUpdateEvent;
use Mautic\PluginBundle\PluginEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Caution: none of the methods persist data.
 */
class ReloadHelper
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    /**
     * Disables plugins that are in the database but are missing in the filesystem.
     */
    public function disableMissingPlugins(array $allPlugins, array $installedPlugins): array
    {
        $disabledPlugins = [];

        foreach ($installedPlugins as $plugin) {
            if (!isset($allPlugins[$plugin->getBundle()]) && !$plugin->getIsMissing()) {
                // files are no longer found
                $plugin->setIsMissing(true);
                $disabledPlugins[$plugin->getBundle()] = $plugin;
            }
        }

        return $disabledPlugins;
    }

    /**
     * Re-enables plugins that were disabled because they were missing in the filesystem
     * but appeared in it again.
     */
    public function enableFoundPlugins(array $allPlugins, array $installedPlugins): array
    {
        $enabledPlugins = [];

        foreach ($installedPlugins as $plugin) {
            if (isset($allPlugins[$plugin->getBundle()]) && $plugin->getIsMissing()) {
                // files are no longer found
                $plugin->setIsMissing(false);
                $enabledPlugins[$plugin->getBundle()] = $plugin;
            }
        }

        return $enabledPlugins;
    }

    /**
     * Updates plugins that exist in the filesystem and in the database and their version changed.
     *
     * @param array<string, array<class-string, ClassMetadata>> $pluginMetadata
     * @param array<string, Plugin>                             $installedPlugins
     * @param array<string, Schema>                             $installedPluginsSchemas
     */
    public function updatePlugins(array $allPlugins, array $installedPlugins, array $pluginMetadata, array $installedPluginsSchemas): array
    {
        $updatedPlugins = [];

        foreach ($installedPlugins as $bundle => $plugin) {
            if (isset($allPlugins[$bundle])) {
                $pluginConfig = $allPlugins[$bundle];
                $oldVersion   = $plugin->getVersion();
                $plugin       = $this->mapConfigToPluginEntity($plugin, $pluginConfig);

                // compare versions to see if an update is necessary
                if ((empty($oldVersion) && !empty($plugin->getVersion())) || (!empty($oldVersion) && -1 === version_compare($oldVersion, $plugin->getVersion()))) {
                    $metadata        = $pluginMetadata[$pluginConfig['namespace']] ?? null;
                    $installedSchema = isset($installedPluginsSchemas[$pluginConfig['namespace']])
                        ? $installedPluginsSchemas[$allPlugins[$bundle]['namespace']] : null;

                    $event = new PluginUpdateEvent($plugin, $oldVersion, $metadata, $installedSchema);

                    $this->eventDispatcher->dispatch($event, PluginEvents::ON_PLUGIN_UPDATE);

                    unset($metadata, $installedSchema);

                    $updatedPlugins[$plugin->getBundle()] = $plugin;
                }
            }
        }

        return $updatedPlugins;
    }

    /**
     * Installs plugins that does not exist in the database yet.
     *
     * @param array<string, array<class-string, ClassMetadata>> $pluginMetadata
     */
    public function installPlugins(array $allPlugins, array $existingPlugins, array $pluginMetadata, array $installedPluginsSchemas): array
    {
        $installedPlugins = [];

        foreach ($allPlugins as $bundle => $pluginConfig) {
            if (!isset($existingPlugins[$bundle])) {
                $entity = $this->mapConfigToPluginEntity(new Plugin(), $pluginConfig);

                $metadata        = $pluginMetadata[$pluginConfig['namespace']] ?? null;
                $installedSchema = null;

                if (isset($installedPluginsSchemas[$pluginConfig['namespace']]) && 0 !== count($installedPluginsSchemas[$pluginConfig['namespace']]->getTables())) {
                    $installedSchema = true;
                }

                $event = new PluginInstallEvent($entity, $metadata, $installedSchema);

                $this->eventDispatcher->dispatch($event, PluginEvents::ON_PLUGIN_INSTALL);

                $installedPlugins[$entity->getBundle()] = $entity;
            }
        }

        return $installedPlugins;
    }

    private function mapConfigToPluginEntity(Plugin $plugin, array $config): Plugin
    {
        $plugin->setBundle($config['bundle']);

        if (isset($config['config'])) {
            $details = $config['config'];

            if (isset($details['version'])) {
                $plugin->setVersion($details['version']);
            }

            $plugin->setName(
                $details['name'] ?? $config['base']
            );

            if (isset($details['description'])) {
                $plugin->setDescription($details['description']);
            }

            if (isset($details['author'])) {
                $plugin->setAuthor($details['author']);
            }
        }

        return $plugin;
    }
}
