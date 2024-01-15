<?php

declare(strict_types=1);

namespace Mautic\PluginBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;

class PluginControllerTest extends MauticMysqlTestCase
{
    public function testConfigurePluginSuccessValidation(): void
    {
        $crawler    = $this->client->request(Request::METHOD_GET, '/s/plugins/config/Twilio');
        $form       = $crawler->filter('form')->form();

        $form->setValues([
            'integration_details' => [
                'isPublished' => 0,
                'apiKeys'     => [
                    'username' => 'valid_username',
                    'password' => 'valid_password',
                ],
            ],
        ]);

        $this->client->submit($form);
        Assert::assertTrue($this->client->getResponse()->isOk());
    }

    public function testConfigurePluginValidationError(): void
    {
        $crawler    = $this->client->request(Request::METHOD_GET, '/s/plugins/config/Twilio');
        $form       = $crawler->filter('form')->form();

        $form->setValues([
            'integration_details' => [
                'isPublished' => 1,
                'apiKeys'     => [
                    'username' => '',
                    'password' => 'bbb',
                ],
            ],
        ]);

        $crawler     = $this->client->submit($form);
        Assert::assertStringContainsString('A value is required.', $crawler->filter('#integration_details_apiKeys div')->html());
    }
}
