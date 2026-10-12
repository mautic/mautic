<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\Tests\Functional\Controller;

use GuzzleHttp\Psr7\Response;
use Mautic\CoreBundle\Test\Guzzle\ClientMockTrait;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Message\RequestInterface;
use Symfony\Component\DomCrawler\Crawler;
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

        $crawler = $this->client->request('GET', '/s/marketplace');

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

        $this->client->request('GET', '/s/marketplace?search=recaptcha');

        $this->assertResponseIsSuccessful();
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function packageTypes(): iterable
    {
        yield 'plugins' => ['mautic-plugin', 'plugin', 'Plugin'];
        yield 'themes' => ['mautic-theme', 'theme', 'Theme'];
        yield 'resources' => ['mautic-resource', 'resource', 'Campaign'];
    }

    #[DataProvider('packageTypes')]
    public function testTypeFilterCanBeAppliedPaginatedAndReset(string $type, string $fixture, string $label): void
    {
        $payload = json_decode(file_get_contents(__DIR__.'/../../ApiResponse/list_'.$fixture.'.json'), true, 512, JSON_THROW_ON_ERROR);
        foreach ($payload['results'] as $index => $package) {
            $this->queuePackageResponse(
                ['page' => (string) ($index + 1), 'limit' => '1', 'query' => 'newsletter', 'type' => $type],
                json_encode(['results' => [$package], 'total' => $payload['total']], JSON_THROW_ON_ERROR)
            );
        }
        $this->queuePackageResponse(
            ['page' => '1', 'limit' => '10', 'query' => 'newsletter'],
            file_get_contents(__DIR__.'/../../ApiResponse/list_all_types.json')
        );

        $search  = 'newsletter type:'.$type;
        $crawler = $this->client->request('GET', '/s/marketplace', ['search' => $search, 'limit' => 1]);
        $this->assertResponseIsSuccessful();
        $this->assertPackageRows($crawler, ['Newsletter '.$label.' One'], [$label]);
        $this->assertStringContainsString('2 items, 2 pages in total', $crawler->text(null, true));
        $popover = new Crawler($crawler->filter('button[data-toggle="popover"]')->attr('data-content'));
        $this->assertSame('type:'.$type, $popover->filter('#marketplace-package-type option[selected]')->attr('value'));
        $this->assertCount(1, $popover->filter('[onclick="Mautic.applyFilters()"]'));
        $this->assertCount(1, $popover->filter('[onclick="Mautic.resetFilters()"]'));

        $pageLink = $crawler->filter('a[href*="/marketplace/2?"]')->first()->attr('href');
        parse_str(parse_url($pageLink, PHP_URL_QUERY), $pageQuery);
        $this->assertSame($search, $pageQuery['search']);
        $crawler = $this->client->request('GET', $pageLink);
        $this->assertResponseIsSuccessful();
        $this->assertPackageRows($crawler, ['Newsletter '.$label.' Two'], [$label]);

        $crawler = $this->client->request('GET', '/s/marketplace', ['search' => 'newsletter', 'limit' => 10]);
        $this->assertResponseIsSuccessful();
        $this->assertPackageRows($crawler, [
            'Newsletter Plugin One', 'Newsletter Plugin Two',
            'Newsletter Theme One', 'Newsletter Theme Two',
            'Newsletter Campaign One', 'Newsletter Campaign Two',
        ], ['Plugin', 'Plugin', 'Theme', 'Theme', 'Campaign', 'Campaign']);
        $this->assertStringContainsString('6 items, 1 page in total', $crawler->text(null, true));
        $popover = new Crawler($crawler->filter('button[data-toggle="popover"]')->attr('data-content'));
        $this->assertSame('', $popover->filter('#marketplace-package-type option[selected]')->attr('value'));
        $this->assertCount(0, $this->getClientMockHandler());
    }

    public function testChangingTypeViaAjaxReplacesTheDisplayedPackages(): void
    {
        foreach (['plugin' => 'Plugin', 'theme' => 'Theme', 'resource' => 'Campaign'] as $type => $label) {
            $this->queuePackageResponse(
                ['page' => '1', 'limit' => '10', 'type' => 'mautic-'.$type],
                file_get_contents(__DIR__.'/../../ApiResponse/list_'.$type.'.json')
            );
            $this->client->xmlHttpRequest('GET', '/s/marketplace', ['search' => 'type:mautic-'.$type, 'limit' => 10]);
            $this->assertResponseIsSuccessful();
            $response = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
            $crawler  = new Crawler($response['newContent']);
            $this->assertPackageRows($crawler, ['Newsletter '.$label.' One', 'Newsletter '.$label.' Two'], [$label, $label]);
            $popover = new Crawler($crawler->filter('button[data-toggle="popover"]')->attr('data-content'));
            $this->assertSame('type:mautic-'.$type, $popover->filter('#marketplace-package-type option[selected]')->attr('value'));
        }
        $this->assertCount(0, $this->getClientMockHandler());
    }

    public function testTypeFilterWithNoMatchesCanBeReset(): void
    {
        $this->queuePackageResponse(
            ['page' => '1', 'limit' => '10', 'query' => 'nonexistent', 'type' => 'mautic-theme'],
            file_get_contents(__DIR__.'/../../ApiResponse/list_empty.json')
        );
        $this->queuePackageResponse(
            ['page' => '1', 'limit' => '10'],
            file_get_contents(__DIR__.'/../../ApiResponse/list_all_types.json')
        );
        $crawler = $this->client->request('GET', '/s/marketplace', ['search' => 'nonexistent type:mautic-theme', 'limit' => 10]);
        $this->assertResponseIsSuccessful();
        $this->assertPackageRows($crawler, [], []);
        $this->assertSame('nonexistent type:mautic-theme', $crawler->filter('#list-search')->attr('value'));

        $crawler = $this->client->request('GET', '/s/marketplace', ['search' => '']);
        $this->assertResponseIsSuccessful();
        $this->assertCount(6, $crawler->filter('#marketplace-packages-table .package-name a'));
        $this->assertSame('', $crawler->filter('#list-search')->attr('value'));
        $this->assertCount(0, $this->getClientMockHandler());
    }

    /**
     * @param array<string, string> $expectedQuery
     */
    private function queuePackageResponse(array $expectedQuery, string $body): void
    {
        $this->getClientMockHandler()->append(static function (RequestInterface $request) use ($expectedQuery, $body): Response {
            self::assertSame('GET', $request->getMethod());
            self::assertSame('/api/registry/v1/packages', $request->getUri()->getPath());
            parse_str($request->getUri()->getQuery(), $query);
            self::assertSame($expectedQuery, $query);

            return new Response(200, ['Content-Type' => 'application/json'], $body);
        });
    }

    /**
     * @param list<string> $names
     * @param list<string> $types
     */
    private function assertPackageRows(Crawler $crawler, array $names, array $types): void
    {
        $this->assertSame($names, array_map(trim(...), $crawler->filter('#marketplace-packages-table .package-name a')->extract(['_text'])));
        $this->assertSame($types, array_map(trim(...), $crawler->filter('#marketplace-packages-table .package-type')->extract(['_text'])));
    }

    public function testFilterSurvivesReturningFromDetailAndCanBeCleared(): void
    {
        $handler = $this->getClientMockHandler();
        $list = file_get_contents(__DIR__.'/../../ApiResponse/list_resource.json');
        $allTypes = file_get_contents(__DIR__.'/../../ApiResponse/list_all_types.json');
        $handler->append(
            new Response(200, [], $list),
            new Response(200, [], file_get_contents(__DIR__.'/../../ApiResponse/detail.json')),
            new Response(200, [], $list),
            new Response(200, [], $allTypes),
            new Response(200, [], $allTypes)
        );

        $search = 'newsletter type:mautic-resource';
        $this->client->request('GET', '/s/marketplace', ['search' => $search]);
        self::assertResponseIsSuccessful();
        $crawler = $this->client->request('GET', '/s/marketplace/detail/koco/mautic-recaptcha-bundle');
        self::assertResponseIsSuccessful();
        $backLink = $crawler->filter('a.btn-back')->attr('href');
        $crawler = $this->client->request('GET', $backLink);
        self::assertResponseIsSuccessful();
        $this->assertSame($search, $crawler->filter('#list-search')->attr('value'));
        $popover = new Crawler($crawler->filter('button[data-toggle="popover"]')->attr('data-content'));
        $this->assertSame('type:mautic-resource', $popover->filter('#marketplace-package-type option[selected]')->attr('value'));
        parse_str($handler->getLastRequest()->getUri()->getQuery(), $query);
        $this->assertSame('mautic-resource', $query['type']);
        $this->assertSame('newsletter', $query['query']);
        $this->assertPackageRows($crawler, ['Newsletter Campaign One', 'Newsletter Campaign Two'], ['Campaign', 'Campaign']);

        $this->client->request('GET', '/s/marketplace', ['search' => '']);
        self::assertResponseIsSuccessful();
        $crawler = $this->client->request('GET', '/s/marketplace');
        self::assertResponseIsSuccessful();
        $this->assertSame('', $crawler->filter('#list-search')->attr('value'));
        parse_str($handler->getLastRequest()->getUri()->getQuery(), $query);
        $this->assertArrayNotHasKey('type', $query);
        $this->assertArrayNotHasKey('query', $query);
        $this->assertCount(6, $crawler->filter('#marketplace-packages-table .package-name a'));
        $this->assertCount(0, $handler);
    }

    public function testMarketplaceListHandlesEmptyResults(): void
    {
        $handlerStack = $this->getClientMockHandler();
        $handlerStack->append(
            new Response(SymfonyResponse::HTTP_OK, [], file_get_contents(__DIR__.'/../../ApiResponse/list_empty.json'))
        );

        $crawler = $this->client->request('GET', '/s/marketplace');

        self::assertResponseIsSuccessful();

        // Verify no packages are displayed when API returns no results
        $this->assertSame([], $crawler->filter('#marketplace-packages-table .package-name a')->extract(['_text']));
    }
}
