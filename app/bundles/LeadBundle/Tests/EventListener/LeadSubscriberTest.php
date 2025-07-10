<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\EventListener;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\CoreBundle\Tests\CommonMocks;
use Mautic\LeadBundle\DataObject\LeadManipulator;
use Mautic\LeadBundle\Entity\CompanyLeadRepository;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadEventLog;
use Mautic\LeadBundle\Entity\LeadEventLogRepository;
use Mautic\LeadBundle\Event\LeadEvent;
use Mautic\LeadBundle\Event\LeadTimelineEvent;
use Mautic\LeadBundle\EventListener\LeadSubscriber;
use Mautic\LeadBundle\Helper\LeadChangeEventDispatcher;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Twig\Helper\DncReasonHelper;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class LeadSubscriberTest extends CommonMocks
{
    /**
     * @var IpLookupHelper&MockObject
     */
    private MockObject $ipLookupHelper;

    /**
     * @var AuditLogModel&MockObject
     */
    private MockObject $auditLogModel;

    /**
     * @var LeadChangeEventDispatcher&MockObject
     */
    private MockObject $leadEventDispatcher;

    private DncReasonHelper $dncReasonHelper;

    /**
     * @var EntityManager&MockObject
     */
    private MockObject $entityManager;

    /**
     * @var TranslatorInterface&MockObject
     */
    private MockObject $translator;

    /**
     * @var RouterInterface&MockObject
     */
    private MockObject $router;

    /**
     * @var ModelFactory<object>&MockObject
     */
    private MockObject $modelFacotry;

    /**
     * @var CoreParametersHelper
     */
    private $coreParametersHelper;

    /**
     * @var CompanyLeadRepository
     */
    private $companyLeadRepository;

    protected function setUp(): void
    {
        $this->ipLookupHelper        = $this->createMock(IpLookupHelper::class);
        $this->auditLogModel         = $this->createMock(AuditLogModel::class);
        $this->leadEventDispatcher   = $this->createMock(LeadChangeEventDispatcher::class);
        $this->dncReasonHelper       = new DncReasonHelper($this->createMock(TranslatorInterface::class));
        $this->entityManager         = $this->createMock(EntityManager::class);
        $this->translator            = $this->createMock(TranslatorInterface::class);
        $this->router                = $this->createMock(RouterInterface::class);
        $this->modelFacotry          = $this->createMock(ModelFactory::class);
        $this->coreParametersHelper  = $this->createMock(CoreParametersHelper::class);
        $this->companyLeadRepository = $this->createMock(CompanyLeadRepository::class);
    }

    public function testOnLeadPostSaveWillNotProcessTheSameLeadTwice(): void
    {
        $lead = new Lead();

        $lead->setId(54);

        $changes = [
            'title'          => [
                '0' => 'sdf',
                '1' => 'Mr.',
            ],
            'fields'         => [
                'firstname' => [
                    '0' => 'Test',
                    '1' => 'John',
                ],
                'lastname'  => [
                    '0' => 'test',
                    '1' => 'Doe',
                ],
                'email'     => [
                    '0' => 'zrosa91@gmail.com',
                    '1' => 'john@gmail.com',
                ],
                'mobile'    => [
                    '0' => '345345',
                    '1' => '555555555',
                ],
            ],
            'dateModified'   => [
                '0' => '2017-08-21T15:50:57+00:00',
                '1' => '2017-08-22T08:04:31+00:00',
            ],
            'dateLastActive' => [
                '0' => '2017-08-21T15:50:57+00:00',
                '1' => '2017-08-22T08:04:31+00:00',
            ],
        ];

        $lead->setChanges($changes);

        // This method will be called exactly once
        // even though the onLeadPostSave was called twice for the same lead
        $this->auditLogModel->expects($this->once())
            ->method('writeToLog');

        $subscriber = new LeadSubscriber(
            $this->ipLookupHelper,
            $this->auditLogModel,
            $this->leadEventDispatcher,
            $this->dncReasonHelper,
            $this->entityManager,
            $this->translator,
            $this->router,
            $this->modelFacotry,
            $this->coreParametersHelper,
            $this->companyLeadRepository
        );

        $subscriber->onLeadPostSave(new LeadEvent($lead));

        Assert::assertEmpty($lead->getChanges()); // changes were reset after they were processed.
    }

    /**
     * Make sure that an timeline entry is created for a lead
     * that was created through the API.
     */
    public function testAddTimelineApiCreatedEntries(): void
    {
        $eventTypeKey  = 'lead.apiadded';
        $eventTypeName = 'Added through API';

        $this->translator->expects($this->once())
            ->method('trans')
            ->will($this->returnValue($eventTypeName));

        $lead = new Lead();

        $leadEventLog = [
            'id'         => '1',
            'lead_id'    => '1',
            'user_id'    => null,
            'user_name'  => null,
            'bundle'     => 'lead',
            'object'     => 'api-single',
            'action'     => 'identified_contact',
            'object_id'  => null,
            'date_added' => new \DateTime(),
            'properties' => '{"object_description":"Awesome User"}',
        ];

        $logs = [
            'total'   => 1,
            'results' => [
                $leadEventLog,
            ],
        ];

        $timelineEvent = [
            'event'      => $eventTypeKey,
            'eventId'    => $eventTypeKey.$leadEventLog['id'],
            'eventType'  => $eventTypeName,
            'eventLabel' => $eventTypeName,
            'timestamp'  => $leadEventLog['date_added'],
            'icon'       => 'ri-list-settings-line',
            'extra'      => $leadEventLog,
            'contactId'  => $leadEventLog['lead_id'],
        ];

        $leadEvent = new LeadTimelineEvent($lead);
        $repo      = $this->createMock(LeadEventLogRepository::class);

        $repo->expects($this->exactly(2))
            ->method('getEvents')
            ->withConsecutive(
                [$lead, 'lead', 'api-single', null, $leadEvent->getQueryOptions()],
                [$lead, 'lead', 'api-batch', null, $leadEvent->getQueryOptions()]
            )
            ->willReturnOnConsecutiveCalls($logs, ['total' => 0, 'results' => []]);

        $this->entityManager->method('getRepository')
            ->with(LeadEventLog::class)
            ->willReturn($repo);

        $subscriber = new LeadSubscriber(
            $this->ipLookupHelper,
            $this->auditLogModel,
            $this->leadEventDispatcher,
            $this->dncReasonHelper,
            $this->entityManager,
            $this->translator,
            $this->router,
            $this->modelFacotry,
            $this->coreParametersHelper,
            $this->companyLeadRepository,
            true
        );

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber($subscriber);

        $dispatcher->dispatch($leadEvent, LeadEvents::TIMELINE_ON_GENERATE);

        $this->assertSame([$timelineEvent], $leadEvent->getEvents());
    }

    public function testOnLeadPostSaveWillNotProcessTheSameContactMultipleTimesBetweenContacts(): void
    {
        $lead = new Lead();
        $lead->setId(54);
        $lead->addUpdatedField('title', 'Mr');
        $lead->addUpdatedField('firstname', 'John');
        $lead->addUpdatedField('lastname', 'Doe');

        $lead2 = new Lead();
        $lead2->setId(58);
        $lead2->addUpdatedField('title', 'Mrs');
        $lead2->addUpdatedField('firstname', 'Jane');
        $lead2->addUpdatedField('lastname', 'Doe');

        // Imitate a changed $lead2 but can't clone because it resets stuff in the __clone magic method
        // namely just need same ID
        $lead3 = new Lead();
        $lead3->setId(58);
        $lead3->addUpdatedField('lastname', 'Somebody');

        // This method will be called exactly once per set of changes
        $this->auditLogModel->expects($this->exactly(3))
            ->method('writeToLog')
            ->withConsecutive(
                [
                    [
                        'bundle'    => 'lead',
                        'object'    => 'lead',
                        'objectId'  => $lead->getId(),
                        'action'    => 'update',
                        'details'   => [
                            'title'     => [null, 'Mr'],
                            'fields'    => [
                                'title'     => [null, 'Mr'],
                                'firstname' => [null, 'John'],
                                'lastname'  => [null, 'Doe'],
                            ],
                            'firstname' => [null, 'John'],
                            'lastname'  => [null, 'Doe'],
                        ],
                        'ipAddress' => null,
                    ],
                ],
                [
                    [
                        'bundle'    => 'lead',
                        'object'    => 'lead',
                        'objectId'  => $lead2->getId(),
                        'action'    => 'update',
                        'details'   => [
                            'title'     => [null, 'Mrs'],
                            'fields'    => [
                                'title'     => [null, 'Mrs'],
                                'firstname' => [null, 'Jane'],
                                'lastname'  => [null, 'Doe'],
                            ],
                            'firstname' => [null, 'Jane'],
                            'lastname'  => [null, 'Doe'],
                        ],
                        'ipAddress' => null,
                    ],
                ],
                [
                    [
                        'bundle'    => 'lead',
                        'object'    => 'lead',
                        'objectId'  => $lead3->getId(),
                        'action'    => 'update',
                        'details'   => [
                            'fields'   => [
                                'lastname' => [null, 'Somebody'],
                            ],
                            'lastname' => [null, 'Somebody'],
                        ],
                        'ipAddress' => null,
                    ],
                ]
            );

        $subscriber = new LeadSubscriber(
            $this->ipLookupHelper,
            $this->auditLogModel,
            $this->leadEventDispatcher,
            $this->dncReasonHelper,
            $this->entityManager,
            $this->translator,
            $this->router,
            $this->modelFacotry,
            $this->coreParametersHelper,
            $this->companyLeadRepository,
            true
        );

        $subscriber->onLeadPostSave(new LeadEvent($lead));
        $subscriber->onLeadPostSave(new LeadEvent($lead));
        $subscriber->onLeadPostSave(new LeadEvent($lead2));
        $subscriber->onLeadPostSave(new LeadEvent($lead2));
        $subscriber->onLeadPostSave(new LeadEvent($lead3));
        $subscriber->onLeadPostSave(new LeadEvent($lead3));
    }

    public function testManipulatorLogged(): void
    {
        $lead = new Lead();
        $lead->setId(54);

        $lead->setManipulator(
            new LeadManipulator('campaign', 'trigger-action', 1, 'Event Name (Campaign Name)')
        );

        $lead->addUpdatedField('title', 'Mr');
        $lead->addUpdatedField('firstname', 'John');
        $lead->addUpdatedField('lastname', 'Test');

        // This method will be called exactly once
        // even though the onLeadPostSave was called twice for the same lead
        $this->auditLogModel->expects($this->once())
            ->method('writeToLog')
            ->with(
                [
                    'bundle'    => 'lead',
                    'object'    => 'lead',
                    'objectId'  => $lead->getId(),
                    'action'    => 'update',
                    'details'   => [
                        'title'           => [null, 'Mr'],
                        'fields'          => [
                            'title'     => [null, 'Mr'],
                            'firstname' => [null, 'John'],
                            'lastname'  => [null, 'Test'],
                        ],
                        'firstname'       => [null, 'John'],
                        'lastname'        => [null, 'Test'],
                        'manipulated_by'  => 'Event Name (Campaign Name)',
                        'manipulator_key' => 'campaign:trigger-action:1',
                    ],
                    'ipAddress' => null,
                ]
            );

        $subscriber = new LeadSubscriber(
            $this->ipLookupHelper,
            $this->auditLogModel,
            $this->leadEventDispatcher,
            $this->dncReasonHelper,
            $this->entityManager,
            $this->translator,
            $this->router,
            $this->modelFacotry,
            $this->coreParametersHelper,
            $this->companyLeadRepository,
            true
        );

        $subscriber->onLeadPostSave(new LeadEvent($lead));
    }
}
