<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Functional\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\EventListener\CampaignEventImportExportSubscriber;
use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CoreBundle\Event\EntityExportEvent;
use Mautic\CoreBundle\Event\EntityImportEvent;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class CampaignEventImportExportSubscriberTest extends TestCase
{
    private CampaignEventImportExportSubscriber $subscriber;
    private MockObject&CampaignModel $campaignModel;
    private MockObject&EntityManagerInterface $entityManager;
    private MockObject&EventDispatcherInterface $dispatcher;
    private EventDispatcher $eventDispatcher;
    private MockObject&AuditLogModel $auditLogModel;
    private MockObject&IpLookupHelper $ipLookupHelper;

    protected function setUp(): void
    {
        $this->campaignModel = $this->createMock(CampaignModel::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->dispatcher    = new EventDispatcher();

        $this->subscriber = new CampaignEventImportExportSubscriber(
            $this->campaignModel,
            $this->entityManager,
            $this->auditLogModel,
            $this->ipLookupHelper,
            $this->dispatcher,
        );

        $this->eventDispatcher = new EventDispatcher();
        $this->eventDispatcher->addSubscriber($this->subscriber);
    }

    private function createMockCampaignEvent(): MockObject
    {
        $campaignEvent = $this->createMock(Event::class);
        $campaignEvent->method('getId')->willReturn(1);
        $campaignEvent->method('getName')->willReturn('Test Event');
        $campaignEvent->method('getDescription')->willReturn('Test Description');
        $campaignEvent->method('getType')->willReturn('test_type');
        $campaignEvent->method('getEventType')->willReturn('test_event_type');
        $campaignEvent->method('getOrder')->willReturn(1);
        $campaignEvent->method('getProperties')->willReturn([]);
        $campaignEvent->method('getTriggerInterval')->willReturn(10);
        $campaignEvent->method('getTriggerIntervalUnit')->willReturn('minutes');
        $campaignEvent->method('getTriggerMode')->willReturn('immediate');
        $campaignEvent->method('getTriggerDate')->willReturn(null);
        $campaignEvent->method('getChannel')->willReturn('email');
        $campaignEvent->method('getChannelId')->willReturn(99);
        $campaignEvent->method('getParent')->willReturn(null);

        return $campaignEvent;
    }

    public function testCampaignEventExport(): void
    {
        $mockEvent    = $this->createMockCampaignEvent();
        $mockCampaign = $this->createMock(Campaign::class);
        $mockCampaign->method('getId')->willReturn(1);
        $mockCampaign->method('getEvents')->willReturn([$mockEvent]);

        $this->campaignModel->method('getEntity')->with(1)->willReturn($mockCampaign);

        $event = new EntityExportEvent(Event::ENTITY_NAME, 1);
        $this->eventDispatcher->dispatch($event);

        $exportedData = $event->getEntities();

        $this->assertArrayHasKey(Event::ENTITY_NAME, $exportedData, 'Exported data must contain campaign event entity.');
        $this->assertNotEmpty($exportedData[Event::ENTITY_NAME], 'Exported campaign event data should not be empty.');

        $exportedEvent = $exportedData[Event::ENTITY_NAME][0];
        $this->assertSame(1, $exportedEvent['id'], 'Campaign event ID mismatch.');
        $this->assertSame('Test Event', $exportedEvent['name'], 'Campaign event name mismatch.');
        $this->assertSame('test_type', $exportedEvent['type'], 'Campaign event type mismatch.');
    }

    public function testCampaignEventImport(): void
    {
        $eventData = [
            [
                'id'                   => 1,
                'campaign_id'          => 1,
                'name'                 => 'Imported Event',
                'description'          => 'Imported Description',
                'type'                 => 'imported_type',
                'event_type'           => 'imported_event_type',
                'event_order'          => 2,
                'properties'           => [],
                'trigger_interval'     => 5,
                'trigger_interval_unit'=> 'hours',
                'trigger_mode'         => 'delayed',
                'triggerDate'          => null,
                'channel'              => 'sms',
                'channel_id'           => 101,
                'parent_id'            => null,
            ],
        ];

        $mockCampaign = $this->createMock(Campaign::class);
        $mockCampaign->method('getId')->willReturn(1);
        $this->campaignModel->method('getEntity')->with(1)->willReturn($mockCampaign);

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (Event $event) {
                return 'Imported Event' === $event->getName()
                       && 'Imported Description' === $event->getDescription()
                       && 'imported_type' === $event->getType()
                       && 'imported_event_type' === $event->getEventType()
                       && 2 === $event->getOrder()
                       && 5 === $event->getTriggerInterval()
                       && 'hours' === $event->getTriggerIntervalUnit()
                       && 'delayed' === $event->getTriggerMode()
                       && 'sms' === $event->getChannel()
                       && 101 === $event->getChannelId();
            }));

        $this->entityManager->expects($this->once())->method('flush');

        $event = new EntityImportEvent(Event::ENTITY_NAME, $eventData, 1);
        $this->subscriber->onImport($event);
    }

    public function testUpdateParentEvents(): void
    {
        $eventData = [
            [
                'id'        => 2,
                'parent_id' => 1,
            ],
        ];

        $mockEventParent = $this->createMock(Event::class);
        $mockEventParent->method('getId')->willReturn(1);

        $mockEventChild = $this->createMock(Event::class);
        $mockEventChild->method('getId')->willReturn(2);

        $this->entityManager->method('getRepository')->willReturnSelf();
        $this->entityManager->method('find')->willReturnMap([
            [1, null, $mockEventParent],
            [2, null, $mockEventChild],
        ]);

        $this->entityManager->expects($this->once())->method('flush');

        $event = new EntityImportEvent(Event::ENTITY_NAME, $eventData, 1);
        $event->addEntityIdMap(1, 1);
        $event->addEntityIdMap(2, 2);

        $this->subscriber->onImport($event);
    }
}
