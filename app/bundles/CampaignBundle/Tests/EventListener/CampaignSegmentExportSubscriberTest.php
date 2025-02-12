<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\EventListener;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Result;
use Doctrine\ORM\EntityManagerInterface;
use Mautic\CampaignBundle\EventListener\CampaignSegmentExportSubscriber;
use Mautic\CoreBundle\Event\EntityExportEvent;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class CampaignSegmentExportSubscriberTest extends TestCase
{
    private CampaignSegmentExportSubscriber $subscriber;
    private MockObject $entityManager;
    private MockObject $connection;
    private MockObject $queryBuilder;
    private MockObject $result;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->connection    = $this->createMock(Connection::class);
        $this->queryBuilder  = $this->createMock(QueryBuilder::class);
        $this->result        = $this->createMock(Result::class); // Mock Result object

        // Mock EntityManager to return Connection
        $this->entityManager
            ->method('getConnection')
            ->willReturn($this->connection);

        // Mock Connection to return QueryBuilder
        $this->connection
            ->method('createQueryBuilder')
            ->willReturn($this->queryBuilder);

        $this->subscriber = new CampaignSegmentExportSubscriber($this->entityManager);
    }

    public function testSubscribedEvents(): void
    {
        $events = CampaignSegmentExportSubscriber::getSubscribedEvents();

        $this->assertArrayHasKey(EntityExportEvent::EXPORT_SEGMENT_EVENT, $events);
        $this->assertSame(['onCampaignSegmentExport', 0], $events[EntityExportEvent::EXPORT_SEGMENT_EVENT]);
    }

    public function testOnCampaignSegmentExport(): void
    {
        $campaignId = 123;

        $segmentResults = [
            [
                'leadlist_id'          => 10,
                'name'                 => 'Test Segment',
                'is_published'         => 1,
                'category_id'          => 2,
                'description'          => 'A test segment',
                'alias'                => 'test-segment',
                'public_name'          => 'Public Segment',
                'filters'              => '[]',
                'is_global'            => 0,
                'is_preference_center' => 1,
            ],
        ];

        // Configure the QueryBuilder mock
        $this->queryBuilder
            ->method('select')
            ->willReturnSelf();
        $this->queryBuilder
            ->method('from')
            ->willReturnSelf();
        $this->queryBuilder
            ->method('innerJoin')
            ->willReturnSelf();
        $this->queryBuilder
            ->method('where')
            ->willReturnSelf();
        $this->queryBuilder
            ->method('setParameter')
            ->willReturnSelf();
        $this->queryBuilder
            ->method('executeQuery')
            ->willReturn($this->result);

        // Mock fetchAllAssociative() on Result object
        $this->result
            ->method('fetchAllAssociative')
            ->willReturn($segmentResults);

        $event = new EntityExportEvent(EntityExportEvent::EXPORT_SEGMENT_EVENT, $campaignId);

        // Execute the event listener
        $this->subscriber->onCampaignSegmentExport($event);

        // Verify entities are added correctly
        $entities = $event->getEntities();
        $this->assertArrayHasKey(EntityExportEvent::EXPORT_SEGMENT_EVENT, $entities);
        $this->assertNotEmpty($entities[EntityExportEvent::EXPORT_SEGMENT_EVENT]);

        // Ensure the first index exists before accessing it
        $this->assertArrayHasKey(0, $entities[EntityExportEvent::EXPORT_SEGMENT_EVENT]);

        $this->assertSame($segmentResults[0]['leadlist_id'], $entities[EntityExportEvent::EXPORT_SEGMENT_EVENT][0]['id']);
        $this->assertSame($segmentResults[0]['name'], $entities[EntityExportEvent::EXPORT_SEGMENT_EVENT][0]['name']);

        // Verify dependencies are added correctly
        $dependencies = $event->getDependencies();
        $this->assertArrayHasKey(EntityExportEvent::EXPORT_SEGMENT_EVENT, $dependencies);
        $this->assertNotEmpty($dependencies[EntityExportEvent::EXPORT_SEGMENT_EVENT]);

        // Ensure the first index exists before accessing it
        $this->assertArrayHasKey(0, $dependencies[EntityExportEvent::EXPORT_SEGMENT_EVENT]);

        $this->assertSame($campaignId, $dependencies[EntityExportEvent::EXPORT_SEGMENT_EVENT][0]['campaignId']);
        $this->assertSame($segmentResults[0]['leadlist_id'], $dependencies[EntityExportEvent::EXPORT_SEGMENT_EVENT][0]['segmentId']);
    }
}
