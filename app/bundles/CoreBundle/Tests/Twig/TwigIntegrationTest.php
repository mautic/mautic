<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Twig;

use Mautic\CoreBundle\Tests\Twig\Fakes\AssetsHelperFake;
use Mautic\CoreBundle\Twig\Extension\AppExtension;
use Mautic\CoreBundle\Twig\Extension\AssetExtension;
use Mautic\CoreBundle\Twig\Extension\ClassExtension;
use Mautic\CoreBundle\Twig\Extension\FormExtension;
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
            new AppExtension(),
            new AssetExtension(new AssetsHelperFake()),
            new ClassExtension(),
            new FormExtension(),
        ];
    }

    public function getFixturesDir()
    {
        return __DIR__.'/Fixtures/';
    }
}
