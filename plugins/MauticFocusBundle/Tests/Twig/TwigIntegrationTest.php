<?php

declare(strict_types=1);

namespace MauticPlugin\MauticFocusBundle\Tests\Twig;

use MauticPlugin\MauticFocusBundle\Twig\Extension\FocusBundleExtension;
use Twig\Extension\ExtensionInterface;

/**
 * @see https://twig.symfony.com/doc/3.x/advanced.html#functional-tests
 */
final class TwigIntegrationTest extends \Twig\Test\IntegrationTestCase
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
}
