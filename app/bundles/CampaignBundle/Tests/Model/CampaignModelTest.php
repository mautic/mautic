<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Tests\Model;

use Mautic\CampaignBundle\Tests\CampaignTestAbstract;

class CampaignModelTest extends CampaignTestAbstract
{
    public function testGetSourceListsWithNull()
    {
        $model = $this->initCampaignModel();
        $lists = $model->getSourceLists();
        $this->assertTrue(isset($lists['lists']));
        $this->assertSame([parent::$mockId => parent::$mockName], $lists['lists']);
        $this->assertTrue(isset($lists['forms']));
        $this->assertSame([parent::$mockId => parent::$mockName], $lists['forms']);
    }

    public function testGetSourceListsWithLists()
    {
        $model = $this->initCampaignModel();
        $lists = $model->getSourceLists('lists');
        $this->assertSame([parent::$mockId => parent::$mockName], $lists);
    }

    public function testGetSourceListsWithForms()
    {
        $model = $this->initCampaignModel();
        $lists = $model->getSourceLists('forms');
        $this->assertSame([parent::$mockId => parent::$mockName], $lists);
    }
}
