<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Templating\Twig\Extension;

use Mautic\CoreBundle\Templating\Twig\Extension\AssetExtension;
use Mautic\CoreBundle\Test\AbstractMauticTestCase;
use PHPUnit\Framework\Assert;

class AssetExtensionTest extends AbstractMauticTestCase
{
    public function testGetCountryFlag(): void
    {
        $assetExtension = self::$container->get(AssetExtension::class);
        \assert($assetExtension instanceof AssetExtension);

        Assert::assertStringStartsWith('/media/images/flags/Belgium.png', $assetExtension->getCountryFlag('Belgium'));
    }
}
