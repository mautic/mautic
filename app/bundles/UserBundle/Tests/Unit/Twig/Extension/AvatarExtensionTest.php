<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Unit\Twig\Extension;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Twig\Helper\AssetsHelper;
use Mautic\LeadBundle\Twig\Helper\DefaultAvatarHelper;
use Mautic\UserBundle\Twig\Extension\AvatarExtension;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Asset\Packages;

final class AvatarExtensionTest extends TestCase
{
    public function testAvatarUsesDefaultLineFaceStyle(): void
    {
        $image = $this->createExtension()->getImage(1);

        $this->assertStringStartsWith('data:image/svg+xml;', $image);
        $this->assertSame(
            file_get_contents(__DIR__.'/../../../Fixtures/line-face-user.svg'),
            rawurldecode(substr($image, (int) strpos($image, ',') + 1))
        );
    }

    public function testAvatarsAreStableAndVaryBetweenUsers(): void
    {
        $extension = $this->createExtension();

        $this->assertSame($extension->getImage(1), $extension->getImage(1));
        $this->assertSame($extension->getImage(1), $this->createExtension()->getImage(1));
        $this->assertNotSame($extension->getImage(1), $extension->getImage(2));
    }

    public function testAvatarsVaryBetweenSiteUrls(): void
    {
        $first = $this->createExtension('https://first.example')->getImage(1);
        $second = $this->createExtension('https://second.example')->getImage(1);
        $third = $this->createExtension('https://third.example')->getImage(1);

        $this->assertNotSame($first, $second);
        $this->assertNotSame($first, $third);
        $this->assertNotSame($second, $third);
    }

    public function testSiteUrlIncludesInstallationPath(): void
    {
        $this->assertNotSame(
            $this->createExtension('https://mautic.example/first')->getImage(1),
            $this->createExtension('https://mautic.example/second')->getImage(1)
        );
    }

    public function testTrailingSlashDoesNotChangeAvatar(): void
    {
        $this->assertSame(
            $this->createExtension('https://mautic.example/')->getImage(1),
            $this->createExtension('https://mautic.example')->getImage(1)
        );
    }

    public function testTwigFunction(): void
    {
        $extension = $this->createExtension();
        $functions = $extension->getFunctions();

        $this->assertCount(1, $functions);
        $this->assertSame('userAvatar', $functions[0]->getName());
        $this->assertSame($extension->getImage(1), ($functions[0]->getCallable())(1));
    }

    public function testMissingStyleFileFallsBackToDefaultAvatarAndLogsOnce(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error')->with('Line Face avatar style file not found.');

        $extension = $this->createFallbackExtension($logger, sys_get_temp_dir().'/missing-line-face.json');

        $this->assertSame('http://localhost/images/avatar.png', $extension->getImage(1), 'Default avatar should be used.');
        $this->assertSame('http://localhost/images/avatar.png', $extension->getImage(2), 'Default avatar should be used.');
    }

    public function testInvalidStyleFileFallsBackToDefaultAvatarAndLogsOnce(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'line-face');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error')->with('Unable to load the Line Face avatar style.');

        try {
            file_put_contents($file, '{not json');
            $extension = $this->createFallbackExtension($logger, $file);

            $this->assertSame('http://localhost/images/avatar.png', $extension->getImage(1), 'Default avatar should be used.');
            $this->assertSame('http://localhost/images/avatar.png', $extension->getImage(2), 'Default avatar should be used.');
        } finally {
            unlink($file);
        }
    }

    private function createFallbackExtension(LoggerInterface $logger, string $styleFile): AvatarExtension
    {
        $paths = $this->createStub(PathsHelper::class);
        $paths->method('getSystemPath')->willReturn('http://localhost');
        $paths->method('getAssetsPath')->willReturn(dirname(__DIR__, 7).'/app/assets');
        $paths->method('getMediaPath')->willReturn(dirname(__DIR__, 7).'/media');

        $assets = new AssetsHelper($this->createStub(Packages::class));
        $assets->setPathsHelper($paths);

        return new AvatarExtension($this->createStub(CoreParametersHelper::class), new DefaultAvatarHelper($assets), $logger, $styleFile);
    }

    private function createExtension(string $siteUrl = 'https://mautic.example'): AvatarExtension
    {
        $parameters = $this->createMock(CoreParametersHelper::class);
        $parameters->method('get')->with('site_url')->willReturn($siteUrl);

        $assets = (new \ReflectionClass(AssetsHelper::class))->newInstanceWithoutConstructor();

        return new AvatarExtension($parameters, new DefaultAvatarHelper($assets), $this->createStub(LoggerInterface::class));
    }
}
