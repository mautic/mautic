<?php

namespace Mautic\PluginBundle\Helper;

use Mautic\CoreBundle\Factory\MauticFactory;
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
    /**
     * @var MauticFactory
     */
    private $factory;

    private EventDispatcherInterface $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher, MauticFactory $factory)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->factory         = $factory;
    }

    /**
     * Disables plugins that are in the database but are missing in the filesystem.
     *
     * @return array
     */
    public function disableMissingPlugins(array $allPlugins, array $installedPlugins)
    {
        $disabledPlugins = [];

        foreach ($installedPlugins as $plugin) {
            if (!isset($allPlugins[$plugin->getBundle()]) && !$plugin->getIsMissing()) {
                //files are no longer found
                $plugin->setIsMissing(true);
                $disabledPlugins[$plugin->getBundle()] = $plugin;
            }
        }

        return $disabledPlugins;
    }

    /**
     * Re-enables plugins that were disabled because they were missing in the filesystem
     * but appeared in it again.
     *
     * @return array
     */
    public function enableFoundPlugins(array $allPlugins, array $installedPlugins)
    {
        $enabledPlugins = [];

        foreach ($installedPlugins as $plugin) {
            if (isset($allPlugins[$plugin->getBundle()]) && $plugin->getIsMissing()) {
                //files are no longer found
                $plugin->setIsMissing(false);
                $enabledPlugins[$plugin->getBundle()] = $plugin;
            }
        }

        return $enabledPlugins;
    }

    /**
     * Updates plugins that exist in the filesystem and in the database and their version changed.
     *
     * @return array
     */
    public function updatePlugins(array $allPlugins, array $installedPlugins, array $pluginMetadata, array $installedPluginsSchemas)
    {
        $updatedPlugins = [];

        foreach ($installedPlugins as $bundle => $plugin) {
            if (isset($allPlugins[$bundle])) {
                $pluginConfig = $allPlugins[$bundle];
                $oldVersion   = $plugin->getVersion();
                $plugin       = $this->mapConfigToPluginEntity($plugin, $pluginConfig);

                //compare versions to see if an update is necessary
                if (!empty($oldVersion) && -1 == version_compare($oldVersion, $plugin->getVersion())) {
                    //call the update callback
                    $callback = $pluginConfig['bundleClass'];
                    $metadata = isset($pluginMetadata[$pluginConfig['namespace']])
                        ? $pluginMetadata[$pluginConfig['namespace']] : null;
                    $installedSchema = isset($installedPluginsSchemas[$pluginConfig['namespace']])
                        ? $installedPluginsSchemas[$allPlugins[$bundle]['namespace']] : null;

                    $callback::onPluginUpdate($plugin, $this->factory, $metadata, $installedSchema);

                    $event = new PluginUpdateEvent($plugin, $oldVersion);

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
     * @return array
     */
    public function installPlugins(array $allPlugins, array $existingPlugins, array $pluginMetadata, array $installedPluginsSchemas)
    {
        $installedPlugins = [];

        foreach ($allPlugins as $bundle => $pluginConfig) {
            if (!isset($existingPlugins[$bundle])) {
                $entity = $this->mapConfigToPluginEntity(new Plugin(), $pluginConfig);

                // Call the install callback
                $callback        = $pluginConfig['bundleClass'];
                $metadata        = isset($pluginMetadata[$pluginConfig['namespace']]) ? $pluginMetadata[$pluginConfig['namespace']] : null;
                $installedSchema = null;

                if (isset($installedPluginsSchemas[$pluginConfig['namespace']]) && 0 !== count($installedPluginsSchemas[$pluginConfig['namespace']]->getTables())) {
                    $installedSchema = true;
                }

                $callback::onPluginInstall($entity, $this->factory, $metadata, $installedSchema);

                $event = new PluginInstallEvent($entity);

                $this->eventDispatcher->dispatch($event, PluginEvents::ON_PLUGIN_INSTALL);

                $installedPlugins[$entity->getBundle()] = $entity;
            }
        }

        return $installedPlugins;
    }

    /**
     * @return Plugin
     */
    private function mapConfigToPluginEntity(Plugin $plugin, array $config)
    {
        $plugin->setBundle($config['bundle']);

        if (isset($config['config'])) {
            $details = $config['config'];

            if (isset($details['version'])) {
                $plugin->setVersion($details['version']);
            }

            $plugin->setName(
                isset($details['name']) ? $details['name'] : $config['base']
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
