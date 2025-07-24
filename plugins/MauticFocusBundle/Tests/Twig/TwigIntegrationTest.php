<?php

declare(strict_types=1);

namespace MauticPlugin\MauticFocusBundle\Tests\Twig;

use MauticPlugin\MauticFocusBundle\Twig\Extension\FocusBundleExtension;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use Twig\Extension\ExtensionInterface;

/**
 * @see https://twig.symfony.com/doc/2.x/advanced.html#functional-tests
 */
/**
 * Temporary workaround for Twig test deprecations.
 * Remove after upgrading to twig/twig 4.0.
 */
#[IgnoreDeprecations]
class TwigIntegrationTest extends \Twig\Test\IntegrationTestCase
{
    /**
     * @return ExtensionInterface[]
     */
    public function getExtensions(): array
    {
        return [
            new FocusBundleExtension(),
        ];
    }

    public static function getFixturesDirectory(): string
    {
        return __DIR__.'/Fixtures/';
    }
}
