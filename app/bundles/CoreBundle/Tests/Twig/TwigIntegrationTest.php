<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Twig;

use Mautic\CoreBundle\Templating\Twig\Extension\AppExtension;
use Mautic\CoreBundle\Templating\Twig\Extension\AssetExtension;
use Mautic\CoreBundle\Templating\Twig\Extension\ClassExtension;
use Mautic\CoreBundle\Templating\Twig\Extension\FormExtension;
use Mautic\CoreBundle\Tests\Twig\Fakes\AssetsHelperFake;
use Mautic\CoreBundle\Tests\Twig\Fakes\FormHelperFake;
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
            new FormExtension(new FormHelperFake()),
        ];
    }

    public function getFixturesDir()
    {
        return __DIR__.'/Fixtures/';
    }
}
