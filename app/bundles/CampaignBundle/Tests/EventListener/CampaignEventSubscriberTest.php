<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\EventRepository;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Entity\LeadEventLogRepository;
use Mautic\CampaignBundle\Event\CampaignPreSaveEvent;
use Mautic\CampaignBundle\Event\ExecutedEvent;
use Mautic\CampaignBundle\Event\FailedEvent;
use Mautic\CampaignBundle\Event\NotifyOfFailureEvent;
use Mautic\CampaignBundle\Event\NotifyOfUnpublishEvent;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\AbstractEventAccessor;
use Mautic\CampaignBundle\EventListener\CampaignEventSubscriber;
use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Twig\Helper\DateHelper;
use Mautic\LeadBundle\Entity\Lead;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AllowMockObjectsWithoutExpectations]
final class CampaignEventSubscriberTest extends TestCase
{
    private CampaignEventSubscriber $fixture;

    /**
     * @var MockObject&EventRepository
     */
    private MockObject $eventRepo;

    /**
     * @var MockObject&CampaignModel
     */
    private MockObject $campaignModelMock;

    /**
     * @var MockObject&LeadEventLogRepository
     */
    private MockObject $leadEventLogRepositoryMock;

    /**
     * @var MockObject&EventDispatcherInterface
     */
    private MockObject $eventDispatcherMock;

    protected function setUp(): void
    {
        $this->eventRepo                  = $this->createMock(EventRepository::class);
        $this->campaignModelMock          = $this->createMock(CampaignModel::class);
        $this->leadEventLogRepositoryMock = $this->createMock(LeadEventLogRepository::class);
        $this->eventDispatcherMock        = $this->createMock(EventDispatcherInterface::class);
        $dateHelper                       = new DateHelper(
            'F j, Y g:i a T',
            'D, M d',
            'F j, Y',
            'g:i a',
            $this->createStub(TranslatorInterface::class),
            $this->createStub(CoreParametersHelper::class)
        );
        $this->fixture                    = new CampaignEventSubscriber(
            $this->eventRepo,
            $this->campaignModelMock,
            $this->leadEventLogRepositoryMock,
            $this->eventDispatcherMock,
            $dateHelper
        );
    }

    public function testEventFailedCountsGetResetOnCampaignPublish(): void
    {
        $campaign = new Campaign();
        // Ensure the campaign is unpublished
        $campaign->setIsPublished(false);
        // Go from unpublished to published.
        $campaign->setIsPublished(true);

        $this->eventRepo->expects($this->once())
            ->method('resetFailedCountsForEventsInCampaign')
            ->with($campaign);

        $this->fixture->onCampaignPreSave(new CampaignPreSaveEvent($campaign));
    }

    public function testEventFailedCountsDoesNotGetResetOnCampaignUnPublish(): void
    {
        $campaign = new Campaign();
        // Ensure the campaign is published
        $campaign->setIsPublished(true);
        // Go from published to unpublished.
        $campaign->setIsPublished(false);

        $this->eventRepo->expects($this->never())
            ->method('resetFailedCountsForEventsInCampaign');

        $this->fixture->onCampaignPreSave(new CampaignPreSaveEvent($campaign));
    }

    public function testEventFailedCountsDoesNotGetResetWhenPublishedStateIsNotChanged(): void
    {
        $campaign = new Campaign();

        $this->eventRepo->expects($this->never())
            ->method('resetFailedCountsForEventsInCampaign');

        $this->fixture->onCampaignPreSave(new CampaignPreSaveEvent($campaign));
    }

    public function testNewPublishedCampaignGetsPublishUpWithoutSeconds(): void
    {
        $campaign = new Campaign();
        $campaign->setIsPublished(true);

        $this->fixture->onCampaignPreSave(new CampaignPreSaveEvent($campaign));

        $this->assertInstanceOf(\DateTimeInterface::class, $campaign->getPublishUp());
        $this->assertSame('00', $campaign->getPublishUp()->format('s'));
    }

