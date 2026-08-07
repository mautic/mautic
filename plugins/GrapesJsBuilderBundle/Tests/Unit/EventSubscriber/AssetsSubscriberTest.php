<?php

declare(strict_types=1);

namespace MauticPlugin\GrapesJsBuilderBundle\Tests\Unit\EventSubscriber;

use Mautic\CoreBundle\Event\CustomAssetsEvent;
use Mautic\InstallBundle\Install\InstallService;
use MauticPlugin\GrapesJsBuilderBundle\EventSubscriber\AssetsSubscriber;
use MauticPlugin\GrapesJsBuilderBundle\Integration\Config;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class AssetsSubscriberTest extends TestCase
{
    private const string ASSET_SUBDIR = 'plugins/GrapesJsBuilderBundle/Assets/library/js/dist';

    private MockObject&CustomAssetsEvent $assetsEvent;

    private string $projectDir;

    protected function setUp(): void
    {
        $this->assetsEvent = $this->createMock(CustomAssetsEvent::class);
        $this->projectDir  = sys_get_temp_dir().'/gjs_assets_test_'.uniqid();
        mkdir($this->projectDir.'/'.self::ASSET_SUBDIR, 0777, true);
    }

    protected function tearDown(): void
    {
        $assetDir = $this->projectDir.'/'.self::ASSET_SUBDIR;

        foreach (glob($assetDir.'/*') ?: [] as $file) {
            unlink($file);
        }

        // Remove the full directory tree bottom-up.
        $path = $assetDir;
        while ($path !== $this->projectDir) {
            @rmdir($path);
            $path = dirname($path);
        }

        rmdir($this->projectDir);
    }

    public function testInjectAssetsSkipsWhenNotInstalled(): void
    {
        $config = $this->createMock(Config::class);
        $config->expects($this->never())->method('isPublished');
        $this->assetsEvent->expects($this->never())->method('addScript');

        $this->makeSubscriber($config, installed: false)->injectAssets($this->assetsEvent);
    }

    public function testInjectAssetsSkipsWhenNotAdminPage(): void
    {
        $config = $this->createMock(Config::class);
        $config->expects($this->never())->method('isPublished');
        $this->assetsEvent->expects($this->never())->method('addScript');

        $this->makeSubscriber($config, path: '/api/contacts')->injectAssets($this->assetsEvent);
    }

    public function testInjectAssetsSkipsWhenPluginNotPublished(): void
    {
        $config = $this->createMock(Config::class);
        $config->method('isPublished')->willReturn(false);
        $this->assetsEvent->expects($this->never())->method('addScript');

        $this->makeSubscriber($config)->injectAssets($this->assetsEvent);
    }

    public function testInjectAssetsSkipsWhenManifestMissing(): void
    {
        $this->assetsEvent->expects($this->never())->method('addScript');
        $this->assetsEvent->expects($this->never())->method('addStylesheet');

        $this->makeSubscriberPublished()->injectAssets($this->assetsEvent);
    }

    public function testInjectAssetsSkipsWhenManifestIsInvalidJson(): void
    {
        $this->writeManifest('not-valid-json');
        $this->assetsEvent->expects($this->never())->method('addScript');
        $this->assetsEvent->expects($this->never())->method('addStylesheet');

        $this->makeSubscriberPublished()->injectAssets($this->assetsEvent);
    }

    public function testInjectAssetsSkipsWhenManifestKeysMissing(): void
    {
        $this->writeManifest(['other.js' => 'other.abc123.js']);
        $this->assetsEvent->expects($this->never())->method('addScript');
        $this->assetsEvent->expects($this->never())->method('addStylesheet');

        $this->makeSubscriberPublished()->injectAssets($this->assetsEvent);
    }

    public function testInjectAssetsSkipsWhenManifestValueIsNotString(): void
    {
        $this->writeManifest(['builder.js' => ['nested' => 'value'], 'builder.css' => 42]);
        $this->assetsEvent->expects($this->never())->method('addScript');
        $this->assetsEvent->expects($this->never())->method('addStylesheet');

        $this->makeSubscriberPublished()->injectAssets($this->assetsEvent);
    }

    public function testInjectAssetsSkipsWhenManifestContainsPathTraversal(): void
    {
        $this->writeManifest(['builder.js' => '../evil.js', 'builder.css' => '../../etc/passwd']);
        $this->assetsEvent->expects($this->never())->method('addScript');
        $this->assetsEvent->expects($this->never())->method('addStylesheet');

        $this->makeSubscriberPublished()->injectAssets($this->assetsEvent);
    }

    public function testInjectAssetsSkipsWhenManifestPointsToNonExistentFile(): void
    {
        $this->writeManifest(['builder.js' => 'builder.abc123.js', 'builder.css' => 'builder.abc123.css']);
        // Files are NOT created on disk.
        $this->assetsEvent->expects($this->never())->method('addScript');
        $this->assetsEvent->expects($this->never())->method('addStylesheet');

        $this->makeSubscriberPublished()->injectAssets($this->assetsEvent);
    }

    public function testInjectAssetsAddsScriptAndStylesheetWhenBothAssetsResolved(): void
    {
        $jsFile  = 'builder.abc123.js';
        $cssFile = 'builder.abc123.css';

        $this->writeManifest(['builder.js' => $jsFile, 'builder.css' => $cssFile]);
        $this->touchAsset($jsFile);
        $this->touchAsset($cssFile);

        $this->assetsEvent->expects($this->once())->method('addScript')->with(self::ASSET_SUBDIR.'/'.$jsFile);
        $this->assetsEvent->expects($this->once())->method('addStylesheet')->with(self::ASSET_SUBDIR.'/'.$cssFile);

        $this->makeSubscriberPublished()->injectAssets($this->assetsEvent);
    }

    public function testInjectAssetsAddsOnlyScriptWhenCssNotInManifest(): void
    {
        $jsFile = 'builder.abc123.js';
        $this->writeManifest(['builder.js' => $jsFile]);
        $this->touchAsset($jsFile);

        $this->assetsEvent->expects($this->once())->method('addScript');
        $this->assetsEvent->expects($this->never())->method('addStylesheet');

        $this->makeSubscriberPublished()->injectAssets($this->assetsEvent);
    }

    public function testInjectAssetsAddsOnlyStylesheetWhenJsNotInManifest(): void
    {
        $cssFile = 'builder.abc123.css';
        $this->writeManifest(['builder.css' => $cssFile]);
        $this->touchAsset($cssFile);

        $this->assetsEvent->expects($this->never())->method('addScript');
        $this->assetsEvent->expects($this->once())->method('addStylesheet');

        $this->makeSubscriberPublished()->injectAssets($this->assetsEvent);
    }

    // --- helpers ---

    private function makeSubscriberPublished(): AssetsSubscriber
    {
        $config = $this->createStub(Config::class);
        $config->method('isPublished')->willReturn(true);

        return $this->makeSubscriber($config);
    }

    private function makeSubscriber(
        ?Config $config = null,
        bool $installed = true,
        string $path = '/s/emails',
    ): AssetsSubscriber {
        $installer = $this->createStub(InstallService::class);
        $installer->method('checkIfInstalled')->willReturn($installed);

        $request = $this->createStub(Request::class);
        $request->method('getPathInfo')->willReturn($path);

        $requestStack = $this->createStub(RequestStack::class);
        $requestStack->method('getCurrentRequest')->willReturn($request);

        return new AssetsSubscriber(
            $config ?? $this->createStub(Config::class),
            $installer,
            $requestStack,
            $this->projectDir,
            $this->createStub(LoggerInterface::class),
        );
    }

    /**
     * @param array<string, mixed>|string $content
     */
    private function writeManifest(array|string $content): void
    {
        $data = is_array($content) ? json_encode($content) : $content;
        file_put_contents($this->projectDir.'/'.self::ASSET_SUBDIR.'/manifest.json', $data);
    }

    private function touchAsset(string $fileName): void
    {
        touch($this->projectDir.'/'.self::ASSET_SUBDIR.'/'.$fileName);
    }
}
