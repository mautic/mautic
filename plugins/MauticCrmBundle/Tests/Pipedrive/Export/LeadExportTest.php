<?php

namespace MauticPlugin\MauticCrmBundle\Tests\Pipedrive\Export;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\PluginBundle\Entity\IntegrationEntity;
use Mautic\PointBundle\Entity\Trigger;
use Mautic\PointBundle\Entity\TriggerEvent;
use MauticPlugin\MauticCrmBundle\Integration\PipedriveIntegration;
use MauticPlugin\MauticCrmBundle\Tests\Pipedrive\PipedriveTest;
use Symfony\Component\HttpFoundation\Request;

class LeadExportTest extends PipedriveTest
{
    private $features = [
        'leadFields' => [
            'first_name' => 'firstname',
            'last_name'  => 'lastname',
            'email'      => 'email',
            'phone'      => 'phone',
        ],
    ];

    public function testAddPersonViaPointTrigger()
    {
        $iterations = 2;

        $this->installPipedriveIntegration(
            true,
            $this->features,
            [
                'url'   => 'Api/Post',
                'token' => 'token',
            ]
        );

        $trigger = new Trigger();
        $trigger->setName('Add Lead To Integration');
        $trigger->setIsPublished(true);

        $this->em->persist($trigger);

        $triggerEvent = new TriggerEvent();
        $triggerEvent->setTrigger($trigger);
        $triggerEvent->setName('Push contact to integration');
        $triggerEvent->setType('plugin.leadpush');

        $this->em->persist($triggerEvent);
        $this->em->flush();

        for ($i = 0; $i < $iterations; ++$i) {
            $crawler     = $this->client->request(Request::METHOD_GET, '/s/contacts/new');
            $formCrawler = $crawler->filter('form[name=lead]');
            $this->assertSame(1, $formCrawler->count());

            $form = $formCrawler->form();
            $form->setValues([
                'lead[firstname]' => 'Test'.$i,
                'lead[lastname]'  => 'User'.$i,
                'lead[email]'     => 'test'.$i.'@test.pl',
            ]);
            $this->client->submit($form);
        }

        $integrationEntities = $this->em->getRepository(IntegrationEntity::class)->findAll();
        $integrationEntity   = $integrationEntities[0];

        $this->assertEquals($integrationEntity->getIntegrationEntity(), PipedriveIntegration::PERSON_ENTITY_TYPE);
        $this->assertEquals($integrationEntity->getInternalEntity(), PipedriveIntegration::LEAD_ENTITY_TYPE);
        $this->assertEquals($integrationEntity->getIntegration(), PipedriveIntegration::INTEGRATION_NAME);
    }

    public function testUpdatePerson()
    {
        $this->installPipedriveIntegration(
            true,
            $this->features,
            [
                'url'   => 'Api/Put',
                'token' => 'token',
            ]
        );
        $lead = $this->createLead();

        $crawler     = $this->client->request(Request::METHOD_GET, '/s/contacts/edit/'.$lead->getId());
        $formCrawler = $crawler->filter('form[name=lead]');
        $this->assertSame(1, $formCrawler->count());

        $form = $formCrawler->form();
        $form->setValues([
            'lead[firstname]' => 'Test',
            'lead[lastname]'  => 'User',
            'lead[email]'     => 'test@test.pl',
            'lead[points]'    => 0,
            'lead[phone]'     => 123456789,
        ]);
        $this->client->submit($form);

        $requests = $GLOBALS['requests'];
        $request  = $requests['POST/Api/Put/persons'];

        $this->assertSame(count($request), 1);
        $this->assertEquals($request[0]['form_params']['first_name'], 'Test');
        $this->assertEquals($request[0]['form_params']['last_name'], 'User');
        $this->assertEquals($request[0]['form_params']['email'], 'test@test.pl');
        $this->assertEquals($request[0]['form_params']['phone'], '123456789');
    }

