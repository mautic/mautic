<?php

declare(strict_types=1);

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Tests\EventListener;

use DateTime;
use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\CoreBundle\Tests\CommonMocks;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadEventLog;
use Mautic\LeadBundle\Entity\LeadEventLogRepository;
use Mautic\LeadBundle\Event\LeadEvent;
use Mautic\LeadBundle\Event\LeadTimelineEvent;
use Mautic\LeadBundle\EventListener\LeadSubscriber;
use Mautic\LeadBundle\Helper\LeadChangeEventDispatcher;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Templating\Helper\DncReasonHelper;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;

class LeadSubscriberTest extends CommonMocks
{
    /**
     * @var IpLookupHelper|MockObject
     */
    private $ipLookupHelper;

    /**
     * @var AuditLogModel|MockObject
     */
    private $auditLogModel;

    /**
     * @var LeadChangeEventDispatcher|MockObject
     */
    private $leadEventDispatcher;

    /**
     * @var DncReasonHelper|MockObject
     */
    private $dncReasonHelper;

    /**
     * @var EntityManager|MockObject
     */
    private $entityManager;

    /**
     * @var TranslatorInterface|MockObject
     */
    private $translator;

    /**
     * @var RouterInterface|MockObject
     */
    private $router;

    protected function setUp(): void
    {
        $this->ipLookupHelper      = $this->createMock(IpLookupHelper::class);
        $this->auditLogModel       = $this->createMock(AuditLogModel::class);
        $this->leadEventDispatcher = $this->createMock(LeadChangeEventDispatcher::class);
        $this->dncReasonHelper     = $this->createMock(DncReasonHelper::class);
        $this->entityManager       = $this->createMock(EntityManager::class);
        $this->translator          = $this->createMock(TranslatorInterface::class);
        $this->router              = $this->createMock(RouterInterface::class);
    }

    public function testOnLeadPostSaveWillNotProcessTheSameLeadTwice()
    {
        $lead = new Lead();

        $lead->setId(54);

        $changes = [
            'title' => [
                '0' => 'sdf',
                '1' => 'Mr.',
            ],
            'fields' => [
                'firstname' => [
                    '0' => 'Test',
                    '1' => 'John',
                ],
                'lastname' => [
                    '0' => 'test',
                    '1' => 'Doe',
                ],
                'email' => [
                    '0' => 'zrosa91@gmail.com',
                    '1' => 'john@gmail.com',
                ],
                'mobile' => [
                    '0' => '345345',
                    '1' => '555555555',
                ],
            ],
            'dateModified' => [
                '0' => '2017-08-21T15:50:57+00:00',
                '1' => '2017-08-22T08:04:31+00:00',
            ],
            'dateLastActive' => [
                '0' => '2017-08-21T15:50:57+00:00',
                '1' => '2017-08-22T08:04:31+00:00',
            ],
        ];

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
            $this->router
        );

        $leadEvent = $this->createMock(LeadEvent::class);

        $leadEvent->expects($this->exactly(2))
            ->method('getLead')
            ->will($this->returnValue($lead));

        $leadEvent->expects($this->exactly(2))
            ->method('getChanges')
            ->will($this->returnValue($changes));

        $subscriber->onLeadPostSave($leadEvent);
        $subscriber->onLeadPostSave($leadEvent);
    }

    /**
     * Make sure that an timeline entry is created for a lead
     * that was created through the API.
     */
    public function testAddTimelineApiCreatedEntries()
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
            'date_added' => new DateTime(),
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
            'icon'       => 'fa-cogs',
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
            true
        );

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber($subscriber);

        $dispatcher->dispatch(LeadEvents::TIMELINE_ON_GENERATE, $leadEvent);

        $this->assertSame([$timelineEvent], $leadEvent->getEvents());
    }
}
