<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\AddonBundle\Bundle;

use Mautic\AddonBundle\Entity\Addon;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\AddonBundle\Helper\AddonHelper;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Base Bundle class which should be extended by addon bundles
 */
abstract class AddonBundleBase extends Bundle
{
    /**
     * Checks if the bundle is enabled
     *
     * @return bool
     */
    public function isEnabled()
    {
        /** @var \Mautic\AddonBundle\Helper\AddonHelper $helper */
        $helper = $this->container->get('mautic.factory')->getHelper('addon');
        return $helper->isEnabled($this->getName());
    }

    /**
     * Called by AddonController::reloadAction when adding a new addon that's not already installed
     *
     * @param MauticFactory $factory
     */
    static public function onInstall(MauticFactory $factory)
    {

    }

    /**
     * Called by AddonController::reloadAction when the addon version does not match what's installed
     *
     * @param Addon         $addon
     * @param MauticFactory $factory
     */
    static public function onUpdate(Addon $addon, MauticFactory $factory)
    {

    }

    /**
     * Called by AddonController::reloadAction when an addon is uninstalled
     *
     * @todo NOT IMPLEMENTED YET IN THE ADDON MANAGER
     *
     * @param Addon         $addon
     * @param MauticFactory $factory
     */
    static public function onUninstall(Addon $addon, MauticFactory $factory)
    {

    }
}
