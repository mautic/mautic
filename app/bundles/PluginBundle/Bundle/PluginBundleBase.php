<?php

namespace Mautic\PluginBundle\Bundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Base Bundle class which should be extended by addon bundles.
 */
abstract class PluginBundleBase extends Bundle
{
    /**
     * @param null $metadata
     * @param null $installedSchema
     *
     * @throws \Exception
     *
     * @deprecated To be removed in 5.0. Listen to PluginEvents::ON_PLUGIN_INSTALL instead
     */
    public static function onPluginInstall($plugin, $factory, $metadata = null, $installedSchema = null)
    {
    }

    /**
     * @param null $metadata
     * @param null $installedSchema
     *
     * @throws \Exception
     *
     * @deprecated To be removed in 5.0. Listen to PluginEvents::ON_PLUGIN_UPDATE instead
     */
    public static function onPluginUpdate($plugin, $factory, $metadata = null, $installedSchema = null)
    {
    }

    /**
     * Not used yet :-).
     *
     * @param null $metadata
     *
     * @deprecated To be removed in 5.0. Listen to PluginEvents::ON_PLUGIN_UNINSTALL instead
     */
    public static function onPluginUninstall($plugin, $factory, $metadata = null)
    {
    }
}
