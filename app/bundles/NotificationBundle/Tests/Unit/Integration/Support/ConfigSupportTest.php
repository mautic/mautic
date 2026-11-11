<?php

declare(strict_types=1);

namespace Mautic\NotificationBundle\Tests\Unit\Integration\Support;

use Mautic\NotificationBundle\Form\Type\OneSignalFeatureSettingsType;
use Mautic\NotificationBundle\Form\Type\OneSignalKeysType;
use Mautic\NotificationBundle\Integration\OneSignalIntegration;
use Mautic\NotificationBundle\Integration\Support\ConfigSupport;
use Mautic\PluginBundle\Entity\Integration;
use PHPUnit\Framework\TestCase;

final class ConfigSupportTest extends TestCase
{
    public function testConfigFormNames(): void
    {
        $configSupport = new ConfigSupport();

        $this->assertSame(OneSignalKeysType::class, $configSupport->getAuthConfigFormName());
        $this->assertSame(OneSignalFeatureSettingsType::class, $configSupport->getFeatureSettingsConfigFormName());
    }

    public function testConfigFormGetsSlugToLabelMapWhileBaseKeepsSlugList(): void
    {
        $this->assertSame(
            [
                'mobile'                       => 'mautic.integration.form.feature.mobile',
                'landing_page_enabled'         => 'mautic.integration.form.feature.landing_page_enabled',
                'welcome_notification_enabled' => 'mautic.integration.form.feature.welcome_notification_enabled',
                'tracking_page_enabled'        => 'mautic.integration.form.feature.tracking_page_enabled',
            ],
            new ConfigSupport()->getSupportedFeatures()
        );

        $this->assertSame(
            ['mobile', 'landing_page_enabled', 'welcome_notification_enabled', 'tracking_page_enabled'],
            new OneSignalIntegration()->getSupportedFeatures()
        );
    }

    public function testIntegrationFeatureSettingsAreReadFromTheIntegrationKey(): void
    {
        $integration = new OneSignalIntegration();
        $this->assertSame([], $integration->getIntegrationFeatureSettings());

        $settings = new Integration();
        $settings->setFeatureSettings(['integration' => ['subdomain_name' => 'acme', 'platforms' => ['ios']]]);
        $integration->setIntegrationSettings($settings);

        $this->assertSame(['subdomain_name' => 'acme', 'platforms' => ['ios']], $integration->getIntegrationFeatureSettings());
    }
}
