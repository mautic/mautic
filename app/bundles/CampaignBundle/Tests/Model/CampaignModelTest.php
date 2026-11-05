<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Model;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Tests\CampaignTestAbstract;
use Mautic\FormBundle\Entity\Form;
use Mautic\LeadBundle\Entity\LeadList;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
final class CampaignModelTest extends CampaignTestAbstract
{
    public function testGetSourceListsWithNull(): void
    {
        $model = $this->initCampaignModel();
        $lists = $model->getSourceLists();
        $this->assertArrayHasKey('lists', $lists);
        $this->assertSame([parent::$mockAlias => parent::$mockName], $lists['lists']);
        $this->assertArrayHasKey('forms', $lists);
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
        $campaign = new Campaign();
        $leadList = new LeadList();

        $this->entityManager->expects($this->once())
            ->method('find')
            ->with(LeadList::class, parent::$mockId)
            ->willReturn($leadList);

        $model->setLeadSources($campaign, ['lists' => [parent::$mockId => parent::$mockName]], []);

        $this->assertTrue($campaign->getLists()->contains($leadList));
    }

    public function testSetLeadSourcesIgnoresNonNumericLeadListIdentifier(): void
    {
        $model    = $this->initCampaignModel();
        $campaign = new Campaign();

        $this->entityManager->expects($this->never())
            ->method('find');

        $model->setLeadSources($campaign, ['lists' => ['list-one' => 0]], []);

        $this->assertCount(0, $campaign->getLists());
        $this->assertSame([], $campaign->getChanges());
    }

    public function testSetLeadSourcesAddsFormById(): void
    {
        $model    = $this->initCampaignModel();
        $campaign = new Campaign();
        $form     = new Form();

        $this->entityManager->expects($this->once())
            ->method('find')
            ->with(Form::class, parent::$mockId)
            ->willReturn($form);

        $model->setLeadSources($campaign, ['forms' => [parent::$mockId => parent::$mockName]], []);

        $this->assertTrue($campaign->getForms()->contains($form));
    }

    public function testSetLeadSourcesRemovesLeadListById(): void
    {
        $model    = $this->initCampaignModel();
        $campaign = new Campaign();
        $leadList = new LeadList();

        $this->entityManager->expects($this->once())
            ->method('find')
            ->with(LeadList::class, parent::$mockId)
            ->willReturn($leadList);

        $model->setLeadSources($campaign, [], ['lists' => [parent::$mockId => parent::$mockName]]);

        $this->assertArrayHasKey('removed', $campaign->getChanges()['lists']);
    }

    public function testSetLeadSourcesRemovesFormById(): void
    {
        $model    = $this->initCampaignModel();
        $campaign = new Campaign();
        $form     = new Form();

        $this->entityManager->expects($this->once())
            ->method('find')
            ->with(Form::class, parent::$mockId)
            ->willReturn($form);

        $model->setLeadSources($campaign, [], ['forms' => [parent::$mockId => parent::$mockName]]);

        $this->assertArrayHasKey('removed', $campaign->getChanges()['forms']);
    }

    public function testSetLeadSourcesIgnoresNonNumericFormIdentifier(): void
    {
        $model    = $this->initCampaignModel();
        $campaign = new Campaign();

        $this->entityManager->expects($this->never())
            ->method('find');

        $model->setLeadSources(
            $campaign,
            ['forms' => ['form-one' => 0]],
            ['forms' => ['form-two' => 0]]
        );

        $this->assertCount(0, $campaign->getForms());
        $this->assertSame([], $campaign->getChanges());
    }
}
