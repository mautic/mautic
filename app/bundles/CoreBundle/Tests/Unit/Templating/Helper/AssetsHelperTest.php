<?php

declare(strict_types=1);

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Tests\Unit\Templating\Helper;

use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Templating\Helper\AssetsHelper;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Asset\Packages;

class AssetsHelperTest extends TestCase
{
    public function testAssetContext(): void
    {
        $pathsHelper = $this->getMockBuilder(PathsHelper::class)
             ->disableOriginalConstructor()
             ->getMock();
        $pathsHelper->method('getSystemPath')
            ->willReturn('');

        $assetHelper = new AssetsHelper($this->createPackagesMock());
        $assetHelper->setPathsHelper($pathsHelper);

        $assetHelper->addStylesheet('/app.css');
        $head = $assetHelper->getHeadDeclarations();

        Assert::assertStringContainsString('app.css', $head);

        $assetHelper->setContext(AssetsHelper::CONTEXT_BUILDER)
            ->addStylesheet('/builder.css')
            ->setContext();

        $head = $assetHelper->getHeadDeclarations();
        Assert::assertStringNotContainsString('builder.css', $head);

        $head = $assetHelper->setContext(AssetsHelper::CONTEXT_BUILDER)
            ->getHeadDeclarations();
        Assert::assertStringContainsString('builder.css', $head);
        Assert::assertStringNotContainsString('app.css', $head);

        $version = $this->setVersion($assetHelper);

        $head = $assetHelper->setContext(AssetsHelper::CONTEXT_BUILDER)
            ->getHeadDeclarations();
        Assert::assertStringContainsString('builder.css?v'.$version, $head);
        Assert::assertStringNotContainsString('app.css?v'.$version, $head);
    }

    public function testGetUrlWithAbsolutePath(): void
    {
        $assetHelper = new AssetsHelper($this->createPackagesMock());

        Assert::assertEquals('http://some.absolute/path', $assetHelper->getUrl('http://some.absolute/path'));
        Assert::assertEquals('https://some.absolute/path', $assetHelper->getUrl('https://some.absolute/path'));

        $this->setVersion($assetHelper);

        Assert::assertEquals('http://some.absolute/path', $assetHelper->getUrl('http://some.absolute/path'));
        Assert::assertEquals('https://some.absolute/path', $assetHelper->getUrl('https://some.absolute/path'));
    }

    public function testGetUrlWithRelativePath(): void
    {
        $pathsHelper = $this->getMockBuilder(PathsHelper::class)
             ->disableOriginalConstructor()
             ->getMock();

        $pathsHelper->method('getSystemPath')
            ->willReturn('http://some.mautic');

        $assetHelper = new AssetsHelper($this->createPackagesMock());
        $assetHelper->setPathsHelper($pathsHelper);

        Assert::assertEquals('http://some.mautic/some/path', $assetHelper->getUrl('some/path'));

        $version = $this->setVersion($assetHelper);

        Assert::assertEquals('http://some.mautic/some/path?v'.$version, $assetHelper->getUrl('some/path'));
    }

    public function testGetUrlWithRelativePathWhenMauticInSubFolder(): void
    {
        $pathsHelper = $this->getMockBuilder(PathsHelper::class)
             ->disableOriginalConstructor()
             ->getMock();

        $pathsHelper->method('getSystemPath')
            ->willReturn('http://some.mautic/m');

        $assetHelper = new AssetsHelper($this->createPackagesMock());
        $assetHelper->setPathsHelper($pathsHelper);

        Assert::assertEquals('http://some.mautic/m/some/path', $assetHelper->getUrl('some/path'));

        $version = $this->setVersion($assetHelper);

        Assert::assertEquals('http://some.mautic/m/some/path?v'.$version, $assetHelper->getUrl('some/path'));
    }

    public function testGetUrlWithRelativePathWithDevIndex(): void
    {
        $pathsHelper = $this->getMockBuilder(PathsHelper::class)
             ->disableOriginalConstructor()
             ->getMock();

        $pathsHelper->method('getSystemPath')
            ->willReturn('http://some.mautic/index_dev.php/');

        $assetHelper = new AssetsHelper($this->createPackagesMock());
        $assetHelper->setPathsHelper($pathsHelper);

        Assert::assertEquals('http://some.mautic/some/path', $assetHelper->getUrl('some/path'));

        $version = $this->setVersion($assetHelper);

        Assert::assertEquals('http://some.mautic/some/path?v'.$version, $assetHelper->getUrl('some/path'));
    }

    public function testGetUrlWithVersionAndExistingQueryPart(): void
    {
        $pathsHelper = $this->getMockBuilder(PathsHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $pathsHelper->method('getSystemPath')
            ->willReturn('/');

        $assetHelper = new AssetsHelper($this->createPackagesMock());
        $assetHelper->setPathsHelper($pathsHelper);

        $version = $this->setVersion($assetHelper);

        Assert::assertEquals('/path?some&amp;v'.$version, $assetHelper->getUrl('/path?some'));
        Assert::assertEquals('/path?some=65&amp;v'.$version, $assetHelper->getUrl('/path?some=65'));
        Assert::assertEquals('/path?v'.$version, $assetHelper->getUrl('/path?v'.$version));
    }

    public function testGetCKEditorScripts(): void
    {
        $secretKey   = 'mautic';
        $version     = 1;
        $assetHelper = new AssetsHelper($this->createPackagesMock());
        $assetHelper->setVersion($secretKey, $version);
        $version = substr(hash('sha1', $secretKey.$version), 0, 8);

        $reflectionObject = new \ReflectionObject($assetHelper);
        $method           = $reflectionObject->getMethod('getCKEditorScripts');
        $method->setAccessible(true);
        $ckEditorScripts = $method->invokeArgs($assetHelper, []);
        Assert::assertEquals(["app/bundles/CoreBundle/Assets/js/libraries/ckeditor/ckeditor.js?v$version",
            "app/bundles/CoreBundle/Assets/js/libraries/ckeditor/adapters/jquery.js?v$version",
        ], $ckEditorScripts);
    }

    /**
     * @return MockObject|Packages
     */
    private function createPackagesMock()
    {
        $packagesMock = $this->getMockBuilder(Packages::class)
            ->disableOriginalConstructor()
            ->getMock();

        $packagesMock->method('getUrl')
            ->will($this->returnCallback(function (string $path) {
                return $path;
            }));

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
