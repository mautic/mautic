<?php

declare(strict_types=1);

namespace MauticPlugin\MauticFocusBundle\Tests\Twig;

use MauticPlugin\MauticFocusBundle\Twig\Extension\FocusBundleExtension;
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
        return [
            new FocusBundleExtension(),
        ];
    }

    public function getFixturesDir()
    {
        return __DIR__.'/Fixtures/';
    }
}
