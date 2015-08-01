<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PluginBundle\Bundle;

use Mautic\PluginBundle\Entity\Addon;
use Mautic\PluginBundle\Entity\Plugin;
use Mautic\CoreBundle\Factory\MauticFactory;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Base Bundle class which should be extended by addon bundles
 */
abstract class PluginBundleBase extends Bundle
{
    /**
     * Called by PluginController::reloadAction when adding a new addon that's not already installed
     *
     * @param MauticFactory $factory
     */
    static public function onPluginInstall(MauticFactory $factory)
    {
        // BC support; @deprecated 1.1.4; to be removed in 2.0
        if (method_exists(get_called_class(), 'onInstall')) {
            self::onInstall($factory);
        }
    }

    /**
     * Called by PluginController::reloadAction when the addon version does not match what's installed
     *
     * @param Plugin        $plugin
     * @param MauticFactory $factory
     */
    static public function onPluginUpdate(Plugin $plugin, MauticFactory $factory)
    {
        // BC support; @deprecated 1.1.4; to be removed in 2.0
        if (method_exists(get_called_class(), 'onUpdate')) {
            // Create a bogus Addon
            $addon = new Addon();
            $addon->setAuthor($plugin->getAuthor())
                ->setBundle($plugin->getBundle())
                ->setDescription($plugin->getDescription())
                ->setId($plugin->getId())
                ->setIntegrations($plugin->getIntegrations())
                ->setIsMissing($plugin->getIsMissing())
                ->setName($plugin->getName())
                ->setVersion($plugin->getVersion());

            self::onUpdate($addon, $factory);
        }
    }

    /**
     * Not used yet :-)
     *
     * @param Plugin        $plugin
     * @param MauticFactory $factory
     */
    static public function onPluginUninstall(Plugin $plugin, MauticFactory $factory)
    {

    }
}
