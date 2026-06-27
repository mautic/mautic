<?php

declare(strict_types=1);

namespace MauticPlugin\MauticFocusBundle\Tests\Twig;

use Mautic\CoreBundle\Tests\Twig\AbstractTwigIntegrationTestCase;
use MauticPlugin\MauticFocusBundle\Twig\Extension\FocusBundleExtension;
use Twig\Extension\ExtensionInterface;

final class TwigIntegrationTest extends AbstractTwigIntegrationTestCase
{
    /**
     * @return ExtensionInterface[]
     */
    protected function getExtensions(): array
    {
        return [
            new FocusBundleExtension(),
        ];
    }

    protected static function getFixturesDirectory(): string
    {
        return __DIR__.'/Fixtures';
    }
}
