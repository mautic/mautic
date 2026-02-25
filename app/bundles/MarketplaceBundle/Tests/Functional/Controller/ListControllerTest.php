<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\Tests\Functional\Controller;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use Mautic\CoreBundle\Test\Guzzle\ClientMockTrait;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

final class ListControllerTest extends MauticMysqlTestCase
{
    use ClientMockTrait;

    public function testMarketplaceListTable(): void
    {
        /** @var MockHandler $handlerStack */
        $handlerStack = $this->getClientMockHandler();
        $handlerStack->append(
            new Response(SymfonyResponse::HTTP_OK, [], file_get_contents(__DIR__.'/../../ApiResponse/list.json'))  // Getting the package list from Packagist API.
        );

        $crawler = $this->client->request('GET', 's/marketplace');

        $this->assertResponseIsSuccessful();

        Assert::assertSame(
            [
                'Mautic Saelos Bundle',
                'Mautic Recaptcha Bundle',
                'Mautic Ldap Auth Bundle',
                'Mautic Referrals Bundle',
                'Mautic Do Not Contact Extras Bundle',
            ],
            array_map(
                fn (string $dirtyPackageName) => trim($dirtyPackageName),
                $crawler->filter('#marketplace-packages-table .package-name a')->extract(['_text'])
            )
        );
    }

    public function testMarketplaceListWithSearchQuery(): void
    {
        /** @var MockHandler $handlerStack */
        $handlerStack = $this->getClientMockHandler();
        $handlerStack->append(
            new Response(SymfonyResponse::HTTP_OK, [], file_get_contents(__DIR__.'/../../ApiResponse/list.json'))
        );

        $crawler = $this->client->request('GET', 's/marketplace?search=recaptcha');

        $this->assertResponseIsSuccessful();
    }

    public function testMarketplaceListHandlesEmptyResults(): void
    {
        /** @var MockHandler $handlerStack */
        $handlerStack = $this->getClientMockHandler();
        $handlerStack->append(
            new Response(SymfonyResponse::HTTP_OK, [], file_get_contents(__DIR__.'/../../ApiResponse/list_empty.json'))
        );

        $crawler = $this->client->request('GET', 's/marketplace');

        $this->assertResponseIsSuccessful();

        // Verify no packages are displayed when API returns no results
        Assert::assertSame(
            [],
            $crawler->filter('#marketplace-packages-table .package-name a')->extract(['_text'])
        );
    }
}
