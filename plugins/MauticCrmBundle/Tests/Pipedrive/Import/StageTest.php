<?php

namespace MauticPlugin\MauticCrmBundle\Tests\Pipedrive\Import;

use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\MauticCrmBundle\Entity\PipedrivePipeline;
use MauticPlugin\MauticCrmBundle\Entity\PipedriveStage;
use MauticPlugin\MauticCrmBundle\Tests\Pipedrive\PipedriveTest;

class StageTest extends PipedriveTest
{
    private $features = [
        'objects' => [
            'company',
            'deal'
        ],
        'leadFields' => [
            'first_name' => 'firstname',
            'last_name'  => 'lastname',
            'email'      => 'email',
            'phone'      => 'phone',
        ],
    ];

    public function testCreateStage()
    {
        $this->installPipedriveIntegration(true, $this->features);
        $pipeline = $this->createPipeline(4, 'New pipeline', true);

        $data = $this->getData('stage.added');

        $this->makeRequest('POST', $data);

        $response     = $this->client->getResponse();
        $responseData = json_decode($response->getContent(), true);
        $stage         = $this->em->getRepository(PipedriveStage::class)->find(1);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertEquals($responseData['status'], 'ok');
        $this->assertEquals($stage->getName(), 'Stage 1');
        $this->assertEquals($stage->getPipeline()->getName(), 'New pipeline');
        $this->assertEquals($stage->isActive(), true);
        $this->assertEquals($stage->getOrder(), 1);
        $this->assertEquals(count($this->em->getRepository(PipedriveStage::class)->findAll()), 1);
    }

    public function testCreateSameStageMultipleTimes()
    {
        $this->installPipedriveIntegration(true, $this->features);
        $pipeline = $this->createPipeline(4, 'New Pipeline', true);

        $data = $this->getData('stage.added');
        $this->makeRequest('POST', $data);
        $this->makeRequest('POST', $data);
        $this->makeRequest('POST', $data);

        $this->assertEquals(count($this->em->getRepository(PipedriveStage::class)->findAll()), 1);
    }

    public function testCreateStageWithoutPipeline()
    {
        $this->installPipedriveIntegration(true, $this->features);

        $data = $this->getData('stage.added');
        $this->makeRequest('POST', $data);

        $this->assertEquals(count($this->em->getRepository(PipedriveStage::class)->findAll()), 0);
    }

    public function testUpdateStage()
    {
        $this->installPipedriveIntegration(true, $this->features);
        $pipeline = $this->createPipeline(4, 'New Pipeline', true);
        $stage    = $this->createStage(16, 'Stage 1', 1, $pipeline);
        $data = json_decode($this->getData('stage.updated'), true);

        $this->makeRequest('POST', json_encode($data));

        $response     = $this->client->getResponse();
        $responseData = json_decode($response->getContent(), true);
        $pipeline     = $this->em->getRepository(PipedriveStage::class)->find(1);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertEquals($responseData['status'], 'ok');
        $this->assertEquals($stage->getName(), 'STAGE 1');
    }

    public function testDeleteStage()
    {
        $this->installPipedriveIntegration(true, $this->features);
        $pipeline = $this->createPipeline(4, 'New Pipeline', true);
        $stage    = $this->createStage(16, 'Stage 1', 1, $pipeline);

        $json = $this->getData('pipeline.deleted');
        $data = json_decode($json, true);

        $this->assertEquals(count($this->em->getRepository(PipedriveStage::class)->findAll()), 1);

        $this->makeRequest('POST', json_encode($data));

        $response     = $this->client->getResponse();
        $responseData = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertEquals($responseData['status'], 'ok');
        $this->assertEquals(count($this->em->getRepository(PipedriveStage::class)->findAll()), 0);
    }
}
