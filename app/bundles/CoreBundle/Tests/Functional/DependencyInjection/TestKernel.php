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

        // the container is compiled once, so the env variables have to be loaded on every boot
        EnvLoader::load();

        parent::__construct('test', false);
    }

    /**
     * The parent resolves the project dir from the kernel file location, what does not work for a kernel that lives in a bundle.
     */
    public function getProjectDir(): string
    {
        return $this->getApplicationDir();
    }
}
