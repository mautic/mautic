<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;

final class AbstractFormControllerTest extends MauticMysqlTestCase
{
    public function testUnlockActionWithValidReturnUrl(): void
    {
        $objectId    = 1;
        $objectModel = 'form.form';
        $returnUrl   = 'http://localhost/s/forms';

        $this->client->request(
            'GET',
            "/s/action/unlock/$objectModel/$objectId",
            [
                'returnUrl' => urlencode($returnUrl),
                'name'      => 'test',
            ]
        );

        $clientResponse = $this->client->getResponse();
        $payload        = $clientResponse->getContent();

        self::assertResponseIsSuccessful();
        $this->assertStringContainsString("Forms\n</h1>", $payload);
    }

    public function testUnlockActionWithInvalidReturnUrl(): void
    {
        $objectId         = 1;
        $objectModel      = 'form.form';
        $invalidReturnUrl = 'invalid-url';

        $this->client->request(
            'GET',
            "/s/action/unlock/$objectModel/$objectId",
            [
                'returnUrl' => $invalidReturnUrl,
                'name'      => 'test',
            ]
        );

        $response = $this->client->getResponse();
        $payload  = $response->getContent();

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Dashboard</h1>', $payload);
    }

    public function testUnlockActionWithDifferentHostReturnUrl(): void
    {
        $objectId    = 1;
        $objectModel = 'form.form';
        $returnUrl   = 'https://malicious.com/s/forms';

        $this->client->request(
            'GET',
            "/s/action/unlock/$objectModel/$objectId",
            [
                'returnUrl' => urlencode($returnUrl),
                'name'      => 'test',
            ]
        );

        $response = $this->client->getResponse();
        $payload  = $response->getContent();

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Dashboard</h1>', $payload);
    }
}
