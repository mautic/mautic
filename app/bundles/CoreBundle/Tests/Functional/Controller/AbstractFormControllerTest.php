<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Response;

class AbstractFormControllerFunctionalTest extends MauticMysqlTestCase
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

        Assert::assertSame(Response::HTTP_OK, $clientResponse->getStatusCode());
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

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertStringContainsString('Dashboard</h1>', $payload);
    }

    public function testUnlockActionWithDifferentHostReturnUrl(): void
    {
        $objectId    = 1;
        $objectModel = 'form.form';
        $returnUrl   = 'http://malicious.com/s/forms';

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

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertStringContainsString('Dashboard</h1>', $payload);
    }
}
