<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\Tests\Functional\Controller;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\MarketplaceBundle\Service\Allowlist;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

final class DetailControllerTest extends MauticMysqlTestCase
{
    /**
     * @dataProvider dataProvider
     */
    public function testMarketplaceDetailPage(string $requestedPackage, int $responseCode, string $foundPackageName, string $foundPackageDesc, string $latestVersion = ''): void
    {
        /** @var MockHandler $handlerStack */
        $handlerStack = self::$container->get('mautic.http.client.mock_handler');
        $handlerStack->append(
            new Response(SymfonyResponse::HTTP_OK, [], file_get_contents(__DIR__.'/../../ApiResponse/allowlist.json')), // Getting Allow list from Github API.
            new Response(200, [], file_get_contents(__DIR__.'/../../ApiResponse/detail.json')) // Getting package detail from Packagist API.
        );

        /** @var Allowlist $allowlist */
        $allowlist = self::$container->get('marketplace.service.allowlist');
        $allowlist->clearCache();

        $this->client->request('GET', "s/marketplace/detail/{$requestedPackage}");

        $responseContent = $this->client->getResponse()->getContent();

        Assert::assertSame($responseCode, $this->client->getResponse()->getStatusCode(), $this->client->getResponse()->getContent());
        Assert::assertStringContainsString($foundPackageDesc, $responseContent);
        Assert::assertStringContainsString($foundPackageName, $responseContent);
        Assert::assertStringContainsString($latestVersion, $responseContent);
    }

    /**
     * @return iterable<array<string|int>>
     */
    public function dataProvider(): iterable
    {
        // Package that do not exist in the allowlist.
        yield [
            'mautic/unicorn',
            SymfonyResponse::HTTP_NOT_FOUND,
            'mautic/unicorn',
            'Package \'mautic/unicorn\' not found in allowlist.',
        ];

        // Package that exists in the allowlist with display name.
        yield [
            'koco/mautic-recaptcha-bundle',
            SymfonyResponse::HTTP_OK,
            'KocoCaptcha',
            'This plugin brings reCAPTCHA integration to mautic.',
            '<a href="https://github.com/KonstantinCodes/mautic-recaptcha/releases/tag/3.0.1" id="latest-version" target="_blank" rel="noopener noreferrer">',
        ];
    }
}
