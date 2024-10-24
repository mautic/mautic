<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\Tests\Functional\Controller;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

final class ListControllerTest extends MauticMysqlTestCase
{
    public function testMarketplaceListTable(): void
    {
        /** @var MockHandler $handlerStack */
        $handlerStack = static::getContainer()->get(MockHandler::class);
        $handlerStack->append(
            new Response(SymfonyResponse::HTTP_OK, [], file_get_contents(__DIR__.'/../../ApiResponse/list.json'))  // Getting the package list from Packagist API.
        );

        $crawler = $this->client->request('GET', 's/marketplace');

        Assert::assertTrue($this->client->getResponse()->isOk(), $this->client->getResponse()->getContent());

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
}
