<?php

namespace Mautic\CampaignBundle\Tests\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\EventRepository;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Entity\LeadEventLogRepository;
use Mautic\CampaignBundle\Event\CampaignEvent;
use Mautic\CampaignBundle\Event\ExecutedEvent;
use Mautic\CampaignBundle\Event\FailedEvent;
use Mautic\CampaignBundle\Event\NotifyOfFailureEvent;
use Mautic\CampaignBundle\Event\NotifyOfUnpublishEvent;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\AbstractEventAccessor;
use Mautic\CampaignBundle\EventListener\CampaignEventSubscriber;
use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\LeadBundle\Entity\Lead;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class CampaignEventSubscriberTest extends TestCase
{
    private CampaignEventSubscriber $fixture;

    private EventRepository|MockObject $eventRepo;

    private MockObject|CampaignModel $campaignModelMock;

    private MockObject|LeadEventLogRepository $leadEventLogRepositoryMock;

    private MockObject|EventDispatcherInterface $eventDispatcherMock;

    public function setUp(): void
    {
        $this->eventRepo                  = $this->createMock(EventRepository::class);
        $this->campaignModelMock          = $this->createMock(CampaignModel::class);
        $this->leadEventLogRepositoryMock = $this->createMock(LeadEventLogRepository::class);
        $this->eventDispatcherMock        = $this->createMock(EventDispatcherInterface::class);
        $this->fixture                    = new CampaignEventSubscriber(
            $this->eventRepo,
            $this->campaignModelMock,
            $this->leadEventLogRepositoryMock,
            $this->eventDispatcherMock
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

        $this->fixture->onCampaignPreSave(new CampaignEvent($campaign));
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

        $this->fixture->onCampaignPreSave(new CampaignEvent($campaign));
    }

    public function testEventFailedCountsDoesNotGetResetWhenPublishedStateIsNotChanged(): void
    {
        $campaign = new Campaign();

        $this->eventRepo->expects($this->never())
            ->method('resetFailedCountsForEventsInCampaign');

        $this->fixture->onCampaignPreSave(new CampaignEvent($campaign));
    }

    public function testFailedEventGeneratesANotification(): void
    {
        $this->leadEventLogRepositoryMock->expects($this->once())
            ->method('isLastFailed')
            ->with(42, 42)
            ->willReturn(false);

        $mockLead     = $this->createMock(Lead::class);
        $mockLead->expects($this->any())
            ->method('getId')
            ->willReturn(42);
        $mockCampaign = $this->createMock(Campaign::class);
        $mockCampaign->expects($this->once())
            ->method('getLeads')
            ->willReturn(new ArrayCollection(range(0, 99)));

        $mockEvent = $this->createMock(Event::class);
        $mockEvent->expects($this->once())
            ->method('getCampaign')
            ->willReturn($mockCampaign);
        $mockEvent->expects($this->any())
            ->method('getId')
            ->willReturn(42);

        $mockEventLog = $this->createMock(LeadEventLog::class);
        $mockEventLog->expects($this->once())
            ->method('getEvent')
            ->willReturn($mockEvent);

        $mockEventLog->expects($this->any())
            ->method('getLead')
            ->willReturn($mockLead);

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
            ->with(CampaignEvents::ON_CAMPAIGN_FAILURE_NOTIFY)
            ->willReturn(true);

        $this->eventDispatcherMock->expects($this->once())
            ->method('dispatch')
            ->willReturn(new NotifyOfFailureEvent($mockLead, $mockEvent));

        $failedEvent = new FailedEvent($this->createMock(AbstractEventAccessor::class), $mockEventLog);

        $this->fixture->onEventFailed($failedEvent);
    }

    public function testFailedCountOverDisableCampaignThresholdDisablesTheCampaign(): void
    {
        $this->leadEventLogRepositoryMock->expects($this->once())
            ->method('isLastFailed')
            ->with(42, 42)
            ->willReturn(false);

        $mockLead     = $this->createMock(Lead::class);
        $mockLead->expects($this->any())
            ->method('getId')
            ->willReturn(42);
        $mockCampaign = $this->createMock(Campaign::class);
        $mockCampaign->expects($this->once())
            ->method('isPublished')
            ->willReturn(true);

        $mockCampaign->expects($this->once())
            ->method('getLeads')
            ->willReturn(new ArrayCollection(range(0, 99)));

        $mockEvent = $this->createMock(Event::class);
        $mockEvent->expects($this->once())
            ->method('getCampaign')
            ->willReturn($mockCampaign);
        $mockEvent->expects($this->any())
            ->method('getId')
            ->willReturn(42);

        $mockEventLog = $this->createMock(LeadEventLog::class);
        $mockEventLog->expects($this->once())
            ->method('getEvent')
            ->willReturn($mockEvent);

        $mockEventLog->expects($this->any())
            ->method('getLead')
            ->willReturn($mockLead);

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
                [CampaignEvents::ON_CAMPAIGN_FAILURE_NOTIFY, true],
                [CampaignEvents::ON_CAMPAIGN_UNPUBLISH_NOTIFY, true],
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

        $failedEvent = new FailedEvent($this->createMock(AbstractEventAccessor::class), $mockEventLog);

        $this->fixture->onEventFailed($failedEvent);
    }

    public function testOnEventExecutedDecreaseTheCounter(): void
    {
        $mockEventLog = $this->createMock(LeadEventLog::class);

        $lead = new Lead();
        $lead->setId(42);

        $eventMock = $this->createMock(Event::class);
        $eventMock->expects($this->any())
            ->method('getId')
            ->willReturn(42);

        $mockEventLog->expects($this->once())
            ->method('getEvent')
            ->willReturn($eventMock);

        $mockEventLog->expects($this->any())
            ->method('getLead')
            ->willReturn($lead);

        $this->leadEventLogRepositoryMock->expects($this->once())
            ->method('isLastFailed')
            ->with(42, 42)
            ->willReturn(true);

        $executedEvent = new ExecutedEvent($this->createMock(AbstractEventAccessor::class), $mockEventLog);

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
        $mockEventLog = $this->createMock(LeadEventLog::class);

        $lead            = new Lead();
        $lead->deletedId = 10;

        $eventMock = $this->createMock(Event::class);
        $eventMock->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $mockEventLog->expects($this->once())
            ->method('getEvent')
            ->willReturn($eventMock);

        $mockEventLog->expects($this->once())
            ->method('getLead')
            ->willReturn($lead);

        $this->leadEventLogRepositoryMock->expects($this->once())
            ->method('isLastFailed')
            ->with($lead->deletedId, 1)
            ->willReturn(true);

        $executedEvent = new ExecutedEvent($this->createMock(AbstractEventAccessor::class), $mockEventLog);

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
        // Set up mocks
        $leadEventLogMock = $this->createMock(LeadEventLog::class);
        $eventMock        = $this->createMock(Event::class);
        $leadEventLogMock->expects($this->once())->method('getEvent')->willReturn($eventMock);
        $leadMock = $this->createMock(Lead::class);
        $leadEventLogMock->expects($this->once())->method('getLead')->willReturn($leadMock);

        // Set up campaign mock with isPublished returning false to simulate campaign already unpublished
        $campaignMock = $this->createMock(Campaign::class);
        $campaignMock->expects($this->once())->method('isPublished')->willReturn(false);
        $eventMock->expects($this->any())->method('getCampaign')->willReturn($campaignMock);

        // Mock behavior for threshold calculations
        $leadMock->expects($this->any())->method('getId')->willReturn(1);
        $eventMock->expects($this->any())->method('getId')->willReturn(1);
        $this->eventRepo->expects($this->once())->method('getFailedCountLeadEvent')
            ->with(1, 1)->willReturn(101);
        $this->leadEventLogRepositoryMock->expects($this->once())->method('isLastFailed')
            ->with(1, 1)->willReturn(false);
        $this->eventRepo->expects($this->once())->method('incrementFailedCount')
            ->with($eventMock)->willReturn(35);

        // Set up leads collection
        $totalLeads = array_fill(0, 100, new Lead());
        $campaignMock->expects($this->once())->method('getLeads')->willReturn(new ArrayCollection($totalLeads));

        // Expect failure notification to be dispatched
        $this->eventDispatcherMock->expects($this->once())
            ->method('hasListeners')
            ->with(CampaignEvents::ON_CAMPAIGN_FAILURE_NOTIFY)
            ->willReturn(true);

        $this->eventDispatcherMock->expects($this->once())
            ->method('dispatch')
            ->willReturn(new NotifyOfFailureEvent($leadMock, $eventMock));

        // Unpublish notification should not be dispatched because campaign is already unpublished
        $this->campaignModelMock->expects($this->never())->method('transactionalCampaignUnPublish');

        // Execute the test
        $failedEvent = new FailedEvent($this->createMock(AbstractEventAccessor::class), $leadEventLogMock);
        $this->fixture->onEventFailed($failedEvent);
    }
}
