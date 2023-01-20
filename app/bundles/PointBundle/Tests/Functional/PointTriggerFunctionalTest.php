<?php

declare(strict_types=1);

namespace Mautic\PointBundle\Tests\Functional\EmailTriggerTest;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\Tag;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PointBundle\Entity\League;
use Mautic\PointBundle\Entity\LeagueContactScore;
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

    public function testLeaguePointsTriggerWithTagAction(): void
    {
        /** @var LeadModel $model */
        $model = self::$container->get('mautic.lead.model.lead');

        $leagueA = $this->createLeague('League A');
        $leagueB = $this->createLeague('League B');

        $triggerA = $this->createTrigger('League A Trigger (should trigger)', 5, $leagueA);
        $this->createAddTagEvent('tagA', $triggerA);

        $triggerB = $this->createTrigger('League B Trigger (should not trigger)', 5, $leagueB);
        $this->createAddTagEvent('tagB', $triggerB);

        $lead = new Lead();
        $data = ['email' => 'pointtest@example.com', 'points' => 0];
        $model->setFieldValues($lead, $data, false, true, true);
        $model->saveEntity($lead);

        $this->em->clear(Lead::class);
        $lead = $model->getEntity($lead->getId());

        $this->addLeagueContactScore($lead, $leagueA, 5);
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

        $triggerA      = $this->createTrigger('League A Trigger (should trigger)', 5, null, true);
        $triggerEventA = $this->createAddTagEvent('tagA', $triggerA);
        $triggerA->addTriggerEvent(0, $triggerEventA);
        $triggerModel->saveEntity($triggerA);

        $triggerB      = $this->createTrigger('League B Trigger (should not trigger)', 6, null, true);
        $triggerEventB = $this->createAddTagEvent('tagB', $triggerB);
        $triggerB->addTriggerEvent(0, $triggerEventB);
        $triggerModel->saveEntity($triggerB);

        $lead = $leadModel->getEntity($lead->getId());

        $this->assertFalse($this->leadHasTag($lead, 'tagB'));
        $this->assertTrue($this->leadHasTag($lead, 'tagA'));
    }

    public function testTriggerWithLeagueForExistingContacts(): void
    {
        /** @var LeadModel $leadModel */
        $leadModel = self::$container->get('mautic.lead.model.lead');

        /** @var TriggerModel $triggerModel */
        $triggerModel = self::$container->get('mautic.point.model.trigger');

        $leagueA = $this->createLeague('League A');
        $leagueB = $this->createLeague('League B');

        $lead = new Lead();
        $data = ['email' => 'pointtest@example.com', 'points' => 5];
        $leadModel->setFieldValues($lead, $data, false, true, true);
        $this->addLeagueContactScore($lead, $leagueA, 5);
        $leadModel->saveEntity($lead);

        $this->em->clear(Lead::class);

        $triggerA      = $this->createTrigger('League A Trigger (should trigger)', 5, $leagueA, true);
        $triggerEventA = $this->createAddTagEvent('tagA', $triggerA);
        $triggerA->addTriggerEvent(0, $triggerEventA);
        $triggerModel->saveEntity($triggerA);

        $triggerB      = $this->createTrigger('League B Trigger (should not trigger)', 5, $leagueB, true);
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
        League $league = null,
        bool $triggerExistingLeads = false
    ): Trigger {
        $trigger = new Trigger();
        $trigger->setName($name);
        $trigger->setPoints($points);

        if (isset($league)) {
            $trigger->setLeague($league);
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

    private function createLeague(
        string $name
    ): League {
        $league = new League();
        $league->setName($name);
        $this->em->persist($league);

        return $league;
    }

    private function addLeagueContactScore(
        Lead $lead,
        League $league,
        int $score
    ): void {
        $leagueContactScore = new LeagueContactScore();
        $leagueContactScore->setContact($lead);
        $leagueContactScore->setLeague($league);
        $leagueContactScore->setScore($score);
        $lead->addLeagueScore($leagueContactScore);
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
