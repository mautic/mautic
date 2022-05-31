<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\Tests\Functional\Controller;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use Mautic\CoreBundle\Test\AbstractMauticTestCase;
use Mautic\MarketplaceBundle\DTO\Allowlist as DTOAllowlist;
use Mautic\MarketplaceBundle\Service\Allowlist;
use PHPUnit\Framework\Assert;

final class ListControllerTest extends AbstractMauticTestCase
{
    public function testMarketplaceListTableWithNoAllowList(): void
    {
        /** @var MockHandler $handlerStack */
        $handlerStack = self::$container->get('mautic.http.client.mock_handler');
        $handlerStack->append(
            new Response(200, [], file_get_contents(__DIR__.'/../../ApiResponse/list.json'))
        );
        $allowlist = $this->createMock(Allowlist::class);
        $allowlist->method('getAllowList')->willReturn(null);
        self::$container->set('marketplace.service.allowlist', $allowlist);

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

    public function testMarketplaceListTableWithAllowList(): void
    {
        $mockResults  = json_decode(file_get_contents(__DIR__.'/../../ApiResponse/list.json'), true)['results'];

        /** @var MockHandler $handlerStack */
        $handlerStack = self::$container->get('mautic.http.client.mock_handler');
        $handlerStack->append(
            new Response(200, [], json_encode(['results' => [$mockResults[1]]])), // mautic-recaptcha-bundle
            new Response(200, [], json_encode(['results' => [$mockResults[3]]])), // mautic-referrals-bundle
        );
        $allowlist = $this->createMock(Allowlist::class);
        $allowlist->method('getAllowList')->willReturn(
            DTOAllowlist::fromArray(json_decode(file_get_contents(__DIR__.'/../../ApiResponse/allowlist.json'), true))
        );
        self::$container->set('marketplace.service.allowlist', $allowlist);

        $crawler = $this->client->request('GET', 's/marketplace');

        Assert::assertTrue($this->client->getResponse()->isOk(), $this->client->getResponse()->getContent());

        Assert::assertSame(
            [
                'KocoCaptcha',
                'Mautic Referrals Bundle',
            ],
            array_map(
                fn (string $dirtyPackageName) => trim($dirtyPackageName),
                $crawler->filter('#marketplace-packages-table .package-name a')->extract(['_text'])
            )
        );
    }
}
