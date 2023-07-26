<?php

/*
 * @copyright   2021 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 *
 */

namespace Mautic\AssetBundle\Tests\Controller;

use Mautic\AssetBundle\Entity\Asset;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\Assert;

class AssetDetailFunctionalTest extends MauticMysqlTestCase
{
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
}
