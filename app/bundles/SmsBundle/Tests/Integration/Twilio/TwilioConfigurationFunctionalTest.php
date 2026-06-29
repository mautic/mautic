<?php

declare(strict_types=1);

namespace Mautic\SmsBundle\Tests\Integration\Twilio;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\PluginBundle\Entity\Integration;
use Mautic\SmsBundle\Integration\TwilioIntegration;
use Mautic\SmsBundle\Tests\SmsTestHelperTrait;
use PHPUnit\Framework\Assert;

final class TwilioConfigurationFunctionalTest extends MauticMysqlTestCase
{
    use SmsTestHelperTrait;

    public function testSaveTwilioConfig(): void
    {
        $this->configureTwilioWithArrayTransport();

        $integration = $this->getContainer()->get('mautic.integration.twilio');
        $this->assertInstanceOf(TwilioIntegration::class, $integration);

        $integrationRepository = $this->em->getRepository(Integration::class);

        $integrationConfig = $integrationRepository->findOneBy(['name' => $integration->getName()]);
        $this->assertInstanceOf(Integration::class, $integrationConfig);
        Assert::assertSame('messaging_sid', $integrationConfig->getFeatureSettings()['messaging_service_sid']);
    }
}
