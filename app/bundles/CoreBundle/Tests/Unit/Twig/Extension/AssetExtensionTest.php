<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Twig\Extension;

use Mautic\CoreBundle\Test\AbstractMauticTestCase;
use Mautic\CoreBundle\Twig\Extension\AssetExtension;
use PHPUnit\Framework\Assert;

final class AssetExtensionTest extends AbstractMauticTestCase
{
    public function testGetCountryFlag(): void
    {
        $assetExtension = static::getContainer()->get(AssetExtension::class);
        $this->assertInstanceOf(AssetExtension::class, $assetExtension);

        Assert::assertStringStartsWith('/./app/assets/images/flags/Belgium.png', $assetExtension->getCountryFlag('Belgium'));
    }
}
