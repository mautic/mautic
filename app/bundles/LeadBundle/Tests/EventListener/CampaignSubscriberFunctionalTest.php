<?php

namespace Mautic\LeadBundle\Tests\EventListener;

use Doctrine\DBAL\DBALException;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\Lead as CampaignLead;
use Mautic\CampaignBundle\Event\CampaignExecutionEvent;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\LeadEvents;
use PHPUnit\Framework\Assert;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\ApplicationTester;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class CampaignSubscriberFunctionalTest extends MauticMysqlTestCase
{
    private LeadRepository $contactRepository;

    /**
     * @var array<int, array<string, int|string>>
     */
    private array $contacts = [
        [
            'email'     => 'contact1@email.com',
            'firstname' => 'Isaac',
            'lastname'  => 'Asimov',
        ],
        [
            'email'     => 'contact2@email.com',
            'firstname' => 'Robert A.',
            'lastname'  => 'Heinlein',
            'points'    => 0,
        ],
        [
            'email'     => 'contact3@email.com',
            'firstname' => 'Arthur C.',
            'lastname'  => 'Clarke',
            'points'    => 1,
        ],
    ];

    /**
     * @var array<int, array<string, int|string>>
     */
    private array $stages = [
        [
            'name'        => 'novice',
            'weight'      => 1,
            'description' => 'This is the first stage',
            'isPublished' => 1,
        ],
        [
            'name'        => 'advanced beginner',
            'weight'      => 2,
            'description' => 'This is the second stage',
            'isPublished' => 1,
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->contactRepository  = $this->em->getRepository(Lead::class);
    }

    /**
     * Clean up after the tests.
     *
     * @throws DBALException
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        $this->truncateTables('leads', 'stages', 'campaigns', 'campaign_events');
    }

    public function testUpdateLeadAction(): void
    {
        $application = new Application(self::$kernel);
        $application->setAutoExit(false);
        $applicationTester = new ApplicationTester($application);

        $contactIds = $this->createContacts();
        $campaign   = $this->createCampaign($contactIds);

        // Force Doctrine to re-fetch the entities otherwise the campaign won't know about any events.
        $this->em->clear();

        // Execute the campaign.
        $exitCode = $applicationTester->run(
            [
                'command'       => 'mautic:campaigns:trigger',
                '--campaign-id' => $campaign->getId(),
            ]
        );

        Assert::assertSame(0, $exitCode, $applicationTester->getDisplay());

        /** @var Lead $contactA */
        $contactA = $this->contactRepository->getEntity($contactIds[0]);
        /** @var Lead $contactB */
        $contactB = $this->contactRepository->getEntity($contactIds[1]);
        /** @var Lead $contactC */
        $contactC = $this->contactRepository->getEntity($contactIds[2]);

        $this->assertEquals(42, $contactA->getPoints());
        $this->assertEquals(42, $contactB->getPoints());
        $this->assertEquals(42, $contactC->getPoints());
    }

    public function testLeadFieldValueDecisionWithUTM(): void
    {
        $application = new Application(self::$kernel);
        $application->setAutoExit(false);
        $applicationTester = new ApplicationTester($application);

        $contactIds = $this->createContacts();
        $campaign   = $this->createCampaign($contactIds);

        // Force Doctrine to re-fetch the entities otherwise the campaign won't know about any events.
        $this->em->clear();

        // Execute the campaign.
        $exitCode = $applicationTester->run(
            [
                'command'       => 'mautic:campaigns:trigger',
                '--campaign-id' => $campaign->getId(),
            ]
        );

        Assert::assertSame(0, $exitCode, $applicationTester->getDisplay());
    }

    public function testLeadFieldStageValueCondition(): void
    {
        $application = new Application(self::$kernel);
        $application->setAutoExit(false);
        $applicationTester = new ApplicationTester($application);

        $contactIds = $this->createContacts();
        $stageIds   = $this->createStages();
        $this->addStageToContacts($contactIds, $stageIds[0]);
        $campaign   = $this->createCampaignWithStageConditionEvent($contactIds);

        // Force Doctrine to re-fetch the entities otherwise the campaign won't know about any events.
        $this->em->clear();

        // Execute the campaign.
        $exitCode = $applicationTester->run(
            [
                'command'       => 'mautic:campaigns:trigger',
                '--campaign-id' => $campaign->getId(),
            ]
        );

        Assert::assertSame(0, $exitCode, $applicationTester->getDisplay());
    }

    public function testIsContactInOneOfStages(): void
    {
        $contactIds = $this->createContacts();
        $stageIds   = $this->createStages();
        $this->addStageToContacts($contactIds, $stageIds[0]);

        $args = [
            'event' => [
                'type'       => 'lead.stages',
                'properties' => [
                    'type'   => 'lead.stages',
                    'stages' => [0 => '1'],
                ],
            ],
            'eventDetails'    => [],
            'systemTriggered' => true,
            'eventSettings'   => [],
        ];

        foreach ($contactIds as $contactId) {
            $args['lead'] = $this->contactRepository->getEntity($contactId);

            $event  = new CampaignExecutionEvent($args, true);

            /** @var EventDispatcherInterface $dispatcher */
            $dispatcher = self::$container->get('event_dispatcher');
            $result     = $dispatcher->dispatch(
                LeadEvents::ON_CAMPAIGN_TRIGGER_CONDITION,
                $event
            );

            Assert::assertSame(true, $event->getResult());
        }
    }

    /**
     * @return array<int, int>
     */
    private function createContacts(): array
    {
        $this->client->request('POST', '/api/contacts/batch/new', $this->contacts);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertEquals(Response::HTTP_CREATED, $clientResponse->getStatusCode(), $clientResponse->getContent());
        $this->assertEquals(Response::HTTP_CREATED, $response['statusCodes'][0], $clientResponse->getContent());
        $this->assertEquals(Response::HTTP_CREATED, $response['statusCodes'][1], $clientResponse->getContent());
        $this->assertEquals(Response::HTTP_CREATED, $response['statusCodes'][2], $clientResponse->getContent());

        return [
            $response['contacts'][0]['id'],
            $response['contacts'][1]['id'],
            $response['contacts'][2]['id'],
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private function createStages(): array
    {
        foreach ($this->stages as $key => $stage) {
            $this->client->request('POST', '/api/stages/new', $stage);
            $clientResponse = $this->client->getResponse();
            $response       = json_decode($clientResponse->getContent(), true);

            $this->assertEquals(Response::HTTP_CREATED, $clientResponse->getStatusCode(), $clientResponse->getContent());

            $stages[$key] = $response['stage']['id'];
        }

        return $stages ?? [];
    }

    /**
     * @param array<int, int> $contactIds
     */
    private function addStageToContacts(array $contactIds, int $stageId): void
    {
        foreach ($contactIds as $contactId) {
            $this->client->request('POST', "/api/stages/$stageId/contact/$contactId/add");
            $clientResponse = $this->client->getResponse();

            $this->assertEquals(Response::HTTP_OK, $clientResponse->getStatusCode(), $clientResponse->getContent());
        }
    }

    /**
     * @param array<int, int> $contactIds
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    private function createCampaign(array $contactIds): Campaign
    {
        $campaign = new Campaign();
        $campaign->setName('Test Update contact');

        $this->em->persist($campaign);
        $this->em->flush();

        foreach ($contactIds as $key => $contactId) {
            $campaignLead = new CampaignLead();
            $campaignLead->setCampaign($campaign);
            $campaignLead->setLead($this->em->getReference(Lead::class, $contactId));
            $campaignLead->setDateAdded(new \DateTime());
            $this->em->persist($campaignLead);
            $campaign->addLead($key, $campaignLead);
        }

        $this->em->flush();

        $event = new Event();
        $event->setCampaign($campaign);
        $event->setName('Update test_datetime2_field to yesterday');
        $event->setType('lead.updatelead');
        $event->setEventType('action');
        $event->setTriggerMode('immediate');
        $event->setProperties(
            [
                'canvasSettings'             => [
                    'droppedX' => '696',
                    'droppedY' => '155',
                ],
                'name'                       => '',
                'triggerMode'                => 'immediate',
                'triggerDate'                => null,
                'triggerInterval'            => '1',
                'triggerIntervalUnit'        => 'd',
                'triggerHour'                => '',
                'triggerRestrictedStartHour' => '',
                'triggerRestrictedStopHour'  => '',
                'anchor'                     => 'leadsource',
                'properties'                 => [
                    'html'                 => '',
                    'title'                => '',
                    'html2'                => '',
                    'firstname'            => '',
                    'lastname'             => '',
                    'company'              => '',
                    'position'             => '',
                    'email'                => '',
                    'mobile'               => '',
                    'phone'                => '',
                    'points'               => 42,
                    'fax'                  => '',
                    'address1'             => '',
                    'address2'             => '',
                    'city'                 => '',
                    'state'                => '',
                    'zipcode'              => '',
                    'country'              => '',
                    'preferred_locale'     => '',
                    'timezone'             => '',
                    'last_active'          => '',
                    'attribution_date'     => '',
                    'attribution'          => '',
                    'website'              => '',
                    'facebook'             => '',
                    'foursquare'           => '',
                    'instagram'            => '',
                    'linkedin'             => '',
                    'skype'                => '',
                    'twitter'              => '',
                ],
                'type'                       => 'lead.updatelead',
                'eventType'                  => 'action',
                'anchorEventType'            => 'source',
                'campaignId'                 => 'mautic_28ac4b8a4758b8597e8d189fa97b245996e338bb',
                '_token'                     => 'HgysZwvH_n0uAp47CcAcsGddRnRk65t-3crOnuLx28Y',
                'buttons'                    => ['save' => ''],
                'html'                       => null,
                'title'                      => null,
                'html2'                      => null,
                'firstname'                  => null,
                'lastname'                   => null,
                'company'                    => null,
                'position'                   => null,
                'email'                      => null,
                'mobile'                     => null,
                'phone'                      => null,
                'points'                     => 42,
                'fax'                        => null,
                'address1'                   => null,
                'address2'                   => null,
                'city'                       => null,
                'state'                      => null,
                'zipcode'                    => null,
                'country'                    => null,
                'preferred_locale'           => null,
                'timezone'                   => null,
                'last_active'                => null,
                'attribution_date'           => null,
                'attribution'                => null,
                'website'                    => null,
                'facebook'                   => null,
                'foursquare'                 => null,
                'instagram'                  => null,
                'linkedin'                   => null,
                'skype'                      => null,
                'twitter'                    => null,
            ]
        );

        $this->em->persist($event);
        $this->em->flush();

        $event2 = new Event();
        $event2->setCampaign($campaign);
        $event2->setName('Check UTM Source Lead Field Value');
        $event2->setType('lead.field_value');
        $event2->setEventType('condition');
        $event2->setTriggerMode('immediate');
        $event2->setProperties(
            [
                'canvasSettings'             => [
                    'droppedX' => '696',
                    'droppedY' => '155',
                ],
                'name'                       => '',
                'triggerMode'                => 'immediate',
                'triggerDate'                => null,
                'triggerInterval'            => '1',
                'triggerIntervalUnit'        => 'd',
                'triggerHour'                => '',
                'triggerRestrictedStartHour' => '',
                'triggerRestrictedStopHour'  => '',
                'anchor'                     => 'leadsource',
                'properties'                 => [
                    'field'    => 'utm_source',
                    'operator' => '=',
                    'value'    => 'val',
                ],
                'type'                       => 'lead.field_value',
                'eventType'                  => 'condition',
                'anchorEventType'            => 'condition',
                'campaignId'                 => 'mautic_28ac4b8a4758b8597e8d189fa97b245996e338bb',
                '_token'                     => 'HgysZwvH_n0uAp47CcAcsGddRnRk65t-3crOnuLx28Y',
                'buttons'                    => ['save' => ''],
                'field'                      => 'utm_source',
                'operator'                   => '=',
                'value'                      => 'val',
            ]
        );

        $this->em->persist($event2);
        $this->em->flush();

        $campaign->setCanvasSettings(
            [
                'nodes'       => [
                    [
                        'id'        => $event->getId(),
                        'positionX' => '696',
                        'positionY' => '155',
                    ],
                    [
                        'id'        => $event2->getId(),
                        'positionX' => '696',
                        'positionY' => '155',
                    ],
                    [
                        'id'        => 'lists',
                        'positionX' => '796',
                        'positionY' => '50',
                    ],
                ],
                'connections' => [
                    [
                        'sourceId' => 'lists',
                        'targetId' => $event->getId(),
                        'anchors'  => [
                            'source' => 'leadsource',
                            'target' => 'top',
                        ],
                    ],
                    [
                        'sourceId' => 'lists',
                        'targetId' => $event2->getId(),
                        'anchors'  => [
                            'source' => 'leadsource',
                            'target' => 'top',
                        ],
                    ],
                ],
            ]
        );

        $this->em->persist($campaign);
        $this->em->flush();

        return $campaign;
    }

    /**
     * @param array<int, int> $contactIds
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    private function createCampaignWithStageConditionEvent(array $contactIds): Campaign
    {
        $campaign = new Campaign();
        $campaign->setName('Test Update contact');

        $this->em->persist($campaign);
        $this->em->flush();

        foreach ($contactIds as $key => $contactId) {
            $campaignLead = new CampaignLead();
            $campaignLead->setCampaign($campaign);
            $campaignLead->setLead($this->em->getReference(Lead::class, $contactId));
            $campaignLead->setDateAdded(new \DateTime());
            $this->em->persist($campaignLead);
            $campaign->addLead($key, $campaignLead);
        }

        $this->em->flush();

        $event1 = new Event();
        $event1->setCampaign($campaign);
        $event1->setName('Check if the contact on one of the stage(s)');
        $event1->setType('lead.stages');
        $event1->setEventType('condition');
        $event1->setTriggerMode('immediate');
        $event1->setProperties(
            [
                'canvasSettings'             => [
                    'droppedX' => '696',
                    'droppedY' => '155',
                ],
                'name'                       => 'Contact stages',
                'triggerMode'                => 'immediate',
                'triggerDate'                => null,
                'triggerInterval'            => '1',
                'triggerIntervalUnit'        => 'd',
                'triggerHour'                => '',
                'triggerRestrictedStartHour' => '',
                'triggerRestrictedStopHour'  => '',
                'order'                      => 1,
                'anchor'                     => 'leadsource',
                'properties'                 => ['stages' => [0 => '1']],
                'type'                       => 'lead.stages',
                'eventType'                  => 'condition',
                'anchorEventType'            => 'source',
                'campaignId'                 => 'mautic_28ac4b8a4758b8597e8d189fa97b245996e338bb',
                '_token'                     => 'HgysZwvH_n0uAp47CcAcsGddRnRk65t-3crOnuLx28Y',
                'buttons'                    => ['save' => ''],
                'stages'                     => [0 => '1'],
            ]
        );

        $this->em->persist($event1);
        $this->em->flush();

        $event2 = new Event();
        $event2->setCampaign($campaign);
        $event2->setName('Change contact\'s stage');
        $event2->setType('stage.change');
        $event2->setEventType('action');
        $event2->setTriggerMode('immediate');
        $event2->setProperties(
            [
                'canvasSettings'             => [
                    'droppedX' => '696',
                    'droppedY' => '155',
                ],
                'name'                       => '',
                'triggerMode'                => 'immediate',
                'triggerDate'                => null,
                'triggerInterval'            => '1',
                'triggerIntervalUnit'        => 'd',
                'triggerHour'                => '',
                'triggerRestrictedStartHour' => '',
                'triggerRestrictedStopHour'  => '',
                'order'                      => 2,
                'anchor'                     => 'bottom',
                'properties'                 => ['stage' => '2'],
                'type'                       => 'stage.change',
                'eventType'                  => 'action',
                'anchorEventType'            => 'action',
                'campaignId'                 => 'mautic_28ac4b8a4758b8597e8d189fa97b245996e338bb',
                '_token'                     => 'HgysZwvH_n0uAp47CcAcsGddRnRk65t-3crOnuLx28Y',
                'buttons'                    => ['save' => ''],
                'stage'                      => 2,
            ]
        );

        $this->em->persist($event2);
        $this->em->flush();

        $campaign->setCanvasSettings(
            [
                'nodes'       => [
                    [
                        'id'        => $event2->getId(),
                        'positionX' => '696',
                        'positionY' => '155',
                    ],
                    [
                        'id'        => $event2->getId(),
                        'positionX' => '696',
                        'positionY' => '155',
                    ],
                    [
                        'id'        => 'lists',
                        'positionX' => '796',
                        'positionY' => '50',
                    ],
                ],
                'connections' => [
                    [
                        'sourceId' => 'lists',
                        'targetId' => $event1->getId(),
                        'anchors'  => [
                            'source' => 'leadsource',
                            'target' => 'top',
                        ],
                    ],
                    [
                        'sourceId' => 'lists',
                        'targetId' => $event2->getId(),
                        'anchors'  => [
                            'source' => 'leadsource',
                            'target' => 'top',
                        ],
                    ],
                ],
            ]
        );

        $this->em->persist($campaign);
        $this->em->flush();

        return $campaign;
    }
}
