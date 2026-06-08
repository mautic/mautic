<?php

declare(strict_types=1);

namespace MauticPlugin\MauticFocusBundle\Tests\Twig;

use Mautic\CoreBundle\Tests\Twig\TwigIntegrationTestTrait;
use MauticPlugin\MauticFocusBundle\Twig\Extension\FocusBundleExtension;
use Twig\Extension\ExtensionInterface;

/**
 * @see https://twig.symfony.com/doc/3.x/advanced.html#functional-tests
 */
class TwigIntegrationTest extends \Twig\Test\IntegrationTestCase
{
    use TwigIntegrationTestTrait;

    /**
     * @return ExtensionInterface[]
     */
    public function getExtensions(): array
    {
        return [
            new FocusBundleExtension(),
        ];
    }
}
