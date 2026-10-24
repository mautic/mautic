<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Unit\Twig\Extension;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Twig\Helper\AssetsHelper;
use Mautic\LeadBundle\Twig\Helper\DefaultAvatarHelper;
use Mautic\UserBundle\Twig\Extension\AvatarExtension;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

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

    private function createExtension(string $siteUrl = 'https://mautic.example'): AvatarExtension
    {
        $parameters = $this->createMock(CoreParametersHelper::class);
        $parameters->method('get')->with('site_url')->willReturn($siteUrl);

        $assets = (new \ReflectionClass(AssetsHelper::class))->newInstanceWithoutConstructor();

        return new AvatarExtension($parameters, new DefaultAvatarHelper($assets), $this->createStub(LoggerInterface::class));
    }
}
