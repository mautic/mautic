<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\AddonBundle\Helper;

use Mautic\CoreBundle\Factory\MauticFactory;

/**
 * Helper class for supporting addon functions
 */
class AddonHelper
{

    /**
     * @var array
     */
    private static $addons = array();

    /**
     * @var MauticFactory
     */
    private $factory;

    /**
     * Flag if the addons have been loaded
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
     *
     * @return bool
     */
    public function isEnabled($bundle)
    {
        // Populate our addon cache if not present
        if (!static::$loaded) {
            $db = $this->factory->getDatabase();
            $sm = $db->getSchemaManager();

            if ($sm->tablesExist(MAUTIC_TABLE_PREFIX.'addons')) {
                /** @var \Mautic\AddonBundle\Entity\IntegrationRepository $repo */
                $repo = $this->factory->getModel('addon')->getRepository();
                static::$addons = $repo->getBundleStatus();
            }

            static::$loaded = true;
        }

        // Check if the bundle is registered
        if (isset(static::$addons[$bundle])) {
            return static::$addons[$bundle];
        }

        // If we don't know about the bundle, it isn't properly registered and we will always return false
        return false;
    }
}
