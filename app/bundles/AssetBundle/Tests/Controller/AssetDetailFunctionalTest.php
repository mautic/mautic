<?php

namespace Mautic\AssetBundle\Tests\Controller;

use Mautic\AssetBundle\Entity\Asset;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\Assert;

class AssetDetailFunctionalTest extends MauticMysqlTestCase
{
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
}
