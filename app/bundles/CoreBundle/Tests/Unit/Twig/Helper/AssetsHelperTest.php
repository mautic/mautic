<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Twig\Helper;

use Mautic\CoreBundle\Helper\AppVersion;
use Mautic\CoreBundle\Helper\AssetGenerationHelper;
use Mautic\CoreBundle\Helper\BundleHelper;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Twig\Helper\AssetsHelper;
use Mautic\InstallBundle\Install\InstallService;
use Mautic\IntegrationsBundle\Helper\BuilderIntegrationsHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Asset\Packages;

final class AssetsHelperTest extends TestCase
{
    /**
     * @var MockObject&PathsHelper
     */
    private MockObject $pathsHelper;

    private AssetsHelper $assetHelper;

    protected function setUp(): void
    {
        $this->pathsHelper = $this->createMock(PathsHelper::class);
        $this->assetHelper = new AssetsHelper(
            $this->createPackagesMock(),
            $this->pathsHelper,
            $this->createAssetGenerationHelper(),
            $this->createStub(BuilderIntegrationsHelper::class),
            $this->createStub(InstallService::class),
            '',
        );
    }

    private function createAssetGenerationHelper(): AssetGenerationHelper
    {
        return new AssetGenerationHelper(
            $this->createStub(BundleHelper::class),
            $this->pathsHelper,
            $this->createStub(CoreParametersHelper::class),
            $this->createStub(AppVersion::class),
        );
    }

    public function testAssetContext(): void
    {
        $this->pathsHelper->expects($this->once())->method('getSystemPath')
            ->willReturn('');

        $this->assetHelper->addStylesheet('/app.css');
        $head = $this->assetHelper->getHeadDeclarations();

        $this->assertStringContainsString('app.css', $head);

        $head = $this->assetHelper->setContext(AssetsHelper::CONTEXT_BUILDER)
            ->getHeadDeclarations();
        $this->assertStringNotContainsString('app.css', $head);

        $version = $this->setVersion($this->assetHelper);

        $head = $this->assetHelper->setContext(AssetsHelper::CONTEXT_BUILDER)
            ->getHeadDeclarations();
        $this->assertStringNotContainsString('app.css?v'.$version, $head);
    }

    public function testGetUrlWithAbsolutePath(): void
    {
        $this->assertSame('http://some.absolute/path', $this->assetHelper->getUrl('http://some.absolute/path'));
        $this->assertSame('https://some.absolute/path', $this->assetHelper->getUrl('https://some.absolute/path'));

        $this->setVersion($this->assetHelper);

        $this->assertSame('http://some.absolute/path', $this->assetHelper->getUrl('http://some.absolute/path'));
        $this->assertSame('https://some.absolute/path', $this->assetHelper->getUrl('https://some.absolute/path'));
    }

    public function testGetUrlWithRelativePath(): void
    {
        $this->pathsHelper->method('getSystemPath')
            ->willReturn('http://some.mautic');

        $this->assertSame('http://some.mautic/some/path', $this->assetHelper->getUrl('some/path'));

        $version = $this->setVersion($this->assetHelper);

        $this->assertSame('http://some.mautic/some/path?v'.$version, $this->assetHelper->getUrl('some/path'));
    }

    public function testGetUrlWithRelativePathWhenMauticInSubFolder(): void
    {
        $this->pathsHelper->method('getSystemPath')
            ->willReturn('http://some.mautic/m');

        $this->assertSame('http://some.mautic/m/some/path', $this->assetHelper->getUrl('some/path'));

        $version = $this->setVersion($this->assetHelper);

        $this->assertSame('http://some.mautic/m/some/path?v'.$version, $this->assetHelper->getUrl('some/path'));
    }

    public function testGetUrlWithRelativePathWithDevIndex(): void
    {
        $this->pathsHelper->method('getSystemPath')
            ->willReturn('http://some.mautic/');

        $this->assertSame('http://some.mautic/some/path', $this->assetHelper->getUrl('some/path'));

        $version = $this->setVersion($this->assetHelper);

        $this->assertSame('http://some.mautic/some/path?v'.$version, $this->assetHelper->getUrl('some/path'));
    }

    public function testGetUrlWithVersionAndExistingQueryPart(): void
    {
        $this->pathsHelper->method('getSystemPath')
            ->willReturn('/');

        $version = $this->setVersion($this->assetHelper);

        $this->assertSame('/path?some&amp;v'.$version, $this->assetHelper->getUrl('/path?some'));
        $this->assertSame('/path?some=65&amp;v'.$version, $this->assetHelper->getUrl('/path?some=65'));
        $this->assertSame('/path?v'.$version, $this->assetHelper->getUrl('/path?v'.$version));
    }

    public function testGetCKEditorScripts(): void
    {
        $secretKey   = 'mautic';
        $version     = 1;
        $this->assetHelper->setVersion($secretKey, $version);
        $version = substr(hash('sha1', $secretKey.$version), 0, 8);

        $reflectionObject = new \ReflectionObject($this->assetHelper);
        $method           = $reflectionObject->getMethod('getCKEditorScripts');
        $ckEditorScripts  = $method->invokeArgs($this->assetHelper, []);
        $this->assertEquals([
            "media/libraries/ckeditor/ckeditor.js?v{$version}",
        ], $ckEditorScripts);
    }

    /**
     * @return MockObject&Packages
     */
    private function createPackagesMock()
    {
        /** @var MockObject&Packages $packagesMock */
        $packagesMock = $this->createMock(Packages::class);

        $packagesMock->method('getUrl')
            ->willReturnCallback(fn (string $path): string => $path);

        return $packagesMock;
    }

    private function setVersion(AssetsHelper $assetsHelper): string
    {
        $secretKey = 'secret';
        $version   = '123';
        $assetsHelper->setVersion($secretKey, $version);

        return substr(hash('sha1', $secretKey.$version), 0, 8);
    }
}
