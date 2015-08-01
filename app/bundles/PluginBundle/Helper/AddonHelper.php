<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PluginBundle\Helper;

use Mautic\CoreBundle\Factory\MauticFactory;

/**
 * Helper class for supporting addon functions
 */
class AddonHelper
{

    /**
     * @var array
     */
    private static $plugins = array();

    /**
     * @var MauticFactory
     */
    private $factory;

    /**
     * Flag if the plugins have been loaded
     *
     * @var bool
     */
    private static $loaded = false;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * Check if a bundle is enabled
     *
     * @param string $bundle
     * @param bool   $forceRefresh
     *
     * @return bool
     */
    public function isEnabled($bundle)
    {
        $dbName = $this->factory->getParameter('db_name');

        if (empty($dbName)) {
            //the database hasn't been installed yet
            return false;
        }

        if (!static::$loaded) {
            $this->buildAddonCache();
        }

        // Check if the bundle is registered
        if (isset(static::$plugins[$bundle])) {
            return static::$plugins[$bundle];
        }

        // If we don't know about the bundle, it isn't properly registered and we will always return false
        return false;
    }

    public function buildAddonCache()
    {
        // Populate our addon cache if not present

        /** @var \Mautic\PluginBundle\Entity\IntegrationRepository $repo */
        try {
            $repo           = $this->factory->getModel('plugin')->getRepository();
            static::$plugins = $repo->getBundleStatus();
        } catch (\Exception $exception) {
            //database is probably not installed or there was an issue connecting
            return false;
        }

        static::$loaded = true;
    }
}
