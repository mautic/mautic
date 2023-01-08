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

    public function testAddPipedriveOwnerViaUpdate()
    {
        $this->installPipedriveIntegration(
            true,
            $this->features,
            [
                'url'   => '',
                'token' => 'token',
            ]
        );
        $json = $this->getData('user.updated');

        $this->makeRequest('POST', $json);

        $response     = $this->client->getResponse();
        $responseData = json_decode($response->getContent(), true);
        $owners       = $this->em->getRepository(PipedriveOwner::class)->findAll();

        $this->assertCount(1, $owners);
        $owner = reset($owners);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertEquals('ok', $responseData['status']);
        $this->assertEquals('test123@test.com', $owner->getEmail());
        $this->assertEquals(2499712, $owner->getOwnerId());
        $this->assertEquals(1, count($this->em->getRepository(PipedriveOwner::class)->findAll()));
    }

    public function testUpdatePipedriveOwner()
    {
        $this->installPipedriveIntegration(
            true,
            $this->features,
            [
                'url'   => '',
                'token' => 'token',
            ]
        );
        $json = $this->getData('user.updated');
        $data = json_decode($json, true);

        $this->addPipedriveOwner($data['current'][0]['id'], 'abc@test.pl');

        $this->makeRequest('POST', $json);

        $response     = $this->client->getResponse();
        $responseData = json_decode($response->getContent(), true);
        $owners       = $this->em->getRepository(PipedriveOwner::class)->findAll();

        $this->assertCount(1, $owners);
        $owner = reset($owners);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertEquals('ok', $responseData['status']);
        $this->assertEquals('test123@test.com', $owner->getEmail());
        $this->assertEquals(2499712, $owner->getOwnerId());
        $this->assertEquals(1, count($this->em->getRepository(PipedriveOwner::class)->findAll()));
    }
}
