<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Functional\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\EventListener\CampaignEventImportExportSubscriber;
use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CampaignBundle\Model\EventModel;
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
    private MockObject&EventModel $eventModel;
    private MockObject&EntityManagerInterface $entityManager;
    private MockObject&EventDispatcherInterface $dispatcher;
    private MockObject&AuditLogModel $auditLogModel;
    private MockObject&IpLookupHelper $ipLookupHelper;
    private EventDispatcher $eventDispatcher;

    protected function setUp(): void
    {
        $this->campaignModel   = $this->createMock(CampaignModel::class);
        $this->eventModel      = $this->createMock(EventModel::class);
        $this->entityManager   = $this->createMock(EntityManagerInterface::class);
        $this->dispatcher      = $this->createMock(EventDispatcherInterface::class);
        $this->auditLogModel   = $this->createMock(AuditLogModel::class);
        $this->ipLookupHelper  = $this->createMock(IpLookupHelper::class);

        $eventRepository = $this->createMock(\Doctrine\Persistence\ObjectRepository::class);
        $eventRepository->method('findOneBy')->willReturn(null);

        $this->entityManager->method('getRepository')->with(Event::class)->willReturn($eventRepository);

        $this->subscriber = new CampaignEventImportExportSubscriber(
            $this->campaignModel,
            $this->entityManager,
            $this->auditLogModel,
            $this->ipLookupHelper,
            $this->dispatcher,
            $this->eventModel
        );

        $this->eventDispatcher = new EventDispatcher();
        $this->eventDispatcher->addSubscriber($this->subscriber);
    }

    private function mockEvent(int $id = 1): MockObject
    {
        $event = $this->createMock(Event::class);
        $event->method('getId')->willReturn($id);
        $event->method('getName')->willReturn('Event '.$id);
        $event->method('getDescription')->willReturn('Description '.$id);
        $event->method('getType')->willReturn('type');
        $event->method('getEventType')->willReturn('type');
        $event->method('getOrder')->willReturn(1);
        $event->method('getProperties')->willReturn([]);
        $event->method('getTriggerInterval')->willReturn(10);
        $event->method('getTriggerIntervalUnit')->willReturn('minutes');
        $event->method('getTriggerMode')->willReturn('immediate');
        $event->method('getTriggerDate')->willReturn(null);
        $event->method('getChannel')->willReturn('email');
        $event->method('getChannelId')->willReturn(99);
        $event->method('getParent')->willReturn(null);
        $event->method('getUuid')->willReturn('uuid-'.$id);

        $campaign = $this->createMock(Campaign::class);
        $campaign->method('getId')->willReturn(123);
        $event->method('getCampaign')->willReturn($campaign);

        return $event;
    }

    public function testCampaignEventExport(): void
    {
        $event    = $this->mockEvent();
        $campaign = $this->createMock(Campaign::class);
        $campaign->method('getId')->willReturn(123);
        $campaign->method('getEvents')->willReturn([$event]);

        $this->campaignModel->method('getEntity')->with(123)->willReturn($campaign);

        $exportEvent = new EntityExportEvent(Event::ENTITY_NAME, 123);
        $this->eventDispatcher->dispatch($exportEvent);

        $entities = $exportEvent->getEntities();
        $this->assertArrayHasKey(Event::ENTITY_NAME, $entities);
        $this->assertNotEmpty($entities[Event::ENTITY_NAME]);
        $firstEntity = array_values($entities[Event::ENTITY_NAME])[0];
        $this->assertSame(1, $firstEntity['id']);
    }

    public function testCampaignEventImport(): void
    {
        $data = [[
            'id'                    => 1,
            'campaign_id'           => 123,
            'name'                  => 'Imported',
            'description'           => 'Imported Description',
            'type'                  => 'type',
            'event_type'            => 'type',
            'event_order'           => 1,
            'properties'            => [],
            'trigger_interval'      => 5,
            'trigger_interval_unit' => 'hours',
            'trigger_mode'          => 'delayed',
            'triggerDate'           => null,
            'channel'               => 'sms',
            'channel_id'            => 101,
            'parent_id'             => null,
            'uuid'                  => 'uuid-1',
        ]];

        $campaign = $this->createMock(Campaign::class);
        $campaign->method('getId')->willReturn(123);
        $this->campaignModel->method('getEntity')->willReturn($campaign);

        $this->eventModel->expects($this->once())->method('saveEntity')->with($this->callback(fn ($event) => $event instanceof Event && 'Imported' === $event->getName()));
        $this->entityManager->expects($this->once())->method('flush');

        $importEvent = new EntityImportEvent(Event::ENTITY_NAME, $data, 1);
        $this->subscriber->onImport($importEvent);
    }

    public function testUpdateParentEvents(): void
    {
        $child = $this->createMock(Event::class);
        $child->method('getId')->willReturn(2);
        $parent = $this->createMock(Event::class);
        $parent->method('getId')->willReturn(1);

        $campaign = $this->createMock(Campaign::class);
        $campaign->method('getId')->willReturn(123);
        $this->campaignModel->method('getEntity')->with(123)->willReturn($campaign);

        $this->entityManager->method('getRepository')->willReturnSelf();
        $this->entityManager->method('find')->willReturnMap([
            [1, null, $parent],
            [2, null, $child],
        ]);

        $this->entityManager->expects($this->once())->method('flush');

        $importEvent = new EntityImportEvent(Event::ENTITY_NAME, [[
            'id'         => 2,
            'parent_id'  => 1,
            'campaign_id'=> 123, // ✅ Add campaign_id to match subscriber expectations
        ]], 1);

        $importEvent->addEntityIdMap(1, 1);
        $importEvent->addEntityIdMap(2, 2);

        $this->subscriber->onImport($importEvent);
    }
}
