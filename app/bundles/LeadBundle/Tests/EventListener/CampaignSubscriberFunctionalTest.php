<?php

namespace Mautic\LeadBundle\Tests\EventListener;

use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\Lead as CampaignLead;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Event\CampaignExecutionEvent;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\DataObject\LeadManipulator;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Tests\Traits\LeadFieldTestTrait;
use Mautic\PointBundle\Entity\Group;
use Mautic\PointBundle\Entity\GroupContactScore;
use PHPUnit\Framework\Assert;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\ApplicationTester;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class CampaignSubscriberFunctionalTest extends MauticMysqlTestCase
{
    use LeadFieldTestTrait;

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
        if ('testUpdatesContactCampaignActionWithBooleanFields' === $this->getName()) {
            $this->useCleanupRollback = false;
        } else {
            $this->useCleanupRollback = true;
        }

        parent::setUp();

        $this->contactRepository = $this->em->getRepository(Lead::class);
    }

    protected function beforeBeginTransaction(): void
    {
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
            $dispatcher = static::getContainer()->get('event_dispatcher');
            $result     = $dispatcher->dispatch(
                $event,
                LeadEvents::ON_CAMPAIGN_TRIGGER_CONDITION
            );

            Assert::assertTrue($event->getResult());
        }
    }

    public function testLeadPointEvents(): void
    {
        $application = new Application(self::$kernel);
        $application->setAutoExit(false);
        $applicationTester = new ApplicationTester($application);

        $contactIds = $this->createContacts();
        $campaign   = $this->createCampaignWithPointEvents($contactIds);

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

        $this->assertEquals(0, $contactA->getPoints());
        $this->assertEquals(0, $contactB->getPoints());
        $this->assertEquals(2, $contactC->getPoints());
    }

    public function testLeadGroupPointEvents(): void
    {
        $application = new Application(self::$kernel);
        $application->setAutoExit(false);
        $applicationTester = new ApplicationTester($application);

        $groupA    = $this->createGroup('A');
        $this->em->flush();

        $contactIds = $this->createContacts();

        /** @var Lead $contactB */
        $contactB = $this->contactRepository->getEntity($contactIds[1]);
        $this->addGroupContactScore($contactB, $groupA, 0);
        $this->em->persist($contactB);

        /** @var Lead $contactC */
        $contactC = $this->contactRepository->getEntity($contactIds[2]);
        $this->addGroupContactScore($contactC, $groupA, 1);
        $this->em->persist($contactC);
        $this->em->flush();

        $campaign   = $this->createCampaignWithPointEvents($contactIds, $groupA->getId());

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

        // point update action with selected group shouldn't update contact main points
        $this->assertEquals(0, $contactA->getPoints());
        $this->assertEquals(0, $contactB->getPoints());
        $this->assertEquals(1, $contactC->getPoints());

        $contactAGroupScores = $contactA->getGroupScores();
        $contactBGroupScores = $contactB->getGroupScores();
        $contactCGroupScores = $contactC->getGroupScores();

        $this->assertEmpty($contactAGroupScores);
        $this->assertNotEmpty($contactBGroupScores);
        $this->assertNotEmpty($contactCGroupScores);

        $this->assertEquals(0, $contactA->getPoints());
        $this->assertEquals(0, $contactBGroupScores->first()->getScore());
        $this->assertEquals(2, $contactCGroupScores->first()->getScore());
    }

    public function testUpdatesContactCampaignActionWithBooleanFields(): void
    {
        $this->createField([
            'alias' => 'bool1',
            'label' => 'Bool 1',
            'type'  => 'boolean',
        ]);
        $this->createField([
            'alias' => 'bool2',
            'label' => 'Bool 2',
            'type'  => 'boolean',
        ]);
        $this->createField([
            'alias' => 'bool3',
            'label' => 'Bool 3',
            'type'  => 'boolean',
        ]);

        $lead1      = $this->createContact('test_null_'.uniqid().'@example.com');
        $contactId1 = $lead1->getId();

        $lead2      = $this->createContact('test_false_'.uniqid().'@example.com');
        $contactId2 = $lead2->getId();

        $lead3      = $this->createContact('test_true_'.uniqid().'@example.com');
        $contactId3 = $lead3->getId();

        $leadModel = $this->getContainer()->get('mautic.lead.model.lead');

        $leadModel->setFieldValues($lead1, [
            'bool1' => null,
            'bool2' => null,
            'bool3' => null,
        ]);
        $leadModel->saveEntity($lead1);

        $leadModel->setFieldValues($lead2, [
            'bool1' => false,
            'bool2' => false,
            'bool3' => false,
        ]);
        $leadModel->saveEntity($lead2);

        $leadModel->setFieldValues($lead3, [
            'bool1' => true,
            'bool2' => true,
            'bool3' => true,
        ]);
        $leadModel->saveEntity($lead3);

        $campaign = new Campaign();
        $campaign->setName('Test Bool Campaign');
        $this->em->persist($campaign);

        $this->addContactToCampaign($campaign, $lead1);
        $this->addContactToCampaign($campaign, $lead2);
        $this->addContactToCampaign($campaign, $lead3);

        $event = new Event();
        $event->setCampaign($campaign);
        $event->setName('Update contact bools');
        $event->setType('lead.updatelead');
        $event->setEventType('action');
        $event->setTriggerMode('immediate');
        $event->setProperties([
            'bool1' => '', // No change
            'bool2' => 0,  // No
            'bool3' => 1,  // Yes
        ]);

        $campaign->addEvent(1, $event);
        $this->em->persist($campaign);
        $this->em->flush();

        $this->em->clear();

        $exitCode = $this->testSymfonyCommand('mautic:campaigns:trigger', ['--campaign-id' => $campaign->getId()]);
        $this->assertSame(0, $exitCode->getStatusCode());

        $lead1 = $this->contactRepository->getEntity($contactId1);
        $lead2 = $this->contactRepository->getEntity($contactId2);
        $lead3 = $this->contactRepository->getEntity($contactId3);

        $result1 = [
            $lead1->getFieldValue('bool1'),
            $lead1->getFieldValue('bool2'),
            $lead1->getFieldValue('bool3'),
        ];
        $result2 = [
            $lead2->getFieldValue('bool1'),
            $lead2->getFieldValue('bool2'),
            $lead2->getFieldValue('bool3'),
        ];
        $result3 = [
            $lead3->getFieldValue('bool1'),
            $lead3->getFieldValue('bool2'),
            $lead3->getFieldValue('bool3'),
        ];

        $this->assertNull($result1[0], 'Expected bool1 to remain null for contact 1');
        $this->assertEquals(false, $result1[1], 'Failed to update bool2 from null to false for contact 1');
        $this->assertEquals(true, $result1[2], 'Failed to update bool3 from null to true for contact 1');

        $this->assertEquals(false, $result2[0], 'Expected bool1 to remain false for contact 2');
        $this->assertEquals(false, $result2[1], 'Expected bool2 to remain false for contact 2');
        $this->assertEquals(true, $result2[2], 'Failed to update bool3 from false to true for contact 2');

        $this->assertEquals(true, $result3[0], 'Expected bool1 to remain true for contact 3');
        $this->assertEquals(false, $result3[1], 'Failed to update bool2 from true to false for contact 3');
        $this->assertEquals(true, $result3[2], 'Expected bool3 to remain true for contact 3');
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

    private function createGroup(
        string $name,
    ): Group {
        $group = new Group();
        $group->setName($name);
        $this->em->persist($group);

        return $group;
    }

    private function addGroupContactScore(
        Lead $lead,
        Group $group,
        int $score,
    ): void {
        $groupContactScore = new GroupContactScore();
        $groupContactScore->setContact($lead);
        $groupContactScore->setGroup($group);
        $groupContactScore->setScore($score);
        $lead->addGroupScore($groupContactScore);
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

    /**
     * Creates campaign with point condition and point change action.
     *
     * Campaign diagram:
     * -------------------
     * -   Has 1 point?  -
     * -------------------
     *         | Yes
     * -------------------
     * -   Add 1 point   -
     * -------------------
     *
     * @param array<int, int> $contactIds
     * @param int|null        $pointGroup optional use of point group in campaign
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    private function createCampaignWithPointEvents(array $contactIds, int $pointGroup = null): Campaign
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
        $event1->setName('Check if the contact has points');
        $event1->setType('lead.points');
        $event1->setEventType('condition');
        $event1->setTriggerMode('immediate');
        $event1->setOrder(1);
        $event1->setProperties(
            [
                'canvasSettings'             => [
                    'droppedX' => '696',
                    'droppedY' => '155',
                ],
                'name'                       => 'Lead points',
                'triggerMode'                => 'immediate',
                'triggerDate'                => null,
                'triggerInterval'            => '1',
                'triggerIntervalUnit'        => 'd',
                'triggerHour'                => '',
                'triggerRestrictedStartHour' => '',
                'triggerRestrictedStopHour'  => '',
                'order'                      => 1,
                'anchor'                     => 'leadsource',
                'properties'                 => [
                    'operator'                   => 'gte',
                    'score'                      => 1,
                    'group'                      => $pointGroup,
                ],
                'type'                       => 'lead.points',
                'eventType'                  => 'condition',
                'anchorEventType'            => 'source',
                'campaignId'                 => 'mautic_28ac4b8a4758b8597e8d189fa97b245996e338bb',
                '_token'                     => 'HgysZwvH_n0uAp47CcAcsGddRnRk65t-3crOnuLx28Y',
                'buttons'                    => ['save' => ''],
                'operator'                   => 'gte',
                'score'                      => 1,
                'group'                      => $pointGroup,
            ]
        );

        $this->em->persist($event1);
        $this->em->flush();

        $event2 = new Event();
        $event2->setCampaign($campaign);
        $event2->setName('Change contact\'s points');
        $event2->setType('lead.changepoints');
        $event2->setEventType('action');
        $event2->setTriggerMode('immediate');
        $event2->setDecisionPath('yes');
        $event2->setOrder(2);
        $event2->setParent($event1);
        $event2->setProperties(
            [
                'canvasSettings'             => [
                    'droppedX' => '696',
                    'droppedY' => '300',
                ],
                'name'                       => '',
                'triggerMode'                => 'immediate',
                'triggerDate'                => null,
                'triggerInterval'            => '1',
                'triggerIntervalUnit'        => 'd',
                'triggerHour'                => '',
                'triggerRestrictedStartHour' => '',
                'triggerRestrictedStopHour'  => '',
                'anchor'                     => 'yes',
                'properties'                 => [
                    'points'                     => 1,
                    'group'                      => $pointGroup,
                ],
                'type'                       => 'lead.changepoints',
                'eventType'                  => 'action',
                'anchorEventType'            => 'condition',
                'campaignId'                 => 'mautic_28ac4b8a4758b8597e8d189fa97b245996e338bb',
                '_token'                     => 'HgysZwvH_n0uAp47CcAcsGddRnRk65t-3crOnuLx28Y',
                'buttons'                    => ['save' => ''],
                'points'                     => 1,
                'group'                      => $pointGroup,
            ]
        );

        $this->em->persist($event2);
        $this->em->flush();

        $campaign->setCanvasSettings(
            [
                'nodes'       => [
                    [
                        'id'        => $event1->getId(),
                        'positionX' => '696',
                        'positionY' => '150',
                    ],
                    [
                        'id'        => $event2->getId(),
                        'positionX' => '696',
                        'positionY' => '300',
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
                        'sourceId' => $event1->getId(),
                        'targetId' => $event2->getId(),
                        'anchors'  => [
                            'source' => 'yes',
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

    public function testManipulatorSetOnCampaignTriggerAction(): void
    {
        $campaignEvent = new Event();
        $campaign      = new Campaign();
        $campaignEvent->setCampaign($campaign);
        $lead = new Lead();
        $log  = new LeadEventLog();
        $log->setEvent($campaignEvent);

        $args = [
            'lead'            => $lead,
            'event'           => $campaignEvent,
            'eventDetails'    => null,
            'systemTriggered' => false,
            'eventSettings'   => [],
        ];

        $event           = new CampaignExecutionEvent($args, false, $log);
        $eventDispatcher = static::getContainer()->get('event_dispatcher');
        $eventDispatcher->dispatch($event, 'mautic.lead.on_campaign_trigger_action');

        $leadManipulator = $lead->getManipulator();
        Assert::assertInstanceOf(LeadManipulator::class, $leadManipulator);
        Assert::assertSame('campaign', $leadManipulator->getBundleName());
        Assert::assertSame('trigger-action', $leadManipulator->getObjectName());
    }

    private function createContact(string $email): Lead
    {
        $lead = new Lead();
        $lead->setEmail($email);

        $this->em->persist($lead);
        $this->em->flush();

        return $lead;
    }

    private function addContactToCampaign(Campaign $campaign, Lead $lead): void
    {
        $campaignLead = new CampaignLead();
        $campaignLead->setCampaign($campaign);
        $campaignLead->setLead($lead);
        $campaignLead->setDateAdded(new \DateTime());
        $this->em->persist($campaignLead);
        $campaign->addLead($lead->getId(), $campaignLead);
    }
}
