<?php

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Tests\Functional\Campaign;

use Mautic\CampaignBundle\Tests\DataFixtures\ORM\CampaignData;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;

class DetailsTest extends MauticMysqlTestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->loadFixtures([CampaignData::class], true);
    }

    public function testDetailsPageLoadCorrectly()
    {
        $this->client->request('GET', 's/campaigns/view/1');

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertContains('Campaign A', $this->client->getResponse()->getContent());
    }
}
