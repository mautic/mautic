<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PluginBundle\Bundle;

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
    static public function onInstall(MauticFactory $factory)
    {

    }

    /**
     * Called by PluginController::reloadAction when the addon version does not match what's installed
     *
     * @param Plugin        $plugin
     * @param MauticFactory $factory
     */
    static public function onUpdate(Plugin $plugin, MauticFactory $factory)
    {

    }
}
