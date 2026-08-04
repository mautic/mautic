<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\Tests\Service;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\MarketplaceBundle\Service\Config;
use PHPUnit\Framework\TestCase;

final class ConfigTest extends TestCase
{
    /**
     * MAUTIC_MARKETPLACE_API_KEY is unset on a stock install, and the parameter then resolves to
     * null. Browsing the marketplace still has to work, so the built-in key stands in.
     */
    public function testApiKeyFallsBackToTheBuiltInKeyWhenNoneIsConfigured(): void
    {
        foreach ([null, ''] as $configured) {
            $config = new Config($this->parametersReturning($configured));

            $this->assertNotSame('', $config->getApiKey());
        }
    }

    public function testConfiguredApiKeyWins(): void
    {
        $config = new Config($this->parametersReturning('key-from-the-environment'));

        $this->assertSame('key-from-the-environment', $config->getApiKey());
    }

    private function parametersReturning(?string $value): CoreParametersHelper
    {
        $parameters = $this->createMock(CoreParametersHelper::class);
        $parameters->method('get')->with(Config::MARKETPLACE_API_KEY)->willReturn($value);

        return $parameters;
    }
}
