<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Twig\Extension;

use Mautic\CoreBundle\Test\AbstractMauticTestCase;
use Mautic\CoreBundle\Twig\Extension\AssetExtension;

final class AssetExtensionTest extends AbstractMauticTestCase
{
    public function testGetCountryFlag(): void
    {
        $assetExtension = $this->getService(AssetExtension::class);

        $this->assertStringStartsWith('/./app/assets/images/flags/Belgium.png', $assetExtension->getCountryFlag('Belgium'));
    }
}
