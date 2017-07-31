<?php

namespace MauticPlugin\MauticCrmBundle\Tests\Pipedrive\Import;

use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\MauticCrmBundle\Entity\PipedrivePipeline;
use MauticPlugin\MauticCrmBundle\Tests\Pipedrive\PipedriveTest;

class PipelineTest extends PipedriveTest
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

    public function testCreatePipeline()
    {
        $this->installPipedriveIntegration(true, $this->features);
        $data = $this->getData('pipeline.added');

        $this->makeRequest('POST', $data);

        $response     = $this->client->getResponse();
        $responseData = json_decode($response->getContent(), true);
        $pipeline         = $this->em->getRepository(PipedrivePipeline::class)->find(1);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertEquals($responseData['status'], 'ok');
        $this->assertEquals($pipeline->getName(), 'New pipeline');
        $this->assertEquals($pipeline->getPipelineId(), 4);
        $this->assertEquals($pipeline->isActive(), true);
        $this->assertEquals(count($this->em->getRepository(PipedrivePipeline::class)->findAll()), 1);
    }

    public function testCreateSamePipelineMultipleTimes()
    {
        $this->installPipedriveIntegration(true, $this->features);

        $data = $this->getData('pipeline.added');
        $this->makeRequest('POST', $data);
        $this->makeRequest('POST', $data);
        $this->makeRequest('POST', $data);

        $this->assertEquals(count($this->em->getRepository(PipedrivePipeline::class)->findAll()), 1);
    }

    public function testUpdatePipeline()
    {
        $this->installPipedriveIntegration(true, $this->features);
        $data = json_decode($this->getData('pipeline.updated'), true);

        $pipeline = $this->createPipeline(4, 'New Pipeline', true);

        $this->makeRequest('POST', json_encode($data));

        $response     = $this->client->getResponse();
        $responseData = json_decode($response->getContent(), true);
        $pipeline     = $this->em->getRepository(PipedrivePipeline::class)->find(1);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertEquals($responseData['status'], 'ok');
        $this->assertEquals($pipeline->getName(), 'New pipeline name');
        $this->assertEquals($pipeline->isActive(), true);
    }

    public function testDeletePipeline()
    {
        $this->installPipedriveIntegration(true, $this->features);
        $pipeline = $this->createPipeline(4, 'New Pipeline', true);
        $json = $this->getData('pipeline.deleted');
        $data = json_decode($json, true);

        $this->assertEquals(count($this->em->getRepository(PipedrivePipeline::class)->findAll()), 1);

        $this->makeRequest('POST', json_encode($data));

        $response     = $this->client->getResponse();
        $responseData = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertEquals($responseData['status'], 'ok');
        $this->assertEquals(count($this->em->getRepository(PipedrivePipeline::class)->findAll()), 0);
    }
}
