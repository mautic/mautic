<?php

declare(strict_types=1);

namespace Mautic\SmsBundle\Tests\Unit\Integration\Support;

use Mautic\PluginBundle\Entity\Integration;
use Mautic\SmsBundle\Form\Type\TwilioFeatureSettingsType;
use Mautic\SmsBundle\Form\Type\TwilioKeysType;
use Mautic\SmsBundle\Integration\Support\ConfigSupport;
use Mautic\SmsBundle\Integration\TwilioIntegration;
use PHPUnit\Framework\TestCase;

final class ConfigSupportTest extends TestCase
{
    public function testConfigFormNames(): void
    {
        $configSupport = new ConfigSupport();

        $this->assertSame(TwilioKeysType::class, $configSupport->getAuthConfigFormName());
        $this->assertSame(TwilioFeatureSettingsType::class, $configSupport->getFeatureSettingsConfigFormName());
        $this->assertNull($configSupport->getConfigFormName());
        $this->assertNull($configSupport->getConfigFormContentTemplate());
    }

    public function testIntegrationFeatureSettingsAreReadFromTheIntegrationKey(): void
    {
        $integration = new TwilioIntegration();
        $this->assertSame([], $integration->getIntegrationFeatureSettings());

        $settings = new Integration();
        $settings->setFeatureSettings(['integration' => ['messaging_service_sid' => 'MG123']]);
        $integration->setIntegrationSettings($settings);

        $this->assertSame(['messaging_service_sid' => 'MG123'], $integration->getIntegrationFeatureSettings());
    }
}
