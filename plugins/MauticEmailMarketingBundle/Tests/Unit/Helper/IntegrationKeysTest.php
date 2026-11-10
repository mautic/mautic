<?php

declare(strict_types=1);

namespace MauticPlugin\MauticEmailMarketingBundle\Tests\Unit\Helper;

use Mautic\IntegrationsBundle\Exception\IntegrationNotFoundException;
use Mautic\IntegrationsBundle\Helper\IntegrationsHelper;
use Mautic\IntegrationsBundle\Integration\Interfaces\IntegrationInterface;
use Mautic\PluginBundle\Entity\Integration;
use MauticPlugin\MauticEmailMarketingBundle\Helper\IntegrationKeys;
use PHPUnit\Framework\TestCase;

final class IntegrationKeysTest extends TestCase
{
    public function testReturnsTheDecryptedKeys(): void
    {
        $configuration = new Integration();
        $configuration->setApiKeys(['password' => 'key-us7']);

        $integration = $this->createStub(IntegrationInterface::class);
        $integration->method('getIntegrationConfiguration')->willReturn($configuration);

        $integrationsHelper = $this->createMock(IntegrationsHelper::class);
        $integrationsHelper->expects($this->once())->method('getIntegration')->with('Mailchimp')->willReturn($integration);

        $this->assertSame(['password' => 'key-us7'], new IntegrationKeys($integrationsHelper)->get('Mailchimp'));
    }

    public function testReturnsNoKeysWithoutSavedSettings(): void
    {
        $integrationsHelper = $this->createStub(IntegrationsHelper::class);
        $integrationsHelper->method('getIntegration')->willThrowException(new IntegrationNotFoundException());

        $this->assertSame([], new IntegrationKeys($integrationsHelper)->get('Mailchimp'));
    }
}
