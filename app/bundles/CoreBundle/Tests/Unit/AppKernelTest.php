<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit;

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
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
        mkdir($this->applicationDir.'/plugins/ReadablePluginBundle', 0777, true);
        file_put_contents(
            $this->applicationDir.'/plugins/ReadablePluginBundle/ReadablePluginBundle.php',
            <<<'PHP'
<?php

namespace MauticPlugin\ReadablePluginBundle;

final class ReadablePluginBundle extends \Symfony\Component\HttpKernel\Bundle\Bundle
{
}
PHP
        );
        require_once $this->applicationDir.'/plugins/ReadablePluginBundle/ReadablePluginBundle.php';
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
        unlink($this->applicationDir.'/plugins/ReadablePluginBundle/ReadablePluginBundle.php');
        rmdir($this->applicationDir.'/plugins/ReadablePluginBundle');
        rmdir($this->applicationDir.'/plugins');
        unlink($this->applicationDir.'/app/config/paths.php');
        rmdir($this->applicationDir.'/app/config');
        rmdir($this->applicationDir.'/app');
        rmdir($this->applicationDir);
    }

    #[PreserveGlobalState(false)]
    #[RunInSeparateProcess]
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

        $bundles = iterator_to_array($kernel->registerBundles());

        $this->assertContains(
            'MauticPlugin\\ReadablePluginBundle\\ReadablePluginBundle',
            array_map(get_class(...), $bundles)
        );
    }

    #[PreserveGlobalState(false)]
    #[RunInSeparateProcess]
    public function testUnreadablePluginRootDoesNotPreventBundleRegistration(): void
    {
        $pluginRoot = $this->applicationDir.'/plugins';
        chmod($pluginRoot, 0000);

        if (is_readable($pluginRoot)) {
            chmod($pluginRoot, 0700);
            $this->markTestSkipped('The test process can read mode-000 plugin roots.');
        }

        try {
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

            $this->assertIsArray(iterator_to_array($kernel->registerBundles()));
        } finally {
            chmod($pluginRoot, 0700);
        }
    }
}
