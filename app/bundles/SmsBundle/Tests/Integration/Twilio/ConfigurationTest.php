<?php

declare(strict_types=1);

namespace Mautic\SmsBundle\Tests\Integration\Twilio;

use Mautic\IntegrationsBundle\Exception\IntegrationNotFoundException;
use Mautic\IntegrationsBundle\Helper\IntegrationsHelper;
use Mautic\PluginBundle\Entity\Integration;
use Mautic\SmsBundle\Integration\Twilio\Configuration;
use Mautic\SmsBundle\Integration\TwilioIntegration;
use PHPUnit\Framework\TestCase;
use Twilio\Exceptions\ConfigurationException;

final class ConfigurationTest extends TestCase
{
    public function testGetMessagingServiceSid(): void
    {
        $this->assertSame('123', $this->getConfiguration($this->createSettings())->getMessagingServiceSid());
    }

    public function testGetAccountSid(): void
    {
        $this->assertSame('username', $this->getConfiguration($this->createSettings())->getAccountSid());
    }

    public function testGetAuthToken(): void
    {
        $this->assertSame('password', $this->getConfiguration($this->createSettings())->getAuthToken());
    }

    public function testConfigurationExceptionThrownIfNotPublished(): void
    {
        $this->expectException(ConfigurationException::class);

        $this->getConfiguration($this->createSettings(isPublished: false))->getMessagingServiceSid();
    }

    public function testConfigurationExceptionThrownWithoutSettingsRow(): void
    {
        $this->expectException(ConfigurationException::class);

        $integrationsHelper = $this->createStub(IntegrationsHelper::class);
        $integrationsHelper->method('getIntegrationConfiguration')->willThrowException(new IntegrationNotFoundException());

        new Configuration(new TwilioIntegration(), $integrationsHelper)->getMessagingServiceSid();
    }

    public function testConfigurationExceptionThrownWithoutMessagingServiceSId(): void
    {
        $this->expectException(ConfigurationException::class);

        $this->getConfiguration($this->createSettings(messagingServiceSid: ''))->getMessagingServiceSid();
    }

    public function testConfigurationExceptionThrownWithoutUsername(): void
    {
        $this->expectException(ConfigurationException::class);

        $this->getConfiguration($this->createSettings(username: ''))->getMessagingServiceSid();
    }

    public function testConfigurationExceptionThrownWithoutPassword(): void
    {
        $this->expectException(ConfigurationException::class);

        $this->getConfiguration($this->createSettings(password: ''))->getMessagingServiceSid();
    }

    private function createSettings(
        bool $isPublished = true,
        string $messagingServiceSid = '123',
        string $username = 'username',
        string $password = 'password',
    ): Integration {
        $settings = new Integration();
        $settings->setIsPublished($isPublished);
        $settings->setFeatureSettings(['integration' => ['messaging_service_sid' => $messagingServiceSid]]);
        $settings->setApiKeys(['username' => $username, 'password' => $password]);

        return $settings;
    }

    private function getConfiguration(Integration $settings): Configuration
    {
        $integration = new TwilioIntegration();

        // IntegrationsHelper hydrates the integration and returns its settings with decrypted keys
        $integrationsHelper = $this->createStub(IntegrationsHelper::class);
        $integrationsHelper->method('getIntegrationConfiguration')->willReturnCallback(
            function (TwilioIntegration $twilio) use ($settings): Integration {
                $twilio->setIntegrationConfiguration($settings);

                return $settings;
            }
        );

        return new Configuration($integration, $integrationsHelper);
    }
}
