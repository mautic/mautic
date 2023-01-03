<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Templating\Twig\Extension;

use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Templating\Helper\AssetsHelper;
use Mautic\CoreBundle\Templating\Twig\Extension\AssetExtension;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Asset\Packages;

class AssetExtensionTest extends TestCase
{
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

    public function testGetCountryFlag(): void
    {
        $pathsHelper = $this->getMockBuilder(PathsHelper::class)
             ->disableOriginalConstructor()
             ->getMock();
        $pathsHelper->method('getSystemPath')->willReturn('');

        $assetHelper = new AssetsHelper($this->createPackagesMock());
        $assetHelper->setPathsHelper($pathsHelper);

        $extension = new AssetExtension($assetHelper);

        Assert::assertSame('', $extension->getCountryFlag('US'));
    }
}
