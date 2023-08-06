<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Twig\Extension;

use Mautic\CoreBundle\Test\AbstractMauticTestCase;
use Mautic\CoreBundle\Twig\Extension\AssetExtension;
use PHPUnit\Framework\Assert;
use Symfony\Component\DomCrawler\Crawler;

class AssetExtensionTest extends AbstractMauticTestCase
{
    public function testGetCountryFlag(): void
    {
        $assetExtension = self::$container->get(AssetExtension::class);
        \assert($assetExtension instanceof AssetExtension);

        Assert::assertStringStartsWith('/app/assets/images/flags/Belgium.png', $assetExtension->getCountryFlag('Belgium'));
        Assert::assertStringStartsWith('/app/assets/images/flags/Belgium.png', $assetExtension->getCountryFlag('be'));
        Assert::assertEquals('', $assetExtension->getCountryFlag('bambule'));

        Assert::assertStringStartsWith('/app/assets/images/flags/Belgium.png',
            (new Crawler($assetExtension->getCountryFlag('be', false)))->filter('img')->attr('src')
        );
    }
}