    public function testFailedEventGeneratesANotification(): void
    {
        $this->leadEventLogRepositoryMock->expects($this->once())
            ->method('isLastFailed')
            ->with(42, 42)
            ->willReturn(false);

        $mockLead = new Lead();
        $mockLead->setId(42);

        $mockCampaign  = new Campaign();
        $campaignLeads = new \ReflectionProperty($mockCampaign, 'leads');
        $campaignLeads->setValue($mockCampaign, new ArrayCollection(range(0, 99)));

        $mockEvent = new Event();
        $mockEvent->setCampaign($mockCampaign);
        $eventId = new \ReflectionProperty($mockEvent, 'id');
        $eventId->setValue($mockEvent, 42);

        $mockEventLog = new LeadEventLog();
        $mockEventLog->setEvent($mockEvent);
        $mockEventLog->setLead($mockLead);

        $this->eventRepo->expects($this->once())
            ->method('getFailedCountLeadEvent')
            ->withAnyParameters()
            ->willReturn(105);

        // Set failed count to 5% of getLeads()->count()
        $this->eventRepo->expects($this->once())
            ->method('incrementFailedCount')
            ->with($mockEvent)
            ->willReturn(5);

        $this->eventDispatcherMock->expects($this->once())
            ->method('hasListeners')
            ->with(NotifyOfFailureEvent::class)
            ->willReturn(true);

        $this->eventDispatcherMock->expects($this->once())
            ->method('dispatch')
            ->willReturn(new NotifyOfFailureEvent($mockLead, $mockEvent));

        $failedEvent = new FailedEvent($this->createStub(AbstractEventAccessor::class), $mockEventLog);

        $this->fixture->onEventFailed($failedEvent);
    }

    public function testFailedCountOverDisableCampaignThresholdDisablesTheCampaign(): void
    {
        $this->leadEventLogRepositoryMock->expects($this->once())
            ->method('isLastFailed')
            ->with(42, 42)
            ->willReturn(false);

        $mockLead = new Lead();
        $mockLead->setId(42);

        $mockCampaign = new Campaign();
        $mockCampaign->setIsPublished(true);
        $campaignLeads = new \ReflectionProperty($mockCampaign, 'leads');
        $campaignLeads->setValue($mockCampaign, new ArrayCollection(range(0, 99)));

        $mockEvent = new Event();
        $mockEvent->setCampaign($mockCampaign);
        $eventId = new \ReflectionProperty($mockEvent, 'id');
        $eventId->setValue($mockEvent, 42);

        $mockEventLog = new LeadEventLog();
        $mockEventLog->setEvent($mockEvent);
        $mockEventLog->setLead($mockLead);

        $this->eventRepo->expects($this->once())
            ->method('getFailedCountLeadEvent')
            ->withAnyParameters()
            ->willReturn(200);

        // Set failed count to 35% of getLeads()->count()
        $this->eventRepo->expects($this->once())
            ->method('incrementFailedCount')
            ->with($mockEvent)
            ->willReturn(35);

        $this->eventDispatcherMock->expects($this->exactly(2))
            ->method('hasListeners')
            ->willReturnMap([
                [NotifyOfFailureEvent::class, true],
                [NotifyOfUnpublishEvent::class, true],
            ]);

        $this->eventDispatcherMock->expects($this->exactly(2))
            ->method('dispatch')
            ->willReturnOnConsecutiveCalls(
                new NotifyOfFailureEvent($mockLead, $mockEvent),
                new NotifyOfUnpublishEvent($mockEvent)
            );

        $this->campaignModelMock->expects($this->once())
            ->method('transactionalCampaignUnPublish')
            ->with($mockCampaign);

        $failedEvent = new FailedEvent($this->createStub(AbstractEventAccessor::class), $mockEventLog);

        $this->fixture->onEventFailed($failedEvent);
    }

