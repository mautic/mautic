<?php

declare(strict_types=1);

namespace Mautic\SmsBundle\Tests\Integration\Twilio;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\PluginBundle\Entity\Integration;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TwilioConfigurationFunctionalTest extends MauticMysqlTestCase
{
    public function testSaveTwilioConfig(): void
    {
        $messagingServiceSid = 'messaging_sid';
        $integration         = $this->getContainer()->get('mautic.integration.twilio');
        $crawler             = $this->client->request(Request::METHOD_GET, 's/plugins/config/'.$integration->getName());
        $response            = $this->client->getResponse();

        Assert::assertSame(Response::HTTP_OK, $response->getStatusCode(), $response->getContent());

        $saveButton = $crawler->selectButton('integration_details[buttons][save]');
        $form       = $saveButton->form();

        $form['integration_details[apiKeys][username]']->setValue('test_username');
        $form['integration_details[apiKeys][password]']->setValue('test_password');
        $form['integration_details[isPublished]']->setValue('1');
        $form['integration_details[featureSettings][messaging_service_sid]']->setValue($messagingServiceSid);

        $this->client->submit($form);

        $response = $this->client->getResponse();
        Assert::assertSame(Response::HTTP_OK, $response->getStatusCode(), $response->getContent());

        $integrationRepository = $this->em->getRepository(Integration::class);

        $integrationConfig = $integrationRepository->findOneBy(['name' => $integration->getName()]);
        \assert($integrationConfig instanceof Integration);
        Assert::assertSame($messagingServiceSid, $integrationConfig->getFeatureSettings()['messaging_service_sid']);
    }
}
