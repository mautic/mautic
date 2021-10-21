<?php

namespace Mautic\AssetBundle\Tests\Controller;

use Mautic\AssetBundle\Entity\Asset;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\DataFixtures\ORM\LoadLeadData;
use Mautic\LeadBundle\Entity\Lead;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;
use Mautic\LeadBundle\DataFixtures\ORM\LoadLeadData;

class AssetDetailFunctionalTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    protected function setUp(): void
    {
        parent::setUp();

        defined('MAUTIC_TABLE_PREFIX') or define('MAUTIC_TABLE_PREFIX', '');
    }

    public function testLeadViewPreventsXSS(): void
    {
        $title      = 'aaa" onerror=alert(1) a="';
        $asset      = new Asset();
        $asset->setTitle($title);
        $asset->setAlias('dummy-alias');
        $asset->setStorageLocation('local');
        $asset->setPath('broken-image.jpg');
        $asset->setExtension('jpg');
        $this->em->persist($asset);
        $this->em->flush();
        $this->em->clear();

        $crawler   = $this->client->request('GET', sprintf('/s/assets/view/%d', $asset->getId()));
        $imageTag  = $crawler->filter('.tab-content.preview-detail img');

        $onError  = $imageTag->attr('onerror');
        $altProp  = $imageTag->attr('alt');

        Assert::assertNull($onError);
        Assert::assertSame($title, $altProp);
    }

    /**
     * Test contact list exists for asset.
     *
     * @dataProvider assetLeadCountProvider
     */
    public function testContactListExists(int $leadCount): void
    {
        $this->loadFixtures([LoadLeadData::class]);

        $container  = $this->getContainer();
        $assetModel = $container->get('mautic.asset.model.asset');
        $leadModel  = $container->get('mautic.lead.model.lead');

        // Get per-page pagination limit from pageHelper to limit expected
        // visible number of contacts to actual number displayed at a time.
        $pageHelperFactory = $container->get('mautic.page.helper.factory');
        $pageHelper        = $pageHelperFactory->make('mautic.asset', 1);
        $pageLimit         = $pageHelper->getLimit();

        $asset = (new Asset())->setTitle(uniqid());

        $assetModel->saveEntity($asset);

        $leads = $leadCount
            ? $leadModel->getLeadsByIds(range(1, $leadCount))
            : [];

        foreach ($leads as $lead) {
            $request  = new Request([
                'ct' => $assetModel->encodeArrayForUrl([
                    'lead' => $lead->getId(),
                ]),
            ]);

            $assetModel->trackDownload($asset, $request);
        }

        $crawler = $this->client->request('GET', sprintf('/s/assets/view/%d', $asset->getId()));
        $cards   = $crawler->filter('#leads-container .contact-cards');

        $expected = min($leadCount, $pageLimit);

        Assert::assertSame($expected, $cards->count());
    }

    /**
     * Asset lead count provider.
     *
     * @return array
     */
    public function assetLeadCountProvider(): iterable
    {
        yield 'no leads'  => [0];
        yield '5 leads'   => [5];
        yield 'two pages' => [40];
    }
}
