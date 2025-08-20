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

        $model->stageMerge($primary, $secondary);

        $this->em->clear();

        /** @var Lead $updatedLead */
        $updatedLead = $this->em->getRepository(Lead::class)->find($lead->getId());
        $this->assertSame($primary->getId(), $updatedLead->getStage()->getId());

        $stageId = $this->connection->fetchOne('SELECT stage_id FROM '.MAUTIC_TABLE_PREFIX.'stage_lead_action_log WHERE lead_id = ?', [$lead->getId()]);
        $this->assertEquals($primary->getId(), $stageId);

        $stageId = $this->connection->fetchOne('SELECT stage_id FROM '.MAUTIC_TABLE_PREFIX.'lead_stages_change_log WHERE id = ?', [$change->getId()]);
        $this->assertEquals($primary->getId(), $stageId);

        $this->assertNull($model->getEntity($secondary->getId()));
    }
}
