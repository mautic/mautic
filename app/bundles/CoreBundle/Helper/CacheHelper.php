<?php
/**
 * Created by PhpStorm.
 * User: alan
 * Date: 12/17/14
 * Time: 12:28 PM
 */

namespace Mautic\CoreBundle\Helper;

use Mautic\CoreBundle\Factory\MauticFactory;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;

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
        $application->run($input);

        if (ob_get_length() > 0) {
            ob_end_clean();
        }
    }

    /**
     * Deletes the cache folder
     */
    public function nukeCache()
    {
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
}