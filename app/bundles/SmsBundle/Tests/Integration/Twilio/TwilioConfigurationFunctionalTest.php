<?php

declare(strict_types=1);

namespace Mautic\SmsBundle\Tests\Integration\Twilio;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\PluginBundle\Entity\Integration;
use Mautic\PluginBundle\Entity\IntegrationRepository;
use Mautic\SmsBundle\Integration\TwilioIntegration;
use Mautic\SmsBundle\Tests\SmsTestHelperTrait;

final class TwilioConfigurationFunctionalTest extends MauticMysqlTestCase
{
    use SmsTestHelperTrait;

    public function testSaveTwilioConfig(): void
    {
        $this->configureTwilioWithArrayTransport();

        $integration = $this->getService(TwilioIntegration::class);
        $this->assertInstanceOf(TwilioIntegration::class, $integration);

        $integrationRepository = $this->getService(IntegrationRepository::class);

        $integrationConfig = $integrationRepository->findOneBy(['name' => $integration->getName()]);
        $this->assertInstanceOf(Integration::class, $integrationConfig);
        $this->assertSame('messaging_sid', $integrationConfig->getFeatureSettings()['messaging_service_sid']);
    }
}
