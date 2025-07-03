<?php

declare(strict_types=1);

namespace Mautic\PointBundle\Tests\Functional;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\Tag;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PointBundle\Entity\Group;
use Mautic\PointBundle\Model\PointGroupModel;
use Mautic\PointBundle\Model\TriggerModel;

class PointTriggerFunctionalTest extends MauticMysqlTestCase
{
    use TriggerTrait;

    public function testPointsTriggerWithTagAction(): void
    {
        /** @var LeadModel $model */
        $model = self::getContainer()->get('mautic.lead.model.lead');

        $trigger = $this->createTrigger('Trigger', 5);
        $this->createAddTagEvent('tag5', $trigger);
        $trigger = $this->createTrigger('Trigger', 6);
        $this->createAddTagEvent('tag6', $trigger);

        $lead = new Lead();
        $data = ['email' => 'pointtest@example.com', 'points' => 5];
        $model->setFieldValues($lead, $data, false, true, true);
        $model->saveEntity($lead);

        $this->em->clear(Lead::class);
        $lead = $model->getEntity($lead->getId());
        $this->assertFalse($lead->getTags()->isEmpty());
        $this->assertTrue($this->leadHasTag($lead, 'tag5'));
        $this->assertFalse($this->leadHasTag($lead, 'tag6'));
    }

    public function testGroupPointsTriggerWithTagAction(): void
    {
        /** @var LeadModel $model */
        $model = self::getContainer()->get('mautic.lead.model.lead');

        /** @var PointGroupModel $pointGroupModel */
        $pointGroupModel = self::getContainer()->get('mautic.point.model.group');

        $groupA = $this->createGroup('Group A');
        $groupB = $this->createGroup('Group B');

        $triggerA = $this->createTrigger('Group A Trigger (should trigger)', 5, $groupA);
        $this->createAddTagEvent('tagA', $triggerA);

        $triggerB = $this->createTrigger('Group B Trigger (should not trigger)', 5, $groupB);
        $this->createAddTagEvent('tagB', $triggerB);

        $lead = new Lead();
        $data = ['email' => 'pointtest@example.com', 'points' => 0];
        $model->setFieldValues($lead, $data, false, true, true);
        $model->saveEntity($lead);

        $this->em->clear(Lead::class);
        $lead = $model->getEntity($lead->getId());
        $pointGroupModel->adjustPoints($lead, $groupA, 5);
        $lead = $model->getEntity($lead->getId());

        $this->assertFalse($this->leadHasTag($lead, 'tagB'));
        $this->assertTrue($this->leadHasTag($lead, 'tagA'));
    }

    public function testTriggerForExistingContacts(): void
    {
        /** @var LeadModel $leadModel */
        $leadModel = self::getContainer()->get('mautic.lead.model.lead');

        /** @var TriggerModel $triggerModel */
        $triggerModel = self::getContainer()->get('mautic.point.model.trigger');

        $lead = new Lead();
        $data = ['email' => 'pointtest@example.com', 'points' => 5];
        $leadModel->setFieldValues($lead, $data, false, true, true);
        $leadModel->saveEntity($lead);

        $this->em->clear(Lead::class);

        $triggerA      = $this->createTrigger('Group A Trigger (should trigger)', 5, null, true);
        $triggerEventA = $this->createAddTagEvent('tagA', $triggerA);
        $triggerA->addTriggerEvent(0, $triggerEventA);
        $triggerModel->saveEntity($triggerA);

        $triggerB      = $this->createTrigger('Group B Trigger (should not trigger)', 6, null, true);
        $triggerEventB = $this->createAddTagEvent('tagB', $triggerB);
        $triggerB->addTriggerEvent(0, $triggerEventB);
        $triggerModel->saveEntity($triggerB);

        $lead = $leadModel->getEntity($lead->getId());

        $this->assertFalse($this->leadHasTag($lead, 'tagB'));
        $this->assertTrue($this->leadHasTag($lead, 'tagA'));
    }

    public function testTriggerWithGroupForExistingContacts(): void
    {
        /** @var LeadModel $leadModel */
        $leadModel = self::getContainer()->get('mautic.lead.model.lead');

        /** @var TriggerModel $triggerModel */
        $triggerModel = self::getContainer()->get('mautic.point.model.trigger');

        /** @var PointGroupModel $pointGroupModel */
        $pointGroupModel = self::getContainer()->get('mautic.point.model.group');

        $groupA = $this->createGroup('Group A');
        $groupB = $this->createGroup('Group B');

        $lead = new Lead();
        $data = ['email' => 'pointtest@example.com'];
        $leadModel->setFieldValues($lead, $data, false, true, true);
        $leadModel->saveEntity($lead);
        $pointGroupModel->adjustPoints($lead, $groupA, 5);

        $triggerA      = $this->createTrigger('Group A Trigger (should trigger)', 5, $groupA, true);
        $triggerEventA = $this->createAddTagEvent('tagA', $triggerA);
        $triggerA->addTriggerEvent(0, $triggerEventA);
        $triggerModel->saveEntity($triggerA);

        $triggerB      = $this->createTrigger('Group B Trigger (should not trigger)', 5, $groupB, true);
        $triggerEventB = $this->createAddTagEvent('tagB', $triggerB);
        $triggerB->addTriggerEvent(0, $triggerEventB);
        $triggerModel->saveEntity($triggerB);
        $lead = $leadModel->getEntity($lead->getId());

        $triggerC      = $this->createTrigger('General Trigger (should not trigger)', 5, $groupB, true);
        $triggerEventB = $this->createAddTagEvent('tagC', $triggerC);
        $triggerC->addTriggerEvent(0, $triggerEventB);
        $triggerModel->saveEntity($triggerC);
        $lead = $leadModel->getEntity($lead->getId());

        $this->assertFalse($this->leadHasTag($lead, 'tagC'));
        $this->assertFalse($this->leadHasTag($lead, 'tagB'));
        $this->assertTrue($this->leadHasTag($lead, 'tagA'));
    }

    private function createGroup(
        string $name,
    ): Group {
        $group = new Group();
        $group->setName($name);
        $this->em->persist($group);

        return $group;
    }

    private function leadHasTag(
        Lead $lead,
        string $tagName,
    ): bool {
        /** @var Tag $tag */
        foreach ($lead->getTags() as $tag) {
            if ($tag->getTag() === $tagName) {
                return true;
            }
        }

        return false;
    }
}
