<?php

namespace Mautic\StageBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\StageBundle\Cache\StageCountCache;
use Mautic\StageBundle\Entity\Stage;
use Mautic\StageBundle\Model\StageModel;

class StageController extends MauticMysqlTestCase
{
    private StageCountCache $stageCountCache;

    private StageModel $stageModel;

    private LeadModel $leadModel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->stageCountCache = self::getContainer()->get(StageCountCache::class);
        $this->stageModel      = self::getContainer()->get(StageModel::class);
        $this->leadModel       = self::getContainer()->get(LeadModel::class);
    }

    public function testGetStageContactCount(): void
    {
        $contact = new Lead();
        $contact->setEmail('john@doe.com');
        $this->leadModel->saveEntity($contact);

        $stage = new Stage();
        $stage->setName('Stage1');
        $stage->setWeight(1);
        $this->stageModel->saveEntity($stage);

        $this->assertEquals(0, $this->stageCountCache->getStageContactCount($stage->getId()));

        $this->leadModel->addToStages($contact, $stage);
        $this->leadModel->saveEntity($contact);

        $this->assertEquals(1, $this->stageCountCache->getStageContactCount($stage->getId()));

        // create stage 2
        $stage2 = new Stage();
        $stage2->setName('Stage2');
        $stage2->setWeight(2);
        $this->stageModel->saveEntity($stage2);

        $this->assertEquals(0, $this->stageCountCache->getStageContactCount($stage2->getId()));

        $this->leadModel->addToStages($contact, $stage2);
        $this->leadModel->saveEntity($contact);

        $this->assertEquals(1, $this->stageCountCache->getStageContactCount($stage2->getId()));
        $this->assertEquals(0, $this->stageCountCache->getStageContactCount($stage->getId()));
    }
}
