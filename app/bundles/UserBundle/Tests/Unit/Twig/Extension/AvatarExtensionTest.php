<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Tests\Unit\Twig\Extension;

use Composer\InstalledVersions;
use DiceBear\Avatar;
use DiceBear\Style;
use Mautic\UserBundle\Twig\Extension\AvatarExtension;
use PHPUnit\Framework\TestCase;

final class AvatarExtensionTest extends TestCase
{
    public function testAvatarUsesDefaultLineFaceStyle(): void
    {
        $definition = file_get_contents(InstalledVersions::getInstallPath('dicebear/styles').'/src/line-face.json');
        $this->assertIsString($definition);
        $expected = (new Avatar(Style::fromJson($definition), ['seed' => 'user-1']))->toDataUri();
        $extension = new AvatarExtension();

        $this->assertSame($expected, $extension->getImage(1));
        $this->assertStringStartsWith('data:image/svg+xml;', $extension->getImage(1));
    }

    public function testAvatarsAreStableAndVaryBetweenUsers(): void
    {
        $extension = new AvatarExtension();

        $this->assertSame($extension->getImage(1), $extension->getImage(1));
        $this->assertSame($extension->getImage(1), (new AvatarExtension())->getImage(1));
        $this->assertNotSame($extension->getImage(1), $extension->getImage(2));
    }

    public function testTwigFunction(): void
    {
        $extension = new AvatarExtension();
        $functions = $extension->getFunctions();

        $this->assertCount(1, $functions);
        $this->assertSame('userAvatar', $functions[0]->getName());
        $this->assertSame($extension->getImage(1), ($functions[0]->getCallable())(1));
    }
}
