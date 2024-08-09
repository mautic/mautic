<?php

namespace Mautic\LeadBundle\Tests\EventListener;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\Lead as CampaignLead;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;
use PHPUnit\Framework\Assert;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\ApplicationTester;
use Symfony\Component\HttpFoundation\Response;

class Issue9488Test extends MauticMysqlTestCase
{
    private LeadRepository $contactRepository;

    /**
     * @var array<int, array<string, string|int>>
     */
    private array $contacts = [
        [
            'email'     => 'contact1@email.com',
            'firstname' => 'Isaac',
            'lastname'  => 'Asimov',
        ],
        [
            'email'            => 'contact2@email.com',
            'firstname'        => 'Robert A.',
            'lastname'         => 'Heinlein',
            'points'           => 0,
            'preferred_locale' => 'af',
        ],
        [
            'email'     => 'contact3@email.com',
            'firstname' => 'Arthur C.',
            'lastname'  => 'Clarke',
            'points'    => 1,
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        /** @var LeadRepository $leadRepository */
        $leadRepository          = $this->em->getRepository(Lead::class);
        $this->contactRepository = $leadRepository;
    }

    public function testUsesLocale(): void
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

        Assert::assertSame(1, $contactA->getPoints());
        Assert::assertSame(2, $contactB->getPoints());
        Assert::assertSame(2, $contactC->getPoints());
    }

    /**
     * @return int[]
     */
    private function createContacts(): array
    {
        $this->client->request('POST', '/api/contacts/batch/new', $this->contacts);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true, 512, JSON_THROW_ON_ERROR);

        Assert::assertEquals(Response::HTTP_CREATED, $clientResponse->getStatusCode(), $clientResponse->getContent());
        Assert::assertEquals(Response::HTTP_CREATED, $response['statusCodes'][0], $clientResponse->getContent());
        Assert::assertEquals(Response::HTTP_CREATED, $response['statusCodes'][1], $clientResponse->getContent());
        Assert::assertEquals(Response::HTTP_CREATED, $response['statusCodes'][2], $clientResponse->getContent());

        return [
            $response['contacts'][0]['id'],
            $response['contacts'][1]['id'],
            $response['contacts'][2]['id'],
        ];
    }

    /**
     * @param array<int, int> $contactIds
     */
    private function createCampaign(array $contactIds): Campaign
    {
        $campaign = new Campaign();
        $campaign->setName('Test Locale Decision');

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

        $condition = new Event();
        $condition->setCampaign($campaign);
        $condition->setName('Check preferred locale');
        $condition->setType('lead.field_value');
        $condition->setEventType(Event::TYPE_CONDITION);
        $condition->setTriggerMode(Event::TRIGGER_MODE_IMMEDIATE);
        $condition->setProperties(
            [
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
                    'field'    => 'preferred_locale',
                    'operator' => '=',
                    'value'    => 'af',
                ],
                'type'                       => 'lead.field_value',
                'eventType'                  => 'condition',
                'anchorEventType'            => 'source',
                'campaignId'                 => $campaign->getId(),
                '_token'                     => 'HgysZwvH_n0uAp47CcAcsGddRnRk65t-3crOnuLx28Y',
                'buttons'                    => ['save' => ''],
                'field'                      => 'preferred_locale',
                'operator'                   => '=',
                'value'                      => 'af',
            ]
        );

        $this->em->persist($condition);
        $this->em->flush();

        $yesEvent = new Event();
        $yesEvent->setCampaign($campaign);
        $yesEvent->setName('Add 2 if locale');
        $yesEvent->setType('lead.changepoints');
        $yesEvent->setEventType(Event::TYPE_ACTION);
        $yesEvent->setTriggerMode(Event::TRIGGER_MODE_IMMEDIATE);
        $yesEvent->setDecisionPath(Event::PATH_ACTION);
        $yesEvent->setProperties(
            [
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
                    'points' => '2',
                ],
                'type'                       => 'lead.changepoints',
                'eventType'                  => 'action',
                'anchorEventType'            => 'condition',
                'campaignId'                 => $campaign->getId(),
                '_token'                     => 'HgysZwvH_n0uAp47CcAcsGddRnRk65t-3crOnuLx28Y',
                'buttons'                    => ['save' => ''],
                'points'                     => '2',
            ]
        );
        $yesEvent->setParent($condition);

        $this->em->persist($yesEvent);
        $this->em->flush();

        $noEvent = new Event();
        $noEvent->setCampaign($campaign);
        $noEvent->setName('Add 1 if not locale');
        $noEvent->setType('lead.changepoints');
        $noEvent->setEventType(Event::TYPE_ACTION);
        $noEvent->setTriggerMode(Event::TRIGGER_MODE_IMMEDIATE);
        $noEvent->setDecisionPath(Event::PATH_INACTION);
        $noEvent->setProperties(
            [
                'name'                       => '',
                'triggerMode'                => 'immediate',
                'triggerDate'                => null,
                'triggerInterval'            => '1',
                'triggerIntervalUnit'        => 'd',
                'triggerHour'                => '',
                'triggerRestrictedStartHour' => '',
                'triggerRestrictedStopHour'  => '',
                'anchor'                     => 'no',
                'properties'                 => [
                    'points' => '1',
                ],
                'type'                       => 'lead.changepoints',
                'eventType'                  => 'action',
                'anchorEventType'            => 'condition',
                'campaignId'                 => $campaign->getId(),
                '_token'                     => 'HgysZwvH_n0uAp47CcAcsGddRnRk65t-3crOnuLx28Y',
                'buttons'                    => ['save' => ''],
                'points'                     => '1',
            ]
        );
        $noEvent->setParent($condition);

        $this->em->persist($noEvent);
        $this->em->flush();

        $campaign->setCanvasSettings(
            [
                'nodes' => [
                    [
                        'id'        => $condition->getId(),
                        'positionX' => '848',
                        'positionY' => '139',
                    ],
                    [
                        'id'        => $noEvent->getId(),
                        'positionX' => '380',
                        'positionY' => '244',
                    ],
                    [
                        'id'        => $yesEvent->getId(),
                        'positionX' => '948',
                        'positionY' => '244',
                    ],
                    [
                        'id'        => 'lists',
                        'positionX' => '860',
                        'positionY' => '50',
                    ],
                ],
                'connections' => [
                    [
                        'sourceId' => 'lists',
                        'targetId' => $condition->getId(),
                        'anchors'  => [
                            [
                                'endpoint' => 'leadsource',
                                'eventId'  => 'lists',
                            ],
                            [
                                'endpoint' => 'top',
                                'eventId'  => $condition->getId(),
                            ],
                        ],
                    ],
                    [
                        'sourceId' => $condition->getId(),
                        'targetId' => $yesEvent->getId(),
                        'anchors'  => [
                            [
                                'endpoint' => 'yes',
                                'eventId'  => $condition->getId(),
                            ],
                            [
                                'endpoint' => 'top',
                                'eventId'  => $yesEvent->getId(),
                            ],
                        ],
                    ],
                    [
                        'sourceId' => $condition->getId(),
                        'targetId' => $noEvent->getId(),
                        'anchors'  => [
                            [
                                'endpoint' => 'no',
                                'eventId'  => $condition->getId(),
                            ],
                            [
                                'endpoint' => 'top',
                                'eventId'  => $noEvent->getId(),
                            ],
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
