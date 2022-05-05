<?php

namespace Mautic\CoreBundle\Helper;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\KernelInterface;

class CacheHelper
{
    private string $cacheDir;
    private ?Session $session;
    private PathsHelper $pathsHelper;
    private KernelInterface $kernel;

    public function __construct(string $cacheDir, ?Session $session, PathsHelper $pathsHelper, KernelInterface $kernel)
    {
        $this->cacheDir    = $cacheDir;
        $this->session     = $session;
        $this->pathsHelper = $pathsHelper;
        $this->kernel      = $kernel;
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
     * Run the bin/console cache:clear command.
     */
    public function clearSymfonyCache(): int
    {
        $env = $this->kernel->getEnvironment();

        $application = new Application($this->kernel);
        $application->setAutoExit(false);

        $input = new ArrayInput([
            'command' => 'cache:clear',
            '--env'   => $env,
        ]);

        $output = new BufferedOutput();

        return $application->run($input, $output);
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

        opcache_invalidate($this->pathsHelper->getLocalConfigurationFile(), true);
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
