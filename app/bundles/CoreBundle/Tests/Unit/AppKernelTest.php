<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class AppKernelTest extends TestCase
{
    private string $applicationDir;

    private bool $testDirectoryCreated = false;

    protected function setUp(): void
    {
        $this->applicationDir = sys_get_temp_dir().'/mautic_app_kernel_'.bin2hex(random_bytes(6));
        mkdir($this->applicationDir.'/app/config', 0777, true);
        file_put_contents($this->applicationDir.'/app/config/paths.php', '<?php $paths = [];');
        mkdir($this->applicationDir.'/plugins', 0777, true);
        mkdir($this->applicationDir.'/plugins/.unreadable', 0000);
        $this->testDirectoryCreated = true;

        if (is_readable($this->applicationDir.'/plugins/.unreadable')) {
            $this->markTestSkipped('The test process can read mode-000 directories.');
        }
    }

    protected function tearDown(): void
    {
        if (!$this->testDirectoryCreated) {
            return;
        }

        chmod($this->applicationDir.'/plugins/.unreadable', 0700);
        rmdir($this->applicationDir.'/plugins/.unreadable');
        rmdir($this->applicationDir.'/plugins');
        unlink($this->applicationDir.'/app/config/paths.php');
        rmdir($this->applicationDir.'/app/config');
        rmdir($this->applicationDir.'/app');
        rmdir($this->applicationDir);
    }

    public function testUnreadablePluginDirectoryDoesNotPreventBundleRegistration(): void
    {
        $kernel = new class($this->applicationDir) extends \AppKernel {
            public function __construct(private readonly string $testApplicationDir)
            {
                parent::__construct('prod', false);
            }

            public function getApplicationDir(): string
            {
                return $this->testApplicationDir;
            }
        };

        self::assertIsArray(iterator_to_array($kernel->registerBundles()));
    }
}
