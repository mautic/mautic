<?php

namespace Mautic\CampaignBundle\Tests\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\CampaignRepository;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\EventRepository;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Entity\LeadEventLogRepository;
use Mautic\CampaignBundle\Event\CampaignEvent;
use Mautic\CampaignBundle\Event\ExecutedEvent;
use Mautic\CampaignBundle\Event\FailedEvent;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\AbstractEventAccessor;
use Mautic\CampaignBundle\EventListener\CampaignEventSubscriber;
use Mautic\CampaignBundle\Executioner\Helper\NotificationHelper;
use Mautic\LeadBundle\Entity\Lead;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CampaignEventSubscriberTest extends TestCase
{
    private CampaignEventSubscriber $fixture;

    /**
     * @var EventRepository|MockObject
     */
    private $eventRepo;

    /**
     * @var NotificationHelper|MockObject
     */
    private $notificationHelper;

    /**
     * @var CampaignRepository|MockObject
     */
    private $campaignRepository;

    /**
     * @var MockObject|LeadEventLogRepository
     */
    private $leadEventLogRepositoryMock;

    public function setUp(): void
    {
        $this->eventRepo                  = $this->createMock(EventRepository::class);
        $this->notificationHelper         = $this->createMock(NotificationHelper::class);
        $this->campaignRepository         = $this->createMock(CampaignRepository::class);
        $this->leadEventLogRepositoryMock = $this->createMock(LeadEventLogRepository::class);
        $this->fixture                    = new CampaignEventSubscriber(
            $this->eventRepo,
            $this->notificationHelper,
            $this->campaignRepository,
            $this->leadEventLogRepositoryMock
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

        $this->notificationHelper->expects($this->once())
            ->method('notifyOfFailure')
            ->with($mockLead, $mockEvent);

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

        $this->notificationHelper->expects($this->once())
            ->method('notifyOfFailure')
            ->with($mockLead, $mockEvent);

        $this->notificationHelper->expects($this->once())
            ->method('notifyOfUnpublish')
            ->with($mockEvent);

        $failedEvent = new FailedEvent($this->createMock(AbstractEventAccessor::class), $mockEventLog);

        $this->campaignRepository->expects($this->once())
            ->method('saveEntity')
            ->with($mockCampaign);

        $mockCampaign->expects($this->once())
            ->method('setIsPublished')
            ->with(false);

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
}
