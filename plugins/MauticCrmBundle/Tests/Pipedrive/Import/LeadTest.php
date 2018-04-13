<?php

namespace MauticPlugin\MauticCrmBundle\Tests\Pipedrive\Import;

use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\MauticCrmBundle\Tests\Pipedrive\PipedriveTest;

class LeadTest extends PipedriveTest
{
    private $features = [
        'objects' => [
            'company',
        ],
        'leadFields' => [
            'first_name' => 'firstname',
            'last_name'  => 'lastname',
            'email'      => 'email',
            'phone'      => 'phone',
        ],
    ];

    private $updateData = ['email' => 'test@test.pl', 'firstname'=> 'Test', 'lastname'=>'Person', 'phone'=>'678465345'];

    public function testCreateLead()
    {
        $this->installPipedriveIntegration(true, $this->features);
        $data = $this->getData('person.added');

        $this->makeRequest('POST', $data);

        $response     = $this->client->getResponse();
        $responseData = json_decode($response->getContent(), true);
        $lead         = $this->em->getRepository(Lead::class)->find(1);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertEquals($responseData['status'], 'ok');
        $this->assertEquals($lead->getName(), 'Test Person');
        $this->assertEquals($lead->getEmail(), 'test@test.pl');
        $this->assertEquals($lead->getPhone(), '678465345');
        $this->assertNotNull($lead->getDateAdded());
        $this->assertEquals($lead->getPreferredProfileImage(), 'gravatar');
        $this->assertEquals(count($this->em->getRepository(Lead::class)->findAll()), 1);
    }

    public function testCreateLeadWithOwner()
    {
        $this->installPipedriveIntegration(true, $this->features);
        $json = $this->getData('person.added');
        $data = json_decode($json, true);

        //add user
        $owner = $this->createUser(true, 'admin@admin.pl');
        $this->addPipedriveOwner($data['current']['owner_id'], $owner->getEmail());

        $this->makeRequest('POST', $json);

        $response     = $this->client->getResponse();
        $responseData = json_decode($response->getContent(), true);
        $lead         = $this->em->getRepository(Lead::class)->find(1);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertEquals($responseData['status'], 'ok');
        $this->assertEquals($lead->getName(), 'Test Person');
        $this->assertEquals($lead->getEmail(), 'test@test.pl');
        $this->assertEquals($lead->getPhone(), '678465345');
        $this->assertEquals($lead->getOwner()->getEmail(), $owner->getEmail());
        $this->assertNotNull($lead->getDateAdded());
        $this->assertEquals($lead->getPreferredProfileImage(), 'gravatar');
        $this->assertEquals(count($this->em->getRepository(Lead::class)->findAll()), 1);
    }

    public function testCreateLeadWithCompany()
    {
        $newCompanyId   = 88;
        $newCompanyName = 'New Company Name';

        $this->installPipedriveIntegration(true, $this->features);
        $data = json_decode($json = $this->getData('person.added'), true);

        $newCompany = $this->createCompany($newCompanyName);

        $this->createCompanyIntegrationEntity($newCompanyId, $newCompany->getId());

        $data['current']['org_id'] = $newCompanyId;

        $this->makeRequest('POST', json_encode($data));

        $response     = $this->client->getResponse();
        $responseData = json_decode($response->getContent(), true);
        $lead         = $this->em->getRepository(Lead::class)->find(1);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertEquals($responseData['status'], 'ok');
        $this->assertEquals($lead->getName(), 'Test Person');
        $this->assertEquals($lead->getEmail(), 'test@test.pl');
        $this->assertEquals($lead->getPhone(), '678465345');
        $this->assertEquals($lead->getCompany(), $newCompanyName);
        $this->assertNotNull($lead->getDateAdded());
        $this->assertEquals($lead->getPreferredProfileImage(), 'gravatar');
        $this->assertEquals(count($this->em->getRepository(Lead::class)->findAll()), 1);
    }

    public function testCreateSameLeadMultipleTimes()
    {
        $this->installPipedriveIntegration(true, $this->features);

        $data = $this->getData('person.added');
        $this->makeRequest('POST', $data);
        $this->makeRequest('POST', $data);
        $this->makeRequest('POST', $data);

        $this->assertEquals(count($this->em->getRepository(Lead::class)->findAll()), 1);
    }

    public function testCreateLeadViaUpdate()
    {
        $this->installPipedriveIntegration(true, $this->features);
        $data = $this->getData('person.updated');

        $this->makeRequest('POST', $data);

        $response     = $this->client->getResponse();
        $responseData = json_decode($response->getContent(), true);
        $lead         = $this->em->getRepository(Lead::class)->find(1);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertEquals($responseData['status'], 'ok');
        $this->assertEquals($lead->getName(), 'Test Person');
        $this->assertEquals($lead->getEmail(), 'test@test.pl');
        $this->assertEquals($lead->getPhone(), '678465345');
        $this->assertNotNull($lead->getDateAdded());
        $this->assertEquals($lead->getPreferredProfileImage(), 'gravatar');
    }

