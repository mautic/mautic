<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\Tests\Functional\Controller;

use GuzzleHttp\Psr7\Response;
use Mautic\CoreBundle\Test\Guzzle\ClientMockTrait;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

final class DetailControllerTest extends MauticMysqlTestCase
{
    use ClientMockTrait;

    #[DataProvider('dataProvider')]
    public function testMarketplaceDetailPage(string $requestedPackage, int $responseCode, string $foundPackageName, string $foundPackageDesc, string $latestVersion = ''): void
    {
        $handlerStack = $this->getClientMockHandler();
        $handlerStack->append(
            new Response($responseCode, [], file_get_contents(__DIR__.'/../../ApiResponse/detail.json')) // Getting package detail from Packagist API.
        );

        $this->client->request(Request::METHOD_GET, "s/marketplace/detail/{$requestedPackage}");

        $responseContent = $this->client->getResponse()->getContent();

        self::assertResponseStatusCodeSame($responseCode);

        if ($responseCode >= 300) {
            return;
        }

        $this->assertStringContainsString($foundPackageDesc, (string) $responseContent);
        $this->assertStringContainsString($foundPackageName, (string) $responseContent);
        $this->assertStringContainsString($latestVersion, (string) $responseContent);
    }

    /**
     * A 404 from the registry API must render Mautic's normal "not found" page rather than
     * leaking the upstream registry URL/response body through an uncaught ApiException.
     */
    public function testMarketplaceDetailPageDoesNotLeakRegistryDetailsOnNotFound(): void
    {
        $handlerStack = $this->getClientMockHandler();
        $handlerStack->append(
            new Response(SymfonyResponse::HTTP_NOT_FOUND, [], (string) json_encode(['message' => 'Package not found']))
        );

        $this->client->request('GET', 's/marketplace/detail/mautic/unicorn');

        self::assertResponseStatusCodeSame(SymfonyResponse::HTTP_NOT_FOUND);

        $responseContent = (string) $this->client->getResponse()->getContent();
        $this->assertStringNotContainsString('marketplace.mautic.org', $responseContent);
        $this->assertStringNotContainsString('ApiException', $responseContent);
    }

    /**
     * @return iterable<array<string|int>>
     */
    public static function dataProvider(): iterable
    {
        yield [
            'mautic/unicorn',
            SymfonyResponse::HTTP_NOT_FOUND,
            'mautic/unicorn',
            'Package &#039;mautic/unicorn&#039; not found.',
        ];

        yield [
            'koco/mautic-recaptcha-bundle',
            SymfonyResponse::HTTP_OK,
            'Mautic Recaptcha Bundle', // Display name comes from the Packagist API response (detail.json).
            'This plugin brings reCAPTCHA integration to mautic.',
            '<a href="https://github.com/KonstantinCodes/mautic-recaptcha/releases/tag/3.0.1" id="latest-version" target="_blank" rel="noopener noreferrer">',
        ];
    }

    public function testMarketplaceDetailPageDisplaysReviewsFromObjectFormat(): void
    {
        $handlerStack = $this->getClientMockHandler();
        $handlerStack->append(
            new Response(SymfonyResponse::HTTP_OK, [], file_get_contents(__DIR__.'/../../ApiResponse/detail.json'))
        );

        $crawler = $this->client->request('GET', 's/marketplace/detail/koco/mautic-recaptcha-bundle');

        $this->assertResponseIsSuccessful();

        $responseContent = $this->client->getResponse()->getContent();

        // Verify reviews from object format (keyed by username) are displayed correctly
        $this->assertStringContainsString('john_doe', (string) $responseContent);
        $this->assertStringContainsString('Excellent reCAPTCHA integration!', (string) $responseContent);
        $this->assertStringContainsString('jane_smith', (string) $responseContent);
        $this->assertStringContainsString('Works great with Mautic forms', (string) $responseContent);

        // Verify star ratings are rendered (john_doe has 5 stars, jane_smith has 4)
        $starRows = $crawler->filter('.ri-star-fill');
        $this->assertGreaterThanOrEqual(9, $starRows->count()); // 5 + 4 filled stars
    }

    public function testMarketplaceDetailPageHandlesNoReviews(): void
    {
        $handlerStack = $this->getClientMockHandler();
        $handlerStack->append(
            new Response(SymfonyResponse::HTTP_OK, [], file_get_contents(__DIR__.'/../../ApiResponse/detail_no_reviews.json'))
        );

        $crawler = $this->client->request('GET', 's/marketplace/detail/koco/mautic-recaptcha-bundle');

        $this->assertResponseIsSuccessful();

        $responseContent = $this->client->getResponse()->getContent();

        // Verify the page renders successfully with no reviews
        $this->assertStringContainsString('Mautic Recaptcha Bundle', (string) $responseContent);

        // Verify no review blocks are rendered
        $this->assertCount(0, $crawler->filter('blockquote'));
        $this->assertCount(0, $crawler->filter('.ri-star-fill'));
    }
}
