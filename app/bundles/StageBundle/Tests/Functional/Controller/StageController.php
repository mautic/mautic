<?php

declare(strict_types=1);

namespace Mautic\StageBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\StageBundle\Entity\Stage;
use Mautic\StageBundle\Model\StageModel;
use Symfony\Component\HttpFoundation\Request;

class StageController extends MauticMysqlTestCase
{
    private StageModel $stageModel;

    private LeadModel $leadModel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->stageModel = self::getContainer()->get(StageModel::class);
        $this->leadModel  = self::getContainer()->get(LeadModel::class);
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

        $this->assertSame(0, $this->getStageContactCountViaAjax($stage->getId()));

        $this->leadModel->addToStages($contact, $stage);
        $this->leadModel->saveEntity($contact);

        $this->assertSame(1, $this->getStageContactCountViaAjax($stage->getId()));

        // create stage 2
        $stage2 = new Stage();
        $stage2->setName('Stage2');
        $stage2->setWeight(2);
        $this->stageModel->saveEntity($stage2);

        $this->assertSame(0, $this->getStageContactCountViaAjax($stage2->getId()));

        $this->leadModel->addToStages($contact, $stage2);
        $this->leadModel->saveEntity($contact);

        $this->assertSame(1, $this->getStageContactCountViaAjax($stage2->getId()));
        $this->assertSame(0, $this->getStageContactCountViaAjax($stage->getId()));
    }

    private function getStageContactCountViaAjax(int $stageId): int
    {
        $this->setCsrfHeader();
        $this->client->xmlHttpRequest(
            Request::METHOD_POST,
            '/s/ajax',
            ['action' => 'stage:getLeadCount', 'id' => $stageId]
        );

        $response = $this->client->getResponse();
        $this->assertTrue($response->isSuccessful());

        $responseData = json_decode($response->getContent(), true);
        $this->assertIsArray($responseData);
        $this->assertArrayHasKey('leadCount', $responseData);

        return $responseData['leadCount'];
    }
}