    public function testUpdateLead()
    {
        $this->installPipedriveIntegration(true, $this->features);
        $data = json_decode($this->getData('person.updated'), true);

        $lead = $this->createLead([], null, $this->updateData);
        $this->createLeadIntegrationEntity($data['current']['id'], $lead->getId());

        $this->makeRequest('POST', json_encode($data));

        $response     = $this->client->getResponse();
        $responseData = json_decode($response->getContent(), true);
        $lead         = $this->em->getRepository(Lead::class)->find(1);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertEquals($responseData['status'], 'ok');
        $this->assertEquals($lead->getName(), 'Test Person');
        $this->assertEquals($lead->getEmail(), 'test@test.pl');
        $this->assertEquals($lead->getPhone(), '678465345');
        $this->assertNull($lead->getOwner());
        $this->assertNull($lead->getCompany());
        $this->assertNotNull($lead->getDateModified());
    }

    public function testUpdateLeadOwner()
    {
        $newOwnerId    = 88;
        $newOwnerEmail = 'new@admin.com';

        $this->installPipedriveIntegration(true, $this->features);

        $data = json_decode($this->getData('person.updated'), true);

        $oldUser = $this->createUser(true);
        $lead    = $this->createLead([], $oldUser);
        $newUser = $this->createUser(true, $newOwnerEmail, 'admin2');

        $this->addPipedriveOwner($newOwnerId, $newUser->getEmail());

        $data['current']['owner_id'] = $newOwnerId;

        $this->makeRequest('POST', json_encode($data));

        $response     = $this->client->getResponse();
        $responseData = json_decode($response->getContent(), true);
        $lead         = $this->em->getRepository(Lead::class)->find(2);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertEquals($responseData['status'], 'ok');
        $this->assertNotNull($lead->getOwner());
        $this->assertEquals($lead->getOwner()->getEmail(), $newOwnerEmail);
    }

    public function testUpdateLeadCompany()
    {
        $newCompanyId      = 88;
        $newCompanyName    = 'New Company Name';
        $newCompanyAddress = 'Madrit, Spain';

        $this->installPipedriveIntegration(true, $this->features);

        $data = json_decode($this->getData('person.updated'), true);

        $oldCompany = $this->createCompany();
        $newCompany = $this->createCompany($newCompanyName, $newCompanyAddress);

        $lead = $this->createLead($oldCompany, null);

        $this->createLeadIntegrationEntity($data['current']['id'], $lead->getId());
        $this->createCompanyIntegrationEntity($newCompanyId, $newCompany->getId());

        $data['current']['org_id'] = $newCompanyId;
        $this->makeRequest('POST', json_encode($data));

        $response     = $this->client->getResponse();
        $responseData = json_decode($response->getContent(), true);
        $lead         = $this->em->getRepository(Lead::class)->find(1);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertEquals($responseData['status'], 'ok');
        $this->assertNotNull($lead->getCompany());
        $this->assertEquals($lead->getCompany(), $newCompanyName);
        $this->assertNotNull($lead->getDateModified());
    }

    public function testRemoveLeadCompany()
    {
        $companyModel = $this->container->get('mautic.lead.model.company');

        $this->installPipedriveIntegration(true, $this->features);
        $data = json_decode($this->getData('person.updated'), true);

        $oldCompany    = $this->createCompany();
        $lead          = $this->createLead($oldCompany, null, $this->updateData);
        $leadCompanies = $companyModel->getCompanyLeadRepository()->getCompaniesByLeadId($lead->getId());
        $this->assertEquals(count($leadCompanies), 1);

        $this->createLeadIntegrationEntity($data['current']['id'], $lead->getId());

        $this->makeRequest('POST', json_encode($data));

        $response     = $this->client->getResponse();
        $responseData = json_decode($response->getContent(), true);
        $lead         = $this->em->getRepository(Lead::class)->find(1);

        $leadCompanies = $companyModel->getCompanyLeadRepository()->getCompaniesByLeadId($lead->getId());

        $this->assertSame(200, $response->getStatusCode());
        $this->assertEquals($responseData['status'], 'ok');
        $this->assertEquals(count($leadCompanies), 0);
        $this->assertNotNull($lead->getDateModified());
    }

    public function testRemoveLeadOwner()
    {
        $this->installPipedriveIntegration(true, $this->features);
        $data = json_decode($this->getData('person.updated'), true);

        $owner = $this->createUser(true);
        $lead  = $this->createLead([], $owner);

        $this->assertNotNull($lead->getOwner());
        $this->assertEquals($lead->getOwner()->getEmail(), $owner->getEmail());

        $this->createLeadIntegrationEntity($data['current']['id'], $lead->getId());

        $data['current']['owner_id'] = null;

        $this->makeRequest('POST', json_encode($data));

        $response     = $this->client->getResponse();
        $responseData = json_decode($response->getContent(), true);
        $lead         = $this->em->getRepository(Lead::class)->find(1);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertEquals($responseData['status'], 'ok');
        $this->assertNull($lead->getOwner());
        $this->assertNotNull($lead->getDateModified());
    }

    public function testDeleteLead()
    {
        $features = [
            'leadFields' => [
                'first_name' => 'firstname',
                'last_name'  => 'lastname',
                'email'      => 'email',
                'phone'      => 'phone',
            ],
        ];

        $this->installPipedriveIntegration(true, $features);
        $lead = $this->createLead();
        $json = $this->getData('person.deleted');
        $data = json_decode($json, true);
        $this->createLeadIntegrationEntity($data['previous']['id'], $lead->getId());

        $this->assertEquals(count($this->em->getRepository(Lead::class)->findAll()), 1);

        $this->makeRequest('POST', json_encode($data));

        $response     = $this->client->getResponse();
        $responseData = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertEquals($responseData['status'], 'ok');
        $this->assertEquals(count($this->em->getRepository(Lead::class)->findAll()), 0);
    }
}