    public function testUpdatePersonWithCompanyWhenFeatureIsDisabled()
    {
        $integrationId         = 97;
        $integrationCompanyId  = 66;
        $integrationCompany2Id = 77;

        $this->installPipedriveIntegration(
            true,
            $this->features,
            [
                'url'   => 'Api/Put',
                'token' => 'token',
            ]
        );

        $company  = $this->createCompany();
        $company2 = $this->createCompany('Main Company', 'Main Company Address1');
        $lead     = $this->createLead([$company, $company2]);

        $this->createCompanyIntegrationEntity($integrationCompanyId, $company->getId());
        $this->createCompanyIntegrationEntity($integrationCompany2Id, $company2->getId());

        $crawler     = $this->client->request(Request::METHOD_GET, '/s/contacts/edit/'.$lead->getId());
        $formCrawler = $crawler->filter('form[name=lead]');
        $this->assertSame(1, $formCrawler->count());

        $form = $formCrawler->form();
        $form->setValues([
            'lead[firstname]'     => 'Test',
            'lead[lastname]'      => 'User',
            'lead[email]'         => 'test@test.pl',
            'lead[points]'        => 0,
            'lead[phone]'         => 123456789,
            'lead[companies]'     => [],
        ]);
        $this->client->submit($form);

        $requests = $GLOBALS['requests'];
        $request  = $requests['PUT/Api/Put/persons/'.$integrationId][1];

        $this->assertSame(count($requests), 3);
        $this->assertEquals($request['form_params']['first_name'], 'Test');
        $this->assertEquals($request['form_params']['last_name'], 'User');
        $this->assertEquals($request['form_params']['email'], 'test@test.pl');
        $this->assertEquals($request['form_params']['phone'], '123456789');
        $this->assertEquals(isset($request['form_params']['org_id']), false);
    }

    public function testUpdatePersonWithOwner()
    {
        $pipedriveOwnerId = 55;

        $this->installPipedriveIntegration(
            true,
            $this->features,
            [
                'url'   => 'Api/Put',
                'token' => 'token',
            ]
        );

        $owner = $this->createUser(true, 'user@email.com', 'new_user');
        $lead  = $this->createLead();
        $this->addPipedriveOwner($pipedriveOwnerId, $owner->getEmail());

        $crawler     = $this->client->request(Request::METHOD_GET, '/s/contacts/edit/'.$lead->getId());
        $formCrawler = $crawler->filter('form[name=lead]');
        $this->assertSame(1, $formCrawler->count());

        $form = $formCrawler->form();
        $form->setValues([
            'lead[firstname]' => 'Test',
            'lead[lastname]'  => 'User',
            'lead[email]'     => 'test@test.pl',
            'lead[points]'    => 0,
            'lead[phone]'     => 123456789,
            'lead[owner]'     => $owner->getId(),
        ]);
        $this->client->submit($form);

        $requests = $GLOBALS['requests'];
        $request  = $requests['POST/Api/Put/persons'][0];

        $this->assertSame(count($requests['POST/Api/Put/persons']), 1);
        $this->assertEquals($request['form_params']['first_name'], 'Test');
        $this->assertEquals($request['form_params']['last_name'], 'User');
        $this->assertEquals($request['form_params']['email'], 'test@test.pl');
        $this->assertEquals($request['form_params']['phone'], '123456789');
        $this->assertEquals($request['form_params']['owner_id'], $pipedriveOwnerId);
    }

    public function testDeletePerson()
    {
        $integrationId    = 99;
        $pipedriveOwnerId = 55;

        $this->installPipedriveIntegration(
            true,
            $this->features,
            [
                'url'   => 'Api/Delete',
                'token' => 'token',
            ]
        );

        $owner = $this->createUser(true, 'user@email.com', 'new_user');
        $lead  = $this->createLead();
        $this->createLeadIntegrationEntity($integrationId, $lead->getId());
        $this->addPipedriveOwner($pipedriveOwnerId, $owner->getEmail());

        $this->client->request(
            Request::METHOD_POST,
            '/s/contacts/delete/'.$lead->getId().'?mauticUserLastActive=1&mauticLastNotificationId=',
            []
        );

        $integrationEntities = $this->em->getRepository(IntegrationEntity::class)->findAll();
        $leads               = $this->em->getRepository(Lead::class)->findAll();
        $requests            = $GLOBALS['requests'];
        $request             = $requests['DELETE/Api/Delete/persons/'.$integrationId][0];

        $this->assertSame(count($requests), 1);
        $this->assertSame(count($integrationEntities), 0);
        $this->assertSame(count($leads), 0);
        $this->assertEmpty($request['form_params']);
    }
}
