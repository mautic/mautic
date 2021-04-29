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

final class ListControllerTest extends MauticMysqlTestCase
{
    public function testMakretplaceListTable(): void
    {
        $requests     = [];
        $history      = Middleware::history($requests);
        $response     = new Response(200, [], file_get_contents(__DIR__.'/../../ApiResponse/list.json'));
        $handlerStack = HandlerStack::create(new MockHandler([$response]));
        $handlerStack->push($history);
        self::$container->set('mautic.http.client', new Client(['handler' => $handlerStack]));

        $crawler = $this->client->request('GET', 's/marketplace');

        Assert::assertTrue($this->client->getResponse()->isOk(), $this->client->getResponse()->getContent());

        Assert::assertSame(
            [
                'Mautic saelos bundle',
                'Mautic recaptcha bundle',
                'Mautic ldap auth bundle',
                'Mautic referrals bundle',
                'Mautic do not contact extras bundle',
            ],
            array_map(
                fn (string $dirtyPackageName) => trim($dirtyPackageName),
                $crawler->filter('#marketplace-packages-table .package-name a')->extract(['_text'])
            )
        );
    }
}
