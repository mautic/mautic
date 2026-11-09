<?php

declare(strict_types=1);

namespace Mautic\SmsBundle\Tests;

use Mautic\SmsBundle\Integration\TwilioIntegration;
use Mautic\SmsBundle\Sms\TransportChain;
use PHPUnit\Framework\Assert;
use Symfony\Component\DomCrawler\Field\ChoiceFormField;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

trait SmsTestHelperTrait
{
    private function configureTwilioWithArrayTransport(): ArrayTransport
    {
        $this->testSymfonyCommand('mautic:plugins:install');
        $messagingServiceSid = 'messaging_sid';

        $integration = $this->getContainer()->get(TwilioIntegration::class);

        // Like any admin page, the plugin list creates the integration settings rows the config page loads.
        $this->client->request(Request::METHOD_GET, '/s/plugins');

        $crawler  = $this->client->request(Request::METHOD_GET, '/s/plugins/config/'.$integration->getName());
        $response = $this->client->getResponse();

        Assert::assertSame(Response::HTTP_OK, $response->getStatusCode(), (string) $response->getContent());

        $saveButton = $crawler->selectButton('integration_config[buttons][save]');
        $form       = $saveButton->form();

        $form['integration_config[apiKeys][username]']->setValue('test_username');
        $form['integration_config[apiKeys][password]']->setValue('test_password');
        $isPublished = $form['integration_config[isPublished]'];
        Assert::assertInstanceOf(ChoiceFormField::class, $isPublished);
        $isPublished->select('1');
        $form['integration_config[featureSettings][integration][messaging_service_sid]']->setValue($messagingServiceSid);

        $this->client->submit($form);

        $response = $this->client->getResponse();
        Assert::assertSame(Response::HTTP_OK, $response->getStatusCode(), (string) $response->getContent());

        $transportChain = $this->getContainer()->get(TransportChain::class);

        // Replaces Twilio transport with ArrayTransport
        $transport = new ArrayTransport();
        $transportChain->addTransport('mautic.sms.twilio.transport', $transport, 'Array SMS Transport', 'Twilio');

        return $transport;
    }
}
