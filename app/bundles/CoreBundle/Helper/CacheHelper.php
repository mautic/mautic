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

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Session\Session;

class CacheHelper
{
    /**
     * @var string
     */
    private $cacheDir;

    /**
     * @var Session
     */
    private $session;

    /**
     * @var string
     */
    private $configFile;

    public function __construct(string $cacheDir, ?Session $session, PathsHelper $pathsHelper)
    {
        $this->cacheDir   = $cacheDir;
        $this->session    = $session;
        $this->configFile = $pathsHelper->getLocalConfigurationFile();
    }

    /**
     * Deletes the cache folder.
     */
    public function nukeCache(): void
    {
        $this->clearSessionItems();

        $fs = new Filesystem();
        $fs->remove($this->cacheDir);

        $this->clearOpcache();
        $this->clearApcuCache();
    }

    public function refreshConfig(): void
    {
        $this->clearSessionItems();
        $this->clearConfigOpcache();
        $this->clearApcuCache();
    }

    /**
     * Clear cache related session items.
     */
    protected function clearSessionItems(): void
    {
        if (!$this->session) {
            return;
        }

        // Clear the menu items and icons so they can be rebuilt
        $this->session->remove('mautic.menu.items');
        $this->session->remove('mautic.menu.icons');
    }

    private function clearConfigOpcache(): void
    {
        if (!function_exists('opcache_reset') || !function_exists('opcache_invalidate')) {
            return;
        }

        opcache_invalidate($this->configFile, true);
    }

    private function clearOpcache(): void
    {
        if (!function_exists('opcache_reset')) {
            return;
        }

        opcache_reset();
    }

    private function clearApcuCache(): void
    {
        if (!function_exists('apcu_clear_cache')) {
            return;
        }

        apcu_clear_cache();
    }
}
