<?php

declare(strict_types=1);

namespace Mautic\PointBundle\Tests\Functional\EmailTriggerTest;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\Tag;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PointBundle\Entity\Group;
use Mautic\PointBundle\Entity\GroupContactScore;
use Mautic\PointBundle\Entity\Trigger;
use Mautic\PointBundle\Entity\TriggerEvent;
use Mautic\PointBundle\Model\TriggerModel;

class PointTriggerFunctionalTest extends MauticMysqlTestCase
{
    public function testPointsTriggerWithTagAction(): void
    {
        /** @var LeadModel $model */
        $model = self::$container->get('mautic.lead.model.lead');

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
        $model = self::$container->get('mautic.lead.model.lead');

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

        $this->addGroupContactScore($lead, $groupA, 5);
        $model->setFieldValues($lead, ['points' => 5], false, true, true);
        $model->saveEntity($lead);

        $lead = $model->getEntity($lead->getId());

        $this->assertFalse($this->leadHasTag($lead, 'tagB'));
        $this->assertTrue($this->leadHasTag($lead, 'tagA'));
    }

    public function testTriggerForExistingContacts(): void
    {
        /** @var LeadModel $leadModel */
        $leadModel = self::$container->get('mautic.lead.model.lead');

        /** @var TriggerModel $triggerModel */
        $triggerModel = self::$container->get('mautic.point.model.trigger');

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
        $leadModel = self::$container->get('mautic.lead.model.lead');

        /** @var TriggerModel $triggerModel */
        $triggerModel = self::$container->get('mautic.point.model.trigger');

        $groupA = $this->createGroup('Group A');
        $groupB = $this->createGroup('Group B');

        $lead = new Lead();
        $data = ['email' => 'pointtest@example.com', 'points' => 5];
        $leadModel->setFieldValues($lead, $data, false, true, true);
        $this->addGroupContactScore($lead, $groupA, 5);
        $leadModel->saveEntity($lead);

        $triggerA      = $this->createTrigger('Group A Trigger (should trigger)', 5, $groupA, true);
        $triggerEventA = $this->createAddTagEvent('tagA', $triggerA);
        $triggerA->addTriggerEvent(0, $triggerEventA);
        $triggerModel->saveEntity($triggerA);

        $triggerB      = $this->createTrigger('Group B Trigger (should not trigger)', 5, $groupB, true);
        $triggerEventB = $this->createAddTagEvent('tagB', $triggerB);
        $triggerB->addTriggerEvent(0, $triggerEventB);
        $triggerModel->saveEntity($triggerB);
        $lead = $leadModel->getEntity($lead->getId());

        $this->assertFalse($this->leadHasTag($lead, 'tagB'));
        $this->assertTrue($this->leadHasTag($lead, 'tagA'));
    }

    private function createTrigger(
        string $name,
        int $points = 0,
        Group $group = null,
        bool $triggerExistingLeads = false
    ): Trigger {
        $trigger = new Trigger();
        $trigger->setName($name);
        $trigger->setPoints($points);

        if (isset($group)) {
            $trigger->setGroup($group);
        }
        if ($triggerExistingLeads) {
            $trigger->setTriggerExistingLeads($triggerExistingLeads);
        }
        $this->em->persist($trigger);

        return $trigger;
    }

    private function createAddTagEvent(
        string $tag,
        Trigger $trigger
    ): TriggerEvent {
        $triggerEvent = new TriggerEvent();
        $triggerEvent->setTrigger($trigger);
        $triggerEvent->setName('Add '.$tag);
        $triggerEvent->setType('lead.changetags');
        $triggerEvent->setProperties([
            'add_tags'    => [$tag],
            'remove_tags' => [],
        ]);
        $this->em->persist($triggerEvent);

        return $triggerEvent;
    }

    private function createGroup(
        string $name
    ): Group {
        $group = new Group();
        $group->setName($name);
        $this->em->persist($group);

        return $group;
    }

    private function addGroupContactScore(
        Lead $lead,
        Group $group,
        int $score
    ): void {
        $groupContactScore = new GroupContactScore();
        $groupContactScore->setContact($lead);
        $groupContactScore->setGroup($group);
        $groupContactScore->setScore($score);
        $lead->addGroupScore($groupContactScore);
    }

    private function leadHasTag(
        Lead $lead,
        string $tagName
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
