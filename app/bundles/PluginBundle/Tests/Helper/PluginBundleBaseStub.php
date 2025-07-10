<?php

namespace Mautic\PluginBundle\Tests\Helper;

use Doctrine\DBAL\Schema\Schema;
use Mautic\PluginBundle\Entity\Plugin;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * A stub Base Bundle class which implements stub methods for testing purposes.
 */
abstract class PluginBundleBaseStub extends Bundle
{
    public static function onPluginInstall(Plugin $plugin, $metadata = null, $installedSchema = null): void
    {
    }

    /**
     * Called by PluginController::reloadAction when the addon version does not match what's installed.
     */
    public static function onPluginUpdate(Plugin $plugin, $metadata = null, Schema $installedSchema = null)
    {
    }
}