    public function testOnEventExecutedDecreaseTheCounter(): void
    {
        $lead = new Lead();
        $lead->setId(42);

        $eventMock = new Event();
        $eventMock->setCampaign(new Campaign());
        $eventId = new \ReflectionProperty($eventMock, 'id');
        $eventId->setValue($eventMock, 42);

        $mockEventLog = new LeadEventLog();
        $mockEventLog->setEvent($eventMock);
        $mockEventLog->setLead($lead);

        $this->leadEventLogRepositoryMock->expects($this->once())
            ->method('isLastFailed')
            ->with(42, 42)
            ->willReturn(true);

        $executedEvent = new ExecutedEvent($this->createStub(AbstractEventAccessor::class), $mockEventLog);

        $this->eventRepo->expects($this->once())
            ->method('getFailedCountLeadEvent')
            ->withAnyParameters()
            ->willReturn(101);

        $this->eventRepo->expects($this->once())
            ->method('decreaseFailedCount')
            ->with($eventMock);

        $this->fixture->onEventExecuted($executedEvent);
    }

    public function testOnEventExecutedForDeletedContacts(): void
    {
        $lead            = new Lead();
        $lead->deletedId = 10;

        $eventMock = new Event();
        $eventMock->setCampaign(new Campaign());
        $eventId = new \ReflectionProperty($eventMock, 'id');
        $eventId->setValue($eventMock, 1);

        $mockEventLog = new LeadEventLog();
        $mockEventLog->setEvent($eventMock);
        $mockEventLog->setLead($lead);

        $this->leadEventLogRepositoryMock->expects($this->once())
            ->method('isLastFailed')
            ->with($lead->deletedId, 1)
            ->willReturn(true);

        $executedEvent = new ExecutedEvent($this->createStub(AbstractEventAccessor::class), $mockEventLog);

        $this->eventRepo->expects($this->once())
            ->method('getFailedCountLeadEvent')
            ->with($lead->deletedId, 1)
            ->willReturn(101);

        $this->eventRepo->expects($this->once())
            ->method('decreaseFailedCount')
            ->with($eventMock);

        $this->fixture->onEventExecuted($executedEvent);
    }

    public function testOnFailedEventGeneratesOneUnPublishNotificationAndEmail(): void
    {
        // Set up entities; campaign is already unpublished
        $campaignMock = new Campaign();
        $campaignMock->setIsPublished(false);

        $eventMock = new Event();
        $eventMock->setCampaign($campaignMock);
        $eventId = new \ReflectionProperty($eventMock, 'id');
        $eventId->setValue($eventMock, 1);

        $leadMock = new Lead();
        $leadMock->setId(1);

        $leadEventLogMock = new LeadEventLog();
        $leadEventLogMock->setEvent($eventMock);
        $leadEventLogMock->setLead($leadMock);

        $this->eventRepo->expects($this->once())->method('getFailedCountLeadEvent')
            ->with(1, 1)->willReturn(101);
        $this->leadEventLogRepositoryMock->expects($this->once())->method('isLastFailed')
            ->with(1, 1)->willReturn(false);
        $this->eventRepo->expects($this->once())->method('incrementFailedCount')
            ->with($eventMock)->willReturn(35);

        // Set up leads collection
        $totalLeads    = array_fill(0, 100, new Lead());
        $leadsProperty = new \ReflectionProperty($campaignMock, 'leads');
        $leadsProperty->setValue($campaignMock, new ArrayCollection($totalLeads));

        // Expect failure notification to be dispatched
        $this->eventDispatcherMock->expects($this->once())
            ->method('hasListeners')
            ->with(NotifyOfFailureEvent::class)
            ->willReturn(true);

        $this->eventDispatcherMock->expects($this->once())
            ->method('dispatch')
            ->willReturn(new NotifyOfFailureEvent($leadMock, $eventMock));

        // Unpublish notification should not be dispatched because campaign is already unpublished
        $this->campaignModelMock->expects($this->never())->method('transactionalCampaignUnPublish');

        // Execute the test
        $failedEvent = new FailedEvent($this->createStub(AbstractEventAccessor::class), $leadEventLogMock);
        $this->fixture->onEventFailed($failedEvent);
    }
}
