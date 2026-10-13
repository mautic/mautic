<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Twig\Helper;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Twig\Helper\AssetsHelper;
use Mautic\CoreBundle\Twig\Helper\GravatarHelper;
use Mautic\LeadBundle\Twig\Helper\DefaultAvatarHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\RequestStack;

final class GravatarHelperTest extends TestCase
{
    public function testImageIsLoadedFromGravatarWhenEnabled(): void
    {
        $image = $this->createGravatarHelper(true)->getImage('John@Example.com', '100');

        $this->assertStringStartsWith('https://www.gravatar.com/avatar/'.md5('john@example.com').'?s=100', $image);
    }

    #[DataProvider('disabledSettings')]
    public function testDefaultAvatarIsUsedWhenGravatarIsNotEnabled(?bool $enabled): void
    {
        $image = $this->createGravatarHelper($enabled)->getImage('john@example.com');

        $this->assertSame($this->createDefaultAvatarHelper()->getDefaultAvatar(true), $image);
    }

    /**
     * @return iterable<string, array{?bool}>
     */
    public static function disabledSettings(): iterable
    {
        yield 'disabled' => [false];
        yield 'not configured' => [null];
    }

    private function createDefaultAvatarHelper(): DefaultAvatarHelper
    {
        $pathsHelper = $this->createStub(PathsHelper::class);
        $pathsHelper->method('getSystemPath')->willReturn('http://localhost');

        $assetsHelper = new AssetsHelper($this->createStub(Packages::class));
        $assetsHelper->setPathsHelper($pathsHelper);

        return new DefaultAvatarHelper($assetsHelper);
    }

    private function createGravatarHelper(?bool $enabled): GravatarHelper
    {
        $coreParametersHelper = $this->createStub(CoreParametersHelper::class);
        $coreParametersHelper->method('get')->willReturnCallback(
            static fn (string $name, mixed $default = null): mixed => match ($name) {
                'gravatar_enabled' => $enabled ?? $default,
                default            => $default,
            }
        );

        return new GravatarHelper($this->createDefaultAvatarHelper(), $coreParametersHelper, $this->createStub(RequestStack::class));
    }
}
