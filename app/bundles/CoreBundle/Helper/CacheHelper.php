<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Helper;

use Mautic\CoreBundle\Factory\MauticFactory;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\NullOutput;

/**
 * Class CacheHelper
 *
 * @package Mautic\CoreBundle\Helper
 */
class CacheHelper
{
    private $factory;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * Clear the application cache and run the warmup routine for the current environment
     *
     * @param bool $noWarmup Skips the warmup routine
     *
     * @return void
     */
    public function clearCache($noWarmup = false)
    {
        $this->clearSessionItems();

        // Force a refresh of enabled addon bundles so they are picked up by the events
        $addonHelper = $this->factory->getHelper('addon');
        $addonHelper->buildAddonCache();

        ini_set('memory_limit', '128M');

        //attempt to squash command output
        ob_start();

        $env  = $this->factory->getEnvironment();
        $args = array('console', 'cache:clear', '--env=' . $env);

        if ($env == 'prod') {
            $args[] = '--no-debug';
        }

        if ($noWarmup) {
            $args[] = '--no-warmup';
        }

        $input       = new ArgvInput($args);
        $application = new Application($this->factory->getKernel());
        $application->setAutoExit(false);
        $output      = new NullOutput();
        $application->run($input, $output);

        if (ob_get_length() > 0) {
            ob_end_clean();
        }
    }

    /**
     * Deletes the cache folder
     */
    public function nukeCache()
    {
        $this->clearSessionItems();

        $cacheDir = $this->factory->getSystemPath('cache', true);

        $fs = new \Symfony\Component\Filesystem\Filesystem();
        $fs->remove($cacheDir);
    }

    /**
     * Delete's the file Symfony caches settings in
     */
    public function clearCacheFile()
    {
        $env      = $this->factory->getEnvironment();
        $debug    = ($this->factory->getDebugMode()) ? 'Debug' : '';
        $cacheDir = $this->factory->getSystemPath('cache', true);

        $cacheFile = "$cacheDir/app".ucfirst($env)."{$debug}ProjectContainer.php";

        if (file_exists($cacheFile)) {
            unlink($cacheFile);
        }
    }

    /**
     * Clear cache related session items
     */
    protected function clearSessionItems()
    {
        // Clear the menu items and icons so they can be rebuilt
        $session = $this->factory->getSession();
        $session->remove('mautic.menu.items');
        $session->remove('mautic.menu.icons');
    }
}