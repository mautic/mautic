<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\Tests\Functional\Controller;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use Mautic\CoreBundle\Test\Guzzle\ClientMockTrait;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

final class DetailControllerTest extends MauticMysqlTestCase
{
    use ClientMockTrait;

    #[\PHPUnit\Framework\Attributes\DataProvider('dataProvider')]
    public function testMarketplaceDetailPage(string $requestedPackage, int $responseCode, string $foundPackageName, string $foundPackageDesc, string $latestVersion = ''): void
    {
        /** @var MockHandler $handlerStack */
        $handlerStack = $this->getClientMockHandler();
        $handlerStack->append(
            new Response($responseCode, [], file_get_contents(__DIR__.'/../../ApiResponse/detail.json')) // Getting package detail from Packagist API.
        );

        $this->client->request('GET', "s/marketplace/detail/{$requestedPackage}");

        $responseContent = $this->client->getResponse()->getContent();

        Assert::assertSame($responseCode, $this->client->getResponse()->getStatusCode(), $this->client->getResponse()->getContent());

        if ($responseCode >= 300) {
            return;
        }

        Assert::assertStringContainsString($foundPackageDesc, $responseContent);
        Assert::assertStringContainsString($foundPackageName, $responseContent);
        Assert::assertStringContainsString($latestVersion, $responseContent);
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
        /** @var MockHandler $handlerStack */
        $handlerStack = $this->getClientMockHandler();
        $handlerStack->append(
            new Response(SymfonyResponse::HTTP_OK, [], file_get_contents(__DIR__.'/../../ApiResponse/detail.json'))
        );

        $crawler = $this->client->request('GET', 's/marketplace/detail/koco/mautic-recaptcha-bundle');

        $this->assertResponseIsSuccessful();

        $responseContent = $this->client->getResponse()->getContent();

        // Verify reviews from object format (keyed by username) are displayed correctly
        Assert::assertStringContainsString('john_doe', $responseContent);
        Assert::assertStringContainsString('Excellent reCAPTCHA integration!', $responseContent);
        Assert::assertStringContainsString('jane_smith', $responseContent);
        Assert::assertStringContainsString('Works great with Mautic forms', $responseContent);

        // Verify star ratings are rendered (john_doe has 5 stars, jane_smith has 4)
        $starRows = $crawler->filter('.ri-star-fill');
        Assert::assertGreaterThanOrEqual(9, $starRows->count()); // 5 + 4 filled stars
    }

    public function testMarketplaceDetailPageHandlesNoReviews(): void
    {
        /** @var MockHandler $handlerStack */
        $handlerStack = $this->getClientMockHandler();
        $handlerStack->append(
            new Response(SymfonyResponse::HTTP_OK, [], file_get_contents(__DIR__.'/../../ApiResponse/detail_no_reviews.json'))
        );

        $crawler = $this->client->request('GET', 's/marketplace/detail/koco/mautic-recaptcha-bundle');

        $this->assertResponseIsSuccessful();

        $responseContent = $this->client->getResponse()->getContent();

        // Verify the page renders successfully with no reviews
        Assert::assertStringContainsString('Mautic Recaptcha Bundle', $responseContent);

        // Verify no review blocks are rendered
        Assert::assertCount(0, $crawler->filter('blockquote'));
        Assert::assertCount(0, $crawler->filter('.ri-star-fill'));
    }
}
