<?php

namespace Mautic\CampaignBundle\Tests\Model;

use Mautic\CampaignBundle\Tests\CampaignTestAbstract;

class CampaignModelTest extends CampaignTestAbstract
{
    public function testGetSourceListsWithNull(): void
    {
        $model = $this->initCampaignModel();
        $lists = $model->getSourceLists();
        $this->assertTrue(isset($lists['lists']));
        $this->assertSame([parent::$mockId => parent::$mockName], $lists['lists']);
        $this->assertTrue(isset($lists['forms']));
        $this->assertSame([parent::$mockId => parent::$mockName], $lists['forms']);
    }

    public function testGetSourceListsWithLists(): void
    {
        $model = $this->initCampaignModel();
        $lists = $model->getSourceLists('lists');
        $this->assertSame([parent::$mockId => parent::$mockName], $lists);
    }

    public function testGetSourceListsWithForms(): void
    {
        $model = $this->initCampaignModel();
        $lists = $model->getSourceLists('forms');
        $this->assertSame([parent::$mockId => parent::$mockName], $lists);
    }
}
