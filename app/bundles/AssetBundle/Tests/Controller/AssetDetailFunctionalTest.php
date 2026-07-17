<?php

declare(strict_types=1);

namespace Mautic\AssetBundle\Tests\Controller;

use Mautic\AssetBundle\Entity\Asset;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;

final class AssetDetailFunctionalTest extends MauticMysqlTestCase
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
        $this->em->detach($asset);

        $crawler   = $this->client->request('GET', sprintf('/s/assets/view/%d', $asset->getId()));
        $imageTag  = $crawler->filter('.img-thumbnail');

        $onError  = $imageTag->attr('onerror');
        $altProp  = $imageTag->attr('alt');

        $this->assertNull($onError);
        $this->assertSame($title, $altProp);
    }

    public function testAssetUrlActions(): void
    {
        $asset = new Asset();
        $asset->setTitle('Asset URL actions');
        $asset->setAlias('asset-url-actions');
        $asset->setStorageLocation('local');
        $asset->setPath('asset-url-actions.jpg');
        $asset->setExtension('jpg');
        $this->em->persist($asset);
        $this->em->flush();

        $crawler     = $this->client->request('GET', sprintf('/s/assets/view/%d', $asset->getId()));
        $urlPanel     = $crawler->filter('.col-md-3.bdr-l > .panel')->first();
        $urlInput    = $crawler->filter('.input-group input[readonly]');
        $copyButton  = $crawler->filter('button[data-copy]');
        $previewLink = $crawler->filter(sprintf('a.btn-link[data-target="#asset-dialog-preview-modal-%d"]', $asset->getId()));

        $this->assertSame('Asset URL', $urlPanel->filter('.panel-title')->text());
        $this->assertCount(1, $urlInput);
        $this->assertCount(1, $copyButton);
        $this->assertSame($urlInput->attr('value'), $copyButton->attr('data-copy'));
        $this->assertCount(1, $copyButton->filter('.ri-file-copy-line'));
        $this->assertCount(1, $previewLink);
        $this->assertTrue($previewLink->matches('.mt-sm'));
        $this->assertCount(1, $previewLink->filter('.ri-rectangle-line'));
    }
}
