<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PluginBundle\Helper;

use Mautic\CoreBundle\Factory\MauticFactory;

/**
 * Caution: none of the methods persist data.
 */
class ReloadHelper
{
    /**
     * @var MauticFactory
     */
    private $factory;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * Disables plugins that are in the database but are missing in the filesystem.
     *
     * @param array $allPlugins
     * @param array $installedPlugins
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
     * @param array $allPlugins
     * @param array $installedPlugins
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
     * @param array $allPlugins
     * @param array $installedPlugins
     * @param array $pluginMetadata
     * @param array $installedPluginsSchemas
     *
     * @return array
     */
    public function updatePlugins(array $allPlugins, array $installedPlugins, array $pluginMetadata, array $installedPluginsSchemas)
    {
        $updatedPlugins = [];

        foreach ($installedPlugins as $bundle => $plugin) {
            if (isset($allPlugins[$bundle])) {
                $file = $allPlugins[$bundle]['directory'].'/Config/config.php';

                if (!file_exists($file)) {
                    continue;
                }

                /** @var array $details */
                $details = include $file;

                //compare versions to see if an update is necessary
                $version = isset($details['version']) ? $details['version'] : '';
                if (!empty($version) && version_compare($plugin->getVersion(), $version) == -1) {
                    ++$updated;

                    //call the update callback
                    $callback = $allPlugins[$bundle]['bundleClass'];
                    $metadata = (isset($pluginMetadata[$allPlugins[$bundle]['namespace']]))
                        ? $pluginMetadata[$allPlugins[$bundle]['namespace']] : null;
                    $installedSchema = (isset($installedPluginsSchemas[$allPlugins[$bundle]['namespace']]))
                        ? $installedPluginsSchemas[$allPlugins[$bundle]['namespace']] : null;

                    $callback::onPluginUpdate($plugin, $this->factory, $metadata, $installedSchema);

                    unset($metadata, $installedSchema);

                    $updatedPlugins[$plugin->getBundle()] = $plugin;
                }

                $plugin->setVersion($version);

                $plugin->setName(
                    isset($details['name']) ? $details['name'] : $allPlugins[$bundle]['base']
                );

                if (isset($details['description'])) {
                    $plugin->setDescription($details['description']);
                }

                if (isset($details['author'])) {
                    $plugin->setAuthor($details['author']);
                }
            }
        }

        return $updatedPlugins;
    }

    /**
     * Installs plugins that does not exist in the database yet.
     *
     * @param array $allPlugins
     * @param array $installedPlugins
     * @param array $pluginMetadata
     * @param array $installedPluginsSchemas
     *
     * @return array
     */
    public function installPlugins(array $allPlugins, array $installedPlugins, array $pluginMetadata, array $installedPluginsSchemas)
    {
        $installedPlugins = [];

        foreach ($installedPlugins as $bundle => $plugin) {
            if (!isset($allPlugins[$bundle])) {
                $entity = new Plugin();
                $entity->setBundle($plugin['bundle']);

                $file = $plugin['directory'].'/Config/config.php';

                //update details of the bundle
                if (file_exists($file)) {
                    $details = include $file;

                    if (isset($details['version'])) {
                        $entity->setVersion($details['version']);
                    }

                    $entity->setName(
                        isset($details['name']) ? $details['name'] : $plugin['base']
                    );

                    if (isset($details['description'])) {
                        $entity->setDescription($details['description']);
                    }

                    if (isset($details['author'])) {
                        $entity->setAuthor($details['author']);
                    }
                }

                // Call the install callback
                $callback        = $plugin['bundleClass'];
                $metadata        = (isset($pluginMetadata[$plugin['namespace']])) ? $pluginMetadata[$plugin['namespace']] : null;
                $installedSchema = null;

                if (isset($installedPluginsSchemas[$plugin['namespace']]) && count($installedPluginsSchemas[$plugin['namespace']]->getTables()) !== 0) {
                    $installedSchema = true;
                }

                $callback::onPluginInstall($entity, $this->factory, $metadata, $installedSchema);

                $installedPlugins[$entity->getBundle()] = $entity;
            }
        }

        return $installedPlugins;
    }
}
