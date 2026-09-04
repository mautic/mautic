<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\Tests\Functional\Controller;

use GuzzleHttp\Psr7\Response;
use Mautic\CoreBundle\Test\Guzzle\ClientMockTrait;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

final class ListControllerTest extends MauticMysqlTestCase
{
    use ClientMockTrait;

    public function testMarketplaceListTable(): void
    {
        $handlerStack = $this->getClientMockHandler();
        $handlerStack->append(
            new Response(SymfonyResponse::HTTP_OK, [], file_get_contents(__DIR__.'/../../ApiResponse/list.json'))  // Getting the package list from Packagist API.
        );

        $crawler = $this->client->request(Request::METHOD_GET, 's/marketplace');

        self::assertResponseIsSuccessful($this->client->getResponse()->getContent());

        $this->assertSame([
            'Mautic Saelos Bundle',
            'Mautic Recaptcha Bundle',
            'Mautic Ldap Auth Bundle',
            'Mautic Referrals Bundle',
            'Mautic Do Not Contact Extras Bundle',
        ], array_map(
            trim(...),
            $crawler->filter('#marketplace-packages-table .package-name a')->extract(['_text'])
        ));
    }

    public function testMarketplaceListWithSearchQuery(): void
    {
        $handlerStack = $this->getClientMockHandler();
        $handlerStack->append(
            new Response(SymfonyResponse::HTTP_OK, [], file_get_contents(__DIR__.'/../../ApiResponse/list.json'))
        );

        $this->client->request('GET', 's/marketplace?search=recaptcha');

        $this->assertResponseIsSuccessful();
    }

    public function testMarketplaceListHandlesEmptyResults(): void
    {
        $handlerStack = $this->getClientMockHandler();
        $handlerStack->append(
            new Response(SymfonyResponse::HTTP_OK, [], file_get_contents(__DIR__.'/../../ApiResponse/list_empty.json'))
        );

        $crawler = $this->client->request(Request::METHOD_GET, 's/marketplace');

        self::assertResponseIsSuccessful();

        // Verify no packages are displayed when API returns no results
        $this->assertSame([], $crawler->filter('#marketplace-packages-table .package-name a')->extract(['_text']));
    }
}
