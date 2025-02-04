<?php

declare(strict_types=1);

namespace Mautic\SmsBundle\Tests;

use Mautic\SmsBundle\Integration\TwilioIntegration;
use Mautic\SmsBundle\Sms\TransportChain;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

trait SmsTestHelperTrait
{
    private function configureTwilioWithArrayTransport(): ArrayTransport
    {
        $this->testSymfonyCommand('mautic:plugins:install');
        $messagingServiceSid = 'messaging_sid';

        $integration = $this->getContainer()->get('mautic.integration.twilio');
        \assert($integration instanceof TwilioIntegration);

        $crawler  = $this->client->request(Request::METHOD_GET, 's/plugins/config/'.$integration->getName());
        $response = $this->client->getResponse();

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

        $transportChain = $this->getContainer()->get('mautic.sms.transport_chain');
        \assert($transportChain instanceof TransportChain);

        // Replaces Twilio transport with ArrayTransport
        $transport = new ArrayTransport();
        $transportChain->addTransport('mautic.sms.twilio.transport', $transport, 'Array SMS Transport', 'Twilio');

        return $transport;
    }
}
