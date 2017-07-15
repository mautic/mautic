<?php

namespace MauticPlugin\MauticCrmBundle\Tests\Pipedrive\Import;

use MauticPlugin\MauticCrmBundle\Entity\PipedriveOwner;
use MauticPlugin\MauticCrmBundle\Tests\Pipedrive\PipedriveTest;

class OwnerTest extends PipedriveTest
{
    private $features = [
        'leadFields' => [
            'first_name' => 'firstname',
            'last_name'  => 'lastname',
            'email'      => 'email',
            'phone'      => 'phone',
        ],
    ];

    public function testAddPipedriveOwner()
    {
        $this->installPipedriveIntegration(true, $this->features);

        $data = $this->getData('user.added');

        $this->makeRequest('POST', $data);

        $response     = $this->client->getResponse();
        $responseData = json_decode($response->getContent(), true);
        $po           = $this->em->getRepository(PipedriveOwner::class)->find(1);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertEquals($responseData['status'], 'ok');
        $this->assertEquals($po->getEmail(), 'test_user@test.com');
        $this->assertEquals($po->getOwnerId(), 2540581);
        $this->assertEquals(count($this->em->getRepository(PipedriveOwner::class)->findAll()), 1);
    }

    public function testAddPipedriveOwnerViaUpdate()
    {
        $this->installPipedriveIntegration(true, $this->features);
        $json = $this->getData('user.updated');

        $this->makeRequest('POST', $json);

        $response     = $this->client->getResponse();
        $responseData = json_decode($response->getContent(), true);
        $po           = $this->em->getRepository(PipedriveOwner::class)->find(1);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertEquals($responseData['status'], 'ok');
        $this->assertEquals($po->getEmail(), 'test123@test.com');
        $this->assertEquals($po->getOwnerId(), 2499712);
        $this->assertEquals(count($this->em->getRepository(PipedriveOwner::class)->findAll()), 1);
    }

    public function testUpdatePipedriveOwner()
    {
        $this->installPipedriveIntegration(true, $this->features);
        $json = $this->getData('user.updated');
        $data = json_decode($json, true);

        $this->addPipedriveOwner($data['current'][0]['id'], 'abc@test.pl');

        $this->makeRequest('POST', $json);

        $response     = $this->client->getResponse();
        $responseData = json_decode($response->getContent(), true);
        $po           = $this->em->getRepository(PipedriveOwner::class)->find(1);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertEquals($responseData['status'], 'ok');
        $this->assertEquals($po->getEmail(), 'test123@test.com');
        $this->assertEquals($po->getOwnerId(), 2499712);
        $this->assertEquals(count($this->em->getRepository(PipedriveOwner::class)->findAll()), 1);
    }
}
