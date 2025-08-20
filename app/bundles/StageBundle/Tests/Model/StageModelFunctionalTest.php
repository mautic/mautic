<?php

namespace Mautic\StageBundle\Tests\Model;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\StagesChangeLog;
use Mautic\StageBundle\Entity\LeadStageLog;
use Mautic\StageBundle\Entity\Stage;
use Mautic\StageBundle\Model\StageModel;

class StageModelFunctionalTest extends MauticMysqlTestCase
{
    public function testStageMergeMovesData(): void
    {
        /** @var StageModel $model */
        $model = static::getContainer()->get('mautic.stage.model.stage');

        $primary = new Stage();
        $primary->setName('Primary');

        $secondary = new Stage();
        $secondary->setName('Secondary');

        $lead = new Lead();
        $lead->setEmail('test@example.com');
        $lead->setStage($secondary);

        $log = new LeadStageLog();
        $log->setStage($secondary);
        $log->setLead($lead);
        $log->setDateFired(new \DateTime());

        $change = new StagesChangeLog();
        $change->setLead($lead)
            ->setStage($secondary)
            ->setEventName('event')
            ->setActionName('action')
            ->setDateAdded(new \DateTime());

        $this->em->persist($primary);
        $this->em->persist($secondary);
        $this->em->persist($lead);
        $this->em->persist($log);
        $this->em->persist($change);
        $this->em->flush();

        $secondaryId = $secondary->getId();
        $model->stageMerge($primary, $secondary);

        $this->em->clear();

        /** @var Lead $updatedLead */
        $updatedLead = $this->em->getRepository(Lead::class)->find($lead->getId());
        $this->assertSame($primary->getId(), $updatedLead->getStage()->getId());

        $stageId = $this->connection->fetchOne('SELECT stage_id FROM '.MAUTIC_TABLE_PREFIX.'stage_lead_action_log WHERE lead_id = ?', [$lead->getId()]);
        $this->assertEquals($primary->getId(), $stageId);

        $stageId = $this->connection->fetchOne('SELECT stage_id FROM '.MAUTIC_TABLE_PREFIX.'lead_stages_change_log WHERE id = ?', [$change->getId()]);
        $this->assertEquals($primary->getId(), $stageId);

        $this->assertNull($model->getEntity($secondaryId));
    }

    public function testStageMergeWithMultipleLeads(): void
    {
        /** @var StageModel $model */
        $model = static::getContainer()->get('mautic.stage.model.stage');

        $primary = new Stage();
        $primary->setName('Primary');

        $secondary = new Stage();
        $secondary->setName('Secondary');

        $lead1 = new Lead();
        $lead1->setEmail('test1@example.com');
        $lead1->setStage($secondary);

        $lead2 = new Lead();
        $lead2->setEmail('test2@example.com');
        $lead2->setStage($secondary);

        $lead3 = new Lead();
        $lead3->setEmail('test3@example.com');
        $lead3->setStage($primary);

        $this->em->persist($primary);
        $this->em->persist($secondary);
        $this->em->persist($lead1);
        $this->em->persist($lead2);
        $this->em->persist($lead3);
        $this->em->flush();

        $secondaryId = $secondary->getId();
        $model->stageMerge($primary, $secondary);

        $this->em->clear();

        $updatedLead1 = $this->em->getRepository(Lead::class)->find($lead1->getId());
        $updatedLead2 = $this->em->getRepository(Lead::class)->find($lead2->getId());
        $updatedLead3 = $this->em->getRepository(Lead::class)->find($lead3->getId());

        $this->assertSame($primary->getId(), $updatedLead1->getStage()->getId());
        $this->assertSame($primary->getId(), $updatedLead2->getStage()->getId());
        $this->assertSame($primary->getId(), $updatedLead3->getStage()->getId());

        $this->assertNull($model->getEntity($secondaryId));
    }

    public function testStageMergeWithNoLeads(): void
    {
        /** @var StageModel $model */
        $model = static::getContainer()->get('mautic.stage.model.stage');

        $primary = new Stage();
        $primary->setName('Primary');

        $secondary = new Stage();
        $secondary->setName('Secondary');

        $this->em->persist($primary);
        $this->em->persist($secondary);
        $this->em->flush();

        $secondaryId = $secondary->getId();
        $model->stageMerge($primary, $secondary);

        $this->em->clear();

        $this->assertNull($model->getEntity($secondaryId));
        $this->assertNotNull($model->getEntity($primary->getId()));
    }

