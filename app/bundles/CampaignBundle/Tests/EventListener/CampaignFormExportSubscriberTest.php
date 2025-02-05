<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\EventListener;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Result;
use Doctrine\ORM\EntityManagerInterface;
use Mautic\CampaignBundle\EventListener\CampaignFormExportSubscriber;
use Mautic\CoreBundle\Event\EntityExportEvent;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class CampaignFormExportSubscriberTest extends TestCase
{
    private CampaignFormExportSubscriber $subscriber;
    private MockObject $entityManager;
    private MockObject $connection;
    private MockObject $queryBuilder;
    private MockObject $result;

    protected function setUp(): void
    {
        if (!defined('MAUTIC_TABLE_PREFIX')) {
            define('MAUTIC_TABLE_PREFIX', 'mautic_');
        }

        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->connection = $this->createMock(Connection::class);
        $this->queryBuilder = $this->createMock(QueryBuilder::class);
        $this->result = $this->createMock(Result::class);

        // Mock EntityManager to return Connection
        $this->entityManager
            ->method('getConnection')
            ->willReturn($this->connection);

        // Mock Connection to return QueryBuilder
        $this->connection
            ->method('createQueryBuilder')
            ->willReturn($this->queryBuilder);

        $this->subscriber = new CampaignFormExportSubscriber($this->entityManager);
    }

    public function testSubscribedEvents(): void
    {
        $events = CampaignFormExportSubscriber::getSubscribedEvents();

        $this->assertArrayHasKey(EntityExportEvent::EXPORT_CAMPAIGN_FORM, $events);
        $this->assertSame(['onCampaignFormExport', 0], $events[EntityExportEvent::EXPORT_CAMPAIGN_FORM]);
    }

    public function testOnCampaignFormExport(): void
    {
        $campaignId = 123;

        $formResults = [
            [
                'form_id'             => 10,
                'name'                => 'Test Form',
                'is_published'        => 1,
                'category_id'         => 2,
                'description'         => 'A test form',
                'alias'               => 'test-form',
                'lang'                => 'en',
                'cached_html'         => '<div>Form</div>',
                'post_action'         => 'submit',
                'template'            => 'default',
                'form_type'           => 'contact',
                'render_style'        => 'standard',
                'post_action_property'=> 'redirect',
                'form_attr'           => '{}',
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
            ->willReturn($formResults);

        $event = new EntityExportEvent(EntityExportEvent::EXPORT_CAMPAIGN_FORM, $campaignId);

        // Execute the event listener
        $this->subscriber->onCampaignFormExport($event);

        // Verify entities are added correctly
        $entities = $event->getEntities();
        $this->assertArrayHasKey(EntityExportEvent::EXPORT_CAMPAIGN_FORM, $entities);
        $this->assertCount(1, $entities[EntityExportEvent::EXPORT_CAMPAIGN_FORM]);
        $this->assertSame($formResults[0]['form_id'], $entities[EntityExportEvent::EXPORT_CAMPAIGN_FORM][0]['id']);
        $this->assertSame($formResults[0]['name'], $entities[EntityExportEvent::EXPORT_CAMPAIGN_FORM][0]['name']);

        // Verify dependencies are added correctly
        $dependencies = $event->getDependencies();
        $this->assertArrayHasKey(EntityExportEvent::EXPORT_CAMPAIGN_FORM, $dependencies);
        $this->assertCount(1, $dependencies[EntityExportEvent::EXPORT_CAMPAIGN_FORM]);
        $this->assertSame($campaignId, $dependencies[EntityExportEvent::EXPORT_CAMPAIGN_FORM][0]['campaignId']);
        $this->assertSame($formResults[0]['form_id'], $dependencies[EntityExportEvent::EXPORT_CAMPAIGN_FORM][0]['segmentId']);
    }
}
