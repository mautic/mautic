<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\DependencyInjection;

use Mautic\CoreBundle\Test\EnvLoader;

/**
 * Minimal kernel to boot the container without the KernelTestCase machinery.
 */
final class TestKernel extends \AppTestKernel
{
    public function __construct()
    {
        // pin the platform version, so Doctrine does not open a connection to detect it
        \defined('MAUTIC_DB_SERVER_VERSION') || \define('MAUTIC_DB_SERVER_VERSION', '8.4');

        // the phpunit.xml.dist config is not used when a single test file is run, so make the environment explicit
        \defined('IS_PHPUNIT') || \define('IS_PHPUNIT', true);
        $_SERVER['APP_ENV'] = $_ENV['APP_ENV'] = 'test';

        // the container is compiled once, so the env variables have to be loaded on every boot
        EnvLoader::load();

        parent::__construct('test', false);
    }

    public function boot(): void
    {
        $exceptionHandlerBeforeBoot = self::currentExceptionHandler();

        parent::boot();

        // the Symfony ErrorHandler registers itself on boot and never restores the previous handler, what PHPUnit reports as a risky test
        if (self::currentExceptionHandler() !== $exceptionHandlerBeforeBoot) {
            restore_exception_handler();
        }

        // HTMLPurifier warns about its missing cache directory, that is only created by a cache warmer on container compilation
        $htmlPurifierCacheDir = $this->getCacheDir().'/htmlpurifier';
        if (!is_dir($htmlPurifierCacheDir)) {
            mkdir($htmlPurifierCacheDir, 0777, true);
        }
    }

    private static function currentExceptionHandler(): ?callable
    {
        $exceptionHandler = set_exception_handler(null);
        restore_exception_handler();

        return $exceptionHandler;
    }

    /**
     * The parent resolves the project dir from the kernel file location, what does not work for a kernel that lives in a bundle.
     */
    public function getProjectDir(): string
    {
        return $this->getApplicationDir();
    }
}
