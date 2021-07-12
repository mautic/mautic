<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\Tests\Functional\Controller;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\Assert;

final class DetailControllerTest extends MauticMysqlTestCase
{
    public function testMakretplaceListTable(): void
    {
        $requests     = [];
        $history      = Middleware::history($requests);
        $response     = new Response(200, [], file_get_contents(__DIR__.'/../../ApiResponse/detail.json'));
        $handlerStack = HandlerStack::create(new MockHandler([$response]));
        $handlerStack->push($history);
        self::$container->set('mautic.http.client', new Client(['handler' => $handlerStack]));

        $this->client->request('GET', 's/marketplace/detail/koco/mautic-recaptcha-bundle');

        Assert::assertTrue($this->client->getResponse()->isOk(), $this->client->getResponse()->getContent());

        Assert::assertStringContainsString(
            'This plugin brings reCAPTCHA integration to mautic.',
            $this->client->getResponse()->getContent()
        );
    }
}
