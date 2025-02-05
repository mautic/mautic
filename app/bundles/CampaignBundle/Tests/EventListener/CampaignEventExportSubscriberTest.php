<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\EventListener;

use Mautic\CampaignBundle\EventListener\CampaignEventExportSubscriber;
use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CoreBundle\Event\EntityExportEvent;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;

final class CampaignEventExportSubscriberTest extends TestCase
{
    private CampaignEventExportSubscriber $subscriber;
    private MockObject $campaignModel;

    protected function setUp(): void
    {
        $this->campaignModel = $this->createMock(CampaignModel::class);
        $this->subscriber = new CampaignEventExportSubscriber($this->campaignModel);
    }

    public function testSubscribedEvents(): void
    {
        $events = CampaignEventExportSubscriber::getSubscribedEvents();

        $this->assertArrayHasKey(EntityExportEvent::EXPORT_CAMPAIGN_EVENT, $events);
        $this->assertSame(['onCampaignEventExport', 0], $events[EntityExportEvent::EXPORT_CAMPAIGN_EVENT]);
    }

    public function testOnCampaignEventExport(): void
    {
        $campaignId = 123;

        // Mock Campaign entity
        $campaign = $this->createMock(Campaign::class);
        $campaign->method('getId')->willReturn($campaignId);

        // Mock Event entity
        $campaignEvent = $this->createMock(Event::class);
        $campaignEvent->method('getId')->willReturn(10);
        $campaignEvent->method('getName')->willReturn('Test Event');
        $campaignEvent->method('getDescription')->willReturn('A test campaign event');
        $campaignEvent->method('getType')->willReturn('email');
        $campaignEvent->method('getEventType')->willReturn('send');
        $campaignEvent->method('getOrder')->willReturn(1);
        $campaignEvent->method('getProperties')->willReturn(['key' => 'value']);
        $campaignEvent->method('getTriggerInterval')->willReturn(5);
        $campaignEvent->method('getTriggerIntervalUnit')->willReturn('days');
        $campaignEvent->method('getTriggerMode')->willReturn('immediate');
        $campaignEvent->method('getTriggerDate')->willReturn(new \DateTime('2024-01-01T00:00:00Z'));
        $campaignEvent->method('getChannel')->willReturn('email');
        $campaignEvent->method('getChannelId')->willReturn(99);
        $campaignEvent->method('getParent')->willReturn(null); // No parent event

        $campaign->method('getEvents')->willReturn([$campaignEvent]);

        $this->campaignModel
            ->method('getEntity')
            ->with($campaignId)
            ->willReturn($campaign);

        $event = new EntityExportEvent(EntityExportEvent::EXPORT_CAMPAIGN_EVENT, $campaignId);

        // Execute the event listener
        $this->subscriber->onCampaignEventExport($event);

        // Verify entities are added correctly
        $entities = $event->getEntities();
        $this->assertArrayHasKey(EntityExportEvent::EXPORT_CAMPAIGN_EVENT, $entities);
        $this->assertCount(1, $entities[EntityExportEvent::EXPORT_CAMPAIGN_EVENT]);
        $this->assertSame(10, $entities[EntityExportEvent::EXPORT_CAMPAIGN_EVENT][0]['id']);
        $this->assertSame('Test Event', $entities[EntityExportEvent::EXPORT_CAMPAIGN_EVENT][0]['name']);
        $this->assertSame('email', $entities[EntityExportEvent::EXPORT_CAMPAIGN_EVENT][0]['channel']);

        // Verify dependencies are added correctly
        $dependencies = $event->getDependencies();
        $this->assertArrayHasKey(EntityExportEvent::EXPORT_CAMPAIGN_EVENT, $dependencies);
        $this->assertCount(1, $dependencies[EntityExportEvent::EXPORT_CAMPAIGN_EVENT]);
        $this->assertSame($campaignId, $dependencies[EntityExportEvent::EXPORT_CAMPAIGN_EVENT][0]['campaignId']);
        $this->assertSame(10, $dependencies[EntityExportEvent::EXPORT_CAMPAIGN_EVENT][0]['eventId']);
    }
}
