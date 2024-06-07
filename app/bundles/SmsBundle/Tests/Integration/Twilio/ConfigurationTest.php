<?php

namespace Mautic\SmsBundle\Tests\Integration\Twilio;

use Mautic\PluginBundle\Entity\Integration;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use Mautic\PluginBundle\Integration\AbstractIntegration;
use Mautic\SmsBundle\Integration\Twilio\Configuration;
use Twilio\Exceptions\ConfigurationException;

class ConfigurationTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var IntegrationHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private \PHPUnit\Framework\MockObject\MockObject $integrationHelper;

    /**
     * @var AbstractIntegration|\PHPUnit\Framework\MockObject\MockObject
     */
    private \PHPUnit\Framework\MockObject\MockObject $integrationObject;

    protected function setUp(): void
    {
        $this->integrationHelper = $this->createMock(IntegrationHelper::class);

        $integrationSettings = new Integration();
        $integrationSettings->setIsPublished(true);
        $integrationSettings->setFeatureSettings(['messaging_service_sid' => '123']);
        $this->integrationObject = $this->createMock(AbstractIntegration::class);
        $this->integrationObject->method('getIntegrationSettings')
            ->willReturn($integrationSettings);

        $this->integrationHelper->method('getIntegrationObject')
            ->with('Twilio')
            ->willReturn($this->integrationObject);
    }

    public function testGetMessagingServiceSid(): void
    {
        $this->integrationObject->method('getDecryptedApiKeys')
            ->willReturn(
                [
                    'username' => 'username',
                    'password' => 'password',
                ]
            );
        $this->assertEquals('123', $this->getConfiguration()->getMessagingServiceSid());
    }

    public function testGetAccountSid(): void
    {
        $this->integrationObject->method('getDecryptedApiKeys')
            ->willReturn(
                [
                    'username' => 'username',
                    'password' => 'password',
                ]
            );
        $this->assertEquals('username', $this->getConfiguration()->getAccountSid());
    }

    public function testGetAuthToken(): void
    {
        $this->integrationObject->method('getDecryptedApiKeys')
            ->willReturn(
                [
                    'username' => 'username',
                    'password' => 'password',
                ]
            );
        $this->assertEquals('password', $this->getConfiguration()->getAuthToken());
    }

    public function testConfigurationExceptionThrownIfNotPublished(): void
    {
        $this->expectException(ConfigurationException::class);

        $integrationSettings = new Integration();
        $integrationSettings->setIsPublished(false);
        $integrationSettings->setFeatureSettings(['messaging_service_sid' => '123']);

        $this->integrationObject->method('getIntegrationSettings')
            ->willReturn($integrationSettings);

        $this->getConfiguration()->getMessagingServiceSid();
    }

    public function testConfigurationExceptionThrownWithoutMessagingServiceSId(): void
    {
        $this->expectException(ConfigurationException::class);

        $this->integrationObject->getIntegrationSettings()->setFeatureSettings(['messaging_service_sid' => '']);

        $this->getConfiguration()->getMessagingServiceSid();
    }

    public function testConfigurationExceptionThrownWithoutUsername(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->integrationObject->method('getDecryptedApiKeys')
            ->willReturn(
                [
                    'username' => '',
                    'password' => 'password',
                ]
            );
        $this->getConfiguration()->getMessagingServiceSid();
    }

    public function testConfigurationExceptionThrownWithoutPassword(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->integrationObject->method('getDecryptedApiKeys')
            ->willReturn(
                [
                    'username' => 'username',
                    'password' => '',
                ]
            );
        $this->getConfiguration()->getMessagingServiceSid();
    }

    /**
     * @return Configuration
     */
    private function getConfiguration()
    {
        return new Configuration($this->integrationHelper);
    }
}