    public function testStageMergeWithSameStage(): void
    {
        /** @var StageModel $model */
        $model = static::getContainer()->get('mautic.stage.model.stage');

        $stage = new Stage();
        $stage->setName('Test Stage');

        $this->em->persist($stage);
        $this->em->flush();

        $originalId = $stage->getId();
        $result = $model->stageMerge($stage, $stage);

        $this->em->clear();

        $this->assertSame($stage, $result);
        $this->assertNotNull($model->getEntity($originalId));
    }

    public function testStageMergeUpdatesAllRelatedData(): void
    {
        /** @var StageModel $model */
        $model = static::getContainer()->get('mautic.stage.model.stage');

        $primary = new Stage();
        $primary->setName('Primary');

        $secondary = new Stage();
        $secondary->setName('Secondary');

        $lead1 = new Lead();
        $lead1->setEmail('test1@example.com');
        $lead1->setStage($secondary);

        $lead2 = new Lead();
        $lead2->setEmail('test2@example.com');
        $lead2->setStage($secondary);

        // Create one log entry for each lead to avoid composite primary key violation
        $log1 = new LeadStageLog();
        $log1->setStage($secondary);
        $log1->setLead($lead1);
        $log1->setDateFired(new \DateTime());

        $log2 = new LeadStageLog();
        $log2->setStage($secondary);
        $log2->setLead($lead2);
        $log2->setDateFired(new \DateTime());

        $change1 = new StagesChangeLog();
        $change1->setLead($lead1)
            ->setStage($secondary)
            ->setEventName('event1')
            ->setActionName('action1')
            ->setDateAdded(new \DateTime());

        $change2 = new StagesChangeLog();
        $change2->setLead($lead2)
            ->setStage($secondary)
            ->setEventName('event2')
            ->setActionName('action2')
            ->setDateAdded(new \DateTime());

        $this->em->persist($primary);
        $this->em->persist($secondary);
        $this->em->persist($lead1);
        $this->em->persist($lead2);
        $this->em->persist($log1);
        $this->em->persist($log2);
        $this->em->persist($change1);
        $this->em->persist($change2);
        $this->em->flush();

        $model->stageMerge($primary, $secondary);

        $this->em->clear();

        $changeStageId1 = $this->connection->fetchOne('SELECT stage_id FROM '.MAUTIC_TABLE_PREFIX.'lead_stages_change_log WHERE id = ?', [$change1->getId()]);
        $changeStageId2 = $this->connection->fetchOne('SELECT stage_id FROM '.MAUTIC_TABLE_PREFIX.'lead_stages_change_log WHERE id = ?', [$change2->getId()]);

        $this->assertEquals($primary->getId(), $changeStageId1);
        $this->assertEquals($primary->getId(), $changeStageId2);

        // Verify that both leads were updated to the primary stage
        $leadStageId1 = $this->connection->fetchOne('SELECT stage_id FROM '.MAUTIC_TABLE_PREFIX.'stage_lead_action_log WHERE lead_id = ?', [$lead1->getId()]);
        $leadStageId2 = $this->connection->fetchOne('SELECT stage_id FROM '.MAUTIC_TABLE_PREFIX.'stage_lead_action_log WHERE lead_id = ?', [$lead2->getId()]);

        $this->assertEquals($primary->getId(), $leadStageId1);
        $this->assertEquals($primary->getId(), $leadStageId2);
    }

    public function testStageMergeDeletesSecondaryStage(): void
    {
        /** @var StageModel $model */
        $model = static::getContainer()->get('mautic.stage.model.stage');

        $primary = new Stage();
        $primary->setName('Primary');

        $secondary = new Stage();
        $secondary->setName('Secondary');

        $this->em->persist($primary);
        $this->em->persist($secondary);
        $this->em->flush();

        $secondaryId = $secondary->getId();
        $model->stageMerge($primary, $secondary);

        $this->em->clear();

        $this->assertNull($model->getEntity($secondaryId));
        $this->assertNotNull($model->getEntity($primary->getId()));
    }

    public function testStageMergePreservesPrimaryStageData(): void
    {
        /** @var StageModel $model */
        $model = static::getContainer()->get('mautic.stage.model.stage');

        $primary = new Stage();
        $primary->setName('Primary Stage');
        $primary->setDescription('Primary description');

        $secondary = new Stage();
        $secondary->setName('Secondary Stage');
        $secondary->setDescription('Secondary description');

        $this->em->persist($primary);
        $this->em->persist($secondary);
        $this->em->flush();

        $primaryId = $primary->getId();
        $primaryName = $primary->getName();
        $primaryDescription = $primary->getDescription();

        $model->stageMerge($primary, $secondary);

        $this->em->clear();

        $preservedStage = $model->getEntity($primaryId);
        $this->assertNotNull($preservedStage);
        $this->assertEquals($primaryName, $preservedStage->getName());
        $this->assertEquals($primaryDescription, $preservedStage->getDescription());
    }
}
