<?php
/**
 * Created by PhpStorm.
 * User: alan
 * Date: 12/17/14
 * Time: 12:28 PM
 */

namespace Mautic\CoreBundle\Helper;


use Mautic\CoreBundle\Factory\MauticFactory;
use Symfony\Component\Console\Application;
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
     * @return void
     */
    public function clearCache()
    {
        ini_set('memory_limit', '128M');

        //attempt to squash command output
        ob_start();

        $env  = $this->factory->getEnvironment();
        $args = array('console', 'cache:clear', '--env=' . $env);

        if ($env == 'prod') {
            $args[] = '--no-debug';
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