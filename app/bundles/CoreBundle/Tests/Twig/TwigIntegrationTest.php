<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Twig;

use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Twig\Extension\AppExtension;
use Mautic\CoreBundle\Twig\Extension\AssetExtension;
use Mautic\CoreBundle\Twig\Extension\ClassExtension;
use Mautic\CoreBundle\Twig\Extension\FormExtension;
use Mautic\CoreBundle\Twig\Helper\AssetsHelper;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Asset\Packages;
use Twig\Extension\ExtensionInterface;

/**
 * @see https://twig.symfony.com/doc/2.x/advanced.html#functional-tests
 */
class TwigIntegrationTest extends \Twig\Test\IntegrationTestCase
{
    /**
     * @return ExtensionInterface[]
     */
    public function getExtensions(): array
    {
        /** @var Packages&MockObject $packagesMock */
        $packagesMock = $this->createMock(Packages::class);

        /** @var PathsHelper&MockObject $pathHelperMock */
        $pathHelperMock = $this->createMock(PathsHelper::class);

        $packagesMock->method('getUrl')
            ->will($this->returnCallback(function (string $path) {
                $packageName = $version = null;
                $absolute    = $ignorePrefix = false;

                return "{$path}/{$packageName}/{$version}/{$absolute}/{$ignorePrefix}}";
            }));

        $assetsHelper = new AssetsHelper($packagesMock);
        $pathHelperMock->method('getSystemPath')->willReturn('https://example.com/');
        $assetsHelper->setPathsHelper($pathHelperMock);

        return [
            new AppExtension(),
            new AssetExtension($assetsHelper),
            new ClassExtension(),
            new FormExtension(),
        ];
    }

    public function getFixturesDir()
    {
        return __DIR__.'/Fixtures/';
    }
}
