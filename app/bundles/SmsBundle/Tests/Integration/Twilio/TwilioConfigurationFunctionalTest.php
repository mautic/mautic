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
        \assert($integration instanceof TwilioIntegration);

        $integrationRepository = $this->em->getRepository(Integration::class);

        $integrationConfig = $integrationRepository->findOneBy(['name' => $integration->getName()]);
        \assert($integrationConfig instanceof Integration);
        Assert::assertSame('messaging_sid', $integrationConfig->getFeatureSettings()['messaging_service_sid']);
    }
}
