<?php

namespace Mautic\CampaignBundle\Tests\Model;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Tests\CampaignTestAbstract;
use Mautic\FormBundle\Entity\Form;
use Mautic\LeadBundle\Entity\LeadList;

class CampaignModelTest extends CampaignTestAbstract
{
    public function testGetSourceListsWithNull(): void
    {
        $model = $this->initCampaignModel();
        $lists = $model->getSourceLists();
        $this->assertTrue(isset($lists['lists']));
        $this->assertSame([parent::$mockAlias => parent::$mockName], $lists['lists']);
        $this->assertTrue(isset($lists['forms']));
        $this->assertSame([parent::$mockId => parent::$mockName], $lists['forms']);
    }

    public function testGetSourceListsWithLists(): void
    {
        $model = $this->initCampaignModel();
        $lists = $model->getSourceLists('lists');
        $this->assertSame([parent::$mockAlias => parent::$mockName], $lists);
    }

    public function testGetSourceListsWithForms(): void
    {
        $model = $this->initCampaignModel();
        $lists = $model->getSourceLists('forms');
        $this->assertSame([parent::$mockId => parent::$mockName], $lists);
    }

    public function testGetSourceListsWithListsUsingIds(): void
    {
        $model = $this->initCampaignModel();
        $lists = $model->getSourceLists('lists', false, true);
        $this->assertSame([parent::$mockId => parent::$mockName], $lists);
    }

    public function testSetLeadSourcesAddsLeadListById(): void
    {
        $model    = $this->initCampaignModel();
        $campaign = $this->createMock(Campaign::class);
        $leadList = $this->createMock(LeadList::class);

        $this->entityManager->expects($this->once())
            ->method('find')
            ->with(LeadList::class, parent::$mockId)
            ->willReturn($leadList);

        $campaign->expects($this->once())
            ->method('addList')
            ->with($leadList);

        $model->setLeadSources($campaign, ['lists' => [parent::$mockId => parent::$mockName]], []);
    }

    public function testSetLeadSourcesIgnoresNonNumericLeadListIdentifier(): void
    {
        $model    = $this->initCampaignModel();
        $campaign = $this->createMock(Campaign::class);

        $this->entityManager->expects($this->never())
            ->method('find');

        $campaign->expects($this->never())
            ->method('addList');

        $model->setLeadSources($campaign, ['lists' => ['list-one' => 0]], []);
    }

    public function testSetLeadSourcesAddsFormById(): void
    {
        $model    = $this->initCampaignModel();
        $campaign = $this->createMock(Campaign::class);
        $form     = $this->createMock(Form::class);

        $this->entityManager->expects($this->once())
            ->method('find')
            ->with(Form::class, parent::$mockId)
            ->willReturn($form);

        $campaign->expects($this->once())
            ->method('addForm')
            ->with($form);

        $model->setLeadSources($campaign, ['forms' => [parent::$mockId => parent::$mockName]], []);
    }

    public function testSetLeadSourcesRemovesLeadListById(): void
    {
        $model    = $this->initCampaignModel();
        $campaign = $this->createMock(Campaign::class);
        $leadList = $this->createMock(LeadList::class);

        $this->entityManager->expects($this->once())
            ->method('find')
            ->with(LeadList::class, parent::$mockId)
            ->willReturn($leadList);

        $campaign->expects($this->once())
            ->method('removeList')
            ->with($leadList);

        $model->setLeadSources($campaign, [], ['lists' => [parent::$mockId => parent::$mockName]]);
    }

    public function testSetLeadSourcesRemovesFormById(): void
    {
        $model    = $this->initCampaignModel();
        $campaign = $this->createMock(Campaign::class);
        $form     = $this->createMock(Form::class);

        $this->entityManager->expects($this->once())
            ->method('find')
            ->with(Form::class, parent::$mockId)
            ->willReturn($form);

        $campaign->expects($this->once())
            ->method('removeForm')
            ->with($form);

        $model->setLeadSources($campaign, [], ['forms' => [parent::$mockId => parent::$mockName]]);
    }

    public function testSetLeadSourcesIgnoresNonNumericFormIdentifier(): void
    {
        $model    = $this->initCampaignModel();
        $campaign = $this->createMock(Campaign::class);

        $this->entityManager->expects($this->never())
            ->method('find');

        $campaign->expects($this->never())
            ->method('addForm');

        $campaign->expects($this->never())
            ->method('removeForm');

        $model->setLeadSources(
            $campaign,
            ['forms' => ['form-one' => 0]],
            ['forms' => ['form-two' => 0]]
        );
    }
}
