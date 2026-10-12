<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Unit\Twig\Extension;

use Composer\InstalledVersions;
use DiceBear\Avatar;
use DiceBear\Style;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\UserBundle\Twig\Extension\AvatarExtension;
use PHPUnit\Framework\TestCase;

final class AvatarExtensionTest extends TestCase
{
    public function testAvatarUsesDefaultLineFaceStyle(): void
    {
        $definition = file_get_contents(InstalledVersions::getInstallPath('dicebear/styles').'/src/line-face.json');
        $this->assertIsString($definition);
        $expected = (new Avatar(Style::fromJson($definition), ['seed' => 'https://mautic.example/user-1']))->toDataUri();
        $extension = $this->createExtension();

        $this->assertSame($expected, $extension->getImage(1));
        $this->assertStringStartsWith('data:image/svg+xml;', $extension->getImage(1));
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

        return new AvatarExtension($parameters);
    }
}
