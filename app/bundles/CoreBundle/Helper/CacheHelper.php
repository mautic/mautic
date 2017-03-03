<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Helper;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Kernel;

/**
 * Class CacheHelper.
 */
class CacheHelper
{
    protected $cacheDir;

    protected $configFile;

    protected $containerFile;

    protected $container;

    /**
     * CacheHelper constructor.
     *
     * @param \AppKernel $kernel
     */
    public function __construct(\AppKernel $kernel)
    {
        $this->kernel        = $kernel;
        $this->container     = $kernel->getContainer();
        $this->cacheDir      = $this->container->get('mautic.helper.paths')->getSystemPath('cache', true);
        $this->configFile    = $kernel->getLocalConfigFile(false);
        $this->containerFile = $kernel->getContainerFile();
    }

    /**
     * Clear the application cache and run the warmup routine for the current environment.
     */
    public function clearCache()
    {
        $memoryLimit = ini_get('memory_limit');
        if ((int) substr($memoryLimit, 0, -1) < 128) {
            ini_set('memory_limit', '128M');
        }

        $this->clearSessionItems();
        $this->clearOpcaches();

        //attempt to squash command output
        ob_start();

        $args = ['console', 'cache:clear', '--env='.MAUTIC_ENV];

        if (MAUTIC_ENV == 'prod') {
            $args[] = '--no-debug';
        }

        $input       = new ArgvInput($args);
        $application = new Application($this->kernel);
        $application->setAutoExit(false);
        $output = new NullOutput();
        $application->run($input, $output);

        if (ob_get_length() > 0) {
            ob_end_clean();
        }
    }

    /**
     * Deletes the cache folder.
     */
    public function nukeCache()
    {
        $this->clearSessionItems();

        $fs = new Filesystem();
        $fs->remove($this->cacheDir);

        $this->clearOpcaches();
    }

    /**
     * Delete's the file Symfony caches settings in.
     *
     * @param bool $configSave
     */
    public function clearContainerFile($configSave = true)
    {
        $this->clearSessionItems();

        if (file_exists($this->containerFile)) {
            unlink($this->containerFile);
        }

        $this->clearOpcaches($configSave);
    }

    /**
     * Clears the cache for translations.
     *
     * @param null $locale
     */
    public function clearTranslationCache($locale = null)
    {
        if ($locale) {
            $localeCache = $this->cacheDir.'/translations/catalogue.'.$locale.'.php';
            if (file_exists($localeCache)) {
                unlink($localeCache);
            }
        } else {
            $fs = new Filesystem();
            $fs->remove($this->cacheDir.'/translations');
        }
    }

    /**
     * Clears the cache for routing.
     */
    public function clearRoutingCache()
    {
        $unlink = [
            $this->kernel->getContainer()->getParameter('router.options.generator.cache_class'),
            $this->kernel->getContainer()->getParameter('router.options.matcher.cache_class'),
        ];

        foreach ($unlink as $file) {
            if (file_exists($this->cacheDir.'/'.$file.'.php')) {
                unlink($this->cacheDir.'/'.$file.'.php');
            }
        }
    }

    /**
     * Clear cache related session items.
     */
    protected function clearSessionItems()
    {
        // Clear the menu items and icons so they can be rebuilt
        $session = $this->kernel->getContainer()->get('session');
        $session->remove('mautic.menu.items');
        $session->remove('mautic.menu.icons');
    }

    /**
     * Clear opcaches.
     *
     * @param bool|false $configSave
     */
    protected function clearOpcaches($configSave = false)
    {
        // Clear opcaches before rebuilding the cache to ensure latest file changes are used
        if (function_exists('opcache_reset')) {
            if ($configSave && function_exists('opcache_invalidate')) {
                // Clear the cached config file
                opcache_invalidate($this->configFile, true);
                opcache_invalidate($this->containerFile, true);
            } else {
                // Clear the entire cache as anything could have been affected
                opcache_reset();
            }
        }

        if (function_exists('apc_clear_cache')) {
            apc_clear_cache();
        }
    }
}
