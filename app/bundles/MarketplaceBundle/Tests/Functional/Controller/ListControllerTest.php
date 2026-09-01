<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\Tests\Functional\Controller;

use GuzzleHttp\Psr7\Response;
use Mautic\CoreBundle\Test\Guzzle\ClientMockTrait;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
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

        $crawler = $this->client->request('GET', 's/marketplace');

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

        $crawler = $this->client->request('GET', 's/marketplace');

        self::assertResponseIsSuccessful();

        // Verify no packages are displayed when API returns no results
        $this->assertSame([], $crawler->filter('#marketplace-packages-table .package-name a')->extract(['_text']));
    }

    public function testMarketplaceListFilterBySearchName(): void
    {
        $mockResults = json_decode(file_get_contents(__DIR__.'/../../ApiResponse/list.json'), true)['results'];

        /** @var MockHandler $handlerStack */
        $handlerStack = $this->getClientMockHandler();
        $handlerStack->append(
            new Response(SymfonyResponse::HTTP_OK, [], file_get_contents(__DIR__.'/../../ApiResponse/allowlist.json')),
            new Response(SymfonyResponse::HTTP_OK, [], json_encode(['results' => [$mockResults[1]]])), // koco matches
        );

        /** @var Allowlist $allowlist */
        $allowlist = self::getContainer()->get('marketplace.service.allowlist');
        $allowlist->clearCache();

        $crawler = $this->client->request('GET', 's/marketplace?search=koco');

        $this->assertResponseIsSuccessful();

        Assert::assertSame(
            ['KocoCaptcha'],
            array_map(
                trim(...),
                $crawler->filter('#marketplace-packages-table .package-name a')->extract(['_text'])
            )
        );
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('typeFilterProvider')]
    public function testMarketplaceListFilterByType(string $searchCommand, string $expectedPackage): void
    {
        $mockResults = json_decode(file_get_contents(__DIR__.'/../../ApiResponse/list.json'), true)['results'];

        /** @var MockHandler $handlerStack */
        $handlerStack = $this->getClientMockHandler();
        $handlerStack->append(
            new Response(SymfonyResponse::HTTP_OK, [], file_get_contents(__DIR__.'/../../ApiResponse/allowlist_with_types.json')),
            new Response(SymfonyResponse::HTTP_OK, [], json_encode(['results' => [$mockResults[1]]])),
        );

        /** @var Allowlist $allowlist */
        $allowlist = self::getContainer()->get('marketplace.service.allowlist');
        $allowlist->clearCache();

        $crawler = $this->client->request('GET', 's/marketplace?search='.$searchCommand);

        $this->assertResponseIsSuccessful();

        Assert::assertSame(
            [$expectedPackage],
            array_map(
                trim(...),
                $crawler->filter('#marketplace-packages-table .package-name a')->extract(['_text'])
            )
        );
    }

    /**
     * @return \Iterator<string, array{string, string}>
     */
    public static function typeFilterProvider(): \Iterator
    {
        yield 'plugin' => ['is:plugin', 'KocoCaptcha'];
        yield 'theme' => ['is:theme', 'Mautic Referrals Bundle'];
        yield 'campaign' => ['is:campaign', 'Welcome Campaign'];
    }
}
