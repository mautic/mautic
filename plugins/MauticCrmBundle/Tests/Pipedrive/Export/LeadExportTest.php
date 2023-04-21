<?php

namespace MauticPlugin\MauticCrmBundle\Tests\Pipedrive\Export;

use GuzzleHttp\Psr7\Response;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PluginBundle\Entity\IntegrationEntity;
use Mautic\PointBundle\Entity\Trigger;
use Mautic\PointBundle\Entity\TriggerEvent;
use MauticPlugin\MauticCrmBundle\Integration\PipedriveIntegration;
use MauticPlugin\MauticCrmBundle\Tests\Pipedrive\PipedriveTest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

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

    public function testAddPersonViaPointTrigger(): void
    {
        $iterations = 2;

        $this->mockHandler->append(new Response(SymfonyResponse::HTTP_OK, [], self::getData('Api/Post/persons/find')));
        $this->mockHandler->append(new Response(SymfonyResponse::HTTP_OK, [], self::getData('Api/Post/persons')));
        $this->mockHandler->append(new Response(SymfonyResponse::HTTP_OK, [], self::getData('Api/Post/persons/find')));

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

    public function testUpdatePersonWhenFeatureEnalbed(): void
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

        $this->mockHandler->append(new Response(SymfonyResponse::HTTP_OK, [], self::getData('Api/Put/persons/find')));
        $this->mockHandler->append(new Response(SymfonyResponse::HTTP_OK, [], self::getData('Api/Put/persons')));

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

        $this->assertStringEndsWith('Api/Put/persons', $this->mockHandler->getLastRequest()->getUri()->getPath());
        $this->assertEquals(
            'first_name=Test&last_name=User&email=test%40test.pl&phone=123456789&owner_id=0&name=Test+User',
            $this->mockHandler->getLastRequest()->getBody()->__toString()
        );
    }

    public function testUpdatePersonWithCompanyWhenFeatureIsDisabled(): void
    {
        $integrationId         = 97;
        $integrationCompanyId  = 66;
        $integrationCompany2Id = 77;

        $this->mockHandler->append(new Response(SymfonyResponse::HTTP_OK, [], self::getData('Api/Put/persons/find'))); // find by email
        $this->mockHandler->append(new Response(SymfonyResponse::HTTP_OK, [], self::getData('Api/Put/persons'))); // create person
        $this->mockHandler->append(new Response(SymfonyResponse::HTTP_OK, [], self::getData('Api/Put/persons'))); // update person
        $this->mockHandler->append(new Response(SymfonyResponse::HTTP_OK, [], self::getData('Api/Put/persons'))); // update person
        $this->mockHandler->append(new Response(SymfonyResponse::HTTP_OK, [], self::getData('Api/Put/persons'))); // update person

        // These 2 more requests happen. Should be investigated.
        // $this->mockHandler->append(new Response(SymfonyResponse::HTTP_OK, [], self::getData('Api/Put/persons/find'))); // find by email
        // $this->mockHandler->append(new Response(SymfonyResponse::HTTP_OK, [], self::getData('Api/Put/persons'))); // create person

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
            'lead[firstname]' => 'Test',
            'lead[lastname]'  => 'User',
            'lead[email]'     => 'test@test.pl',
            'lead[points]'    => 0,
            'lead[phone]'     => 123456789,
            'lead[companies]' => [],
        ]);
        $this->client->submit($form);

        $this->assertStringEndsWith('Api/Put/persons/'.$integrationId, $this->mockHandler->getLastRequest()->getUri()->getPath());
        $this->assertEquals(
            'first_name=Test&last_name=User&email=test%40test.pl&phone=123456789&owner_id=0&name=Test+User',
            $this->mockHandler->getLastRequest()->getBody()->__toString()
        );
    }

    public function testUpdatePersonWithOwner(): void
    {
        $pipedriveOwnerId = 55;

        $this->mockHandler->append(new Response(SymfonyResponse::HTTP_OK, [], self::getData('Api/Put/persons/find'))); // find by email
        $this->mockHandler->append(new Response(SymfonyResponse::HTTP_OK, [], self::getData('Api/Put/persons'))); // create person

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

        $this->assertStringEndsWith('Api/Put/persons', $this->mockHandler->getLastRequest()->getUri()->getPath());
        $this->assertEquals(
            'first_name=Test&last_name=User&email=test%40test.pl&phone=123456789&owner_id='.$pipedriveOwnerId.'&name=Test+User',
            $this->mockHandler->getLastRequest()->getBody()->__toString()
        );
    }

    public function testDeletePerson(): void
    {
        $integrationId    = 99;
        $pipedriveOwnerId = 55;

        $this->mockHandler->append(new Response(SymfonyResponse::HTTP_OK, [], self::getData('Api/Delete/persons')));

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

        $this->assertStringEndsWith('Api/Delete/persons/'.$integrationId, $this->mockHandler->getLastRequest()->getUri()->getPath());

        $this->assertSame(count($integrationEntities), 0);
        $this->assertSame(count($leads), 0);
    }
}
