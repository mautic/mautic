<?php

namespace Mautic\CampaignBundle\Tests\EventListener;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\EventRepository;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Event\CampaignEvent;
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

    public function setUp(): void
    {
        $this->eventRepo          = $this->createMock(EventRepository::class);
        $this->notificationHelper = $this->createMock(NotificationHelper::class);
        $this->fixture            = new CampaignEventSubscriber($this->eventRepo, $this->notificationHelper);
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
        $mockLead     = $this->createMock(Lead::class);

        $mockEvent = $this->createMock(Event::class);

        $mockEventLog = $this->createMock(LeadEventLog::class);
        $mockEventLog->expects($this->once())
            ->method('getEvent')
            ->willReturn($mockEvent);

        $mockEventLog->expects($this->once())
            ->method('getLead')
            ->willReturn($mockLead);

        $this->notificationHelper->expects($this->once())
            ->method('notifyOfFailure')
            ->with($mockLead, $mockEvent);

        $failedEvent = new FailedEvent($this->createMock(AbstractEventAccessor::class), $mockEventLog);

        $this->fixture->onEventFailed($failedEvent);
    }
}
