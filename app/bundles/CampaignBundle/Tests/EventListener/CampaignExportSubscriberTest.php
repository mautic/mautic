<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\EventListener;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\EventListener\CampaignExportSubscriber;
use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CoreBundle\Event\EntityExportEvent;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class CampaignExportSubscriberTest extends TestCase
{
    private CampaignExportSubscriber $subscriber;
    private MockObject $campaignModel;
    private MockObject $dispatcher;

    protected function setUp(): void
    {
        $this->campaignModel = $this->createMock(CampaignModel::class);
        $this->dispatcher    = $this->createMock(EventDispatcherInterface::class);

        $this->subscriber = new CampaignExportSubscriber($this->campaignModel, $this->dispatcher);
    }

    public function testSubscribedEvents(): void
    {
        $events = CampaignExportSubscriber::getSubscribedEvents();

        $this->assertArrayHasKey(EntityExportEvent::EXPORT_CAMPAIGN, $events);
        $this->assertSame(['onCampaignExport', 0], $events[EntityExportEvent::EXPORT_CAMPAIGN]);
    }

    public function testOnCampaignExport(): void
    {
        $campaignId   = 123;
        $campaignData = [
            'id'              => $campaignId,
            'name'            => 'Test Campaign',
            'description'     => 'A test campaign',
            'is_published'    => true,
            'canvas_settings' => [],
        ];

        // Mock the Campaign entity properly
        $campaign = $this->createMock(Campaign::class);
        $campaign->method('getId')->willReturn($campaignData['id']);
        $campaign->method('getName')->willReturn($campaignData['name']);
        $campaign->method('getDescription')->willReturn($campaignData['description']);
        $campaign->method('getIsPublished')->willReturn($campaignData['is_published']);
        $campaign->method('getCanvasSettings')->willReturn($campaignData['canvas_settings']);

        $this->campaignModel
            ->method('getEntity')
            ->with($campaignId)
            ->willReturn($campaign);

        $event = new EntityExportEvent(EntityExportEvent::EXPORT_CAMPAIGN, $campaignId);

        // Mock sub-events for dependent entities
        $subEventMock = $this->createMock(EntityExportEvent::class);
        $subEventMock->method('getEntities')->willReturn([]);
        $subEventMock->method('getDependencies')->willReturn([]);

        // Expect dispatching of dependent entity events
        $this->dispatcher
            ->expects($this->exactly(3))
            ->method('dispatch')
            ->willReturn($subEventMock);

        // Execute the event listener
        $this->subscriber->onCampaignExport($event);

        // Check if campaign data was added
        $entities = $event->getEntities();
        $this->assertArrayHasKey(EntityExportEvent::EXPORT_CAMPAIGN, $entities);
        $this->assertSame([$campaignData], $entities[EntityExportEvent::EXPORT_CAMPAIGN]);

        // Check if dependencies were added correctly
        $dependencies = $event->getDependencies();
        $this->assertArrayHasKey('dependencies', $event->getEntities());

        // Updated assertion to match the actual structure
        $this->assertSame([$dependencies], $event->getEntities()['dependencies']);
    }
}
