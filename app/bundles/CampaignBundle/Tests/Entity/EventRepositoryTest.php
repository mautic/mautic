<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Entity;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Expr;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CoreBundle\Test\Doctrine\RepositoryConfiguratorTrait;
use PHPUnit\Framework\TestCase;

final class EventRepositoryTest extends TestCase
{
    use RepositoryConfiguratorTrait;

    public function testDecreaseFailedCount(): void
    {
        $emMock           = $this->createMock(EntityManager::class);
        $connMock         = $this->createMock(Connection::class);
        $queryBuilderMock = $this->createMock(QueryBuilder::class);
        $expressionMock   = $this->createMock(Expr::class);

        $queryBuilderMock->expects($this->any())
            ->method('expr')
            ->willReturn($expressionMock);

        $expressionMock->expects($this->once())
            ->method('eq')
            ->with('id', ':id')
            ->willReturn('id = :id');

        $queryBuilderMock->expects($this->any())
            ->method('expr')
            ->willReturn($expressionMock);

        $expressionMock->expects($this->once())
            ->method('gt')
            ->with('failed_count', 0)
            ->willReturn('failed_count > 0');

        $queryBuilderMock->expects($this->once())
            ->method('update')
            ->with(MAUTIC_TABLE_PREFIX.'campaign_events')
            ->willReturn($queryBuilderMock);

        $queryBuilderMock->expects($this->once())
            ->method('set')
            ->with('failed_count', 'failed_count - 1')
            ->willReturn($queryBuilderMock);

        $queryBuilderMock->expects($this->once())
            ->method('where')
            ->with('id = :id')
            ->willReturn($queryBuilderMock);

        $queryBuilderMock->expects($this->once())
            ->method('andWhere')
            ->with('failed_count > 0')
            ->willReturn($queryBuilderMock);

        $queryBuilderMock->expects($this->once())
            ->method('setParameter')
            ->with('id', $this->equalTo(42))
            ->willReturn($queryBuilderMock);

        $connMock->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilderMock);

        $emMock->expects($this->once())
            ->method('getConnection')
            ->willReturn($connMock);

        $eventRepository = $this->configureRepository(Event::class, $emMock);
        $this->connection->method('createQueryBuilder')
            ->willReturnCallback(fn () => $queryBuilderMock);

        $eventMock       = $this->createMock(Event::class);
        $eventMock->method('getId')
            ->willReturn(42);

        $eventRepository->decreaseFailedCount($eventMock);
    }

    public function testSetEventsAsDeletedWithRedirect(): void
    {
        // Event with redirect
        $eventData = [
            [
                'id'              => 123,
                'redirectEvent'   => 456,
            ],
            // Event without redirect
            [
                'id'              => 789,
                'redirectEvent'   => null,
            ],
            // Already deleted event with redirect (will be skipped due to already being deleted)
            [
                'id'              => 101,
                'redirectEvent'   => 202,
            ],
            // Already deleted event without redirect (will be skipped)
            [
                'id'              => 303,
                'redirectEvent'   => null,
            ],
        ];

        $emMock            = $this->createMock(EntityManager::class);
        $connMock          = $this->createMock(Connection::class);
        $queryBuilderMock  = $this->createMock(QueryBuilder::class);
        $selectBuilderMock = $this->createMock(QueryBuilder::class);
        $statementMock     = $this->createMock(\Doctrine\DBAL\Statement::class);

        // We expect one call to getConnection - the connection is retrieved once and reused
        $emMock->expects($this->once())
            ->method('getConnection')
            ->willReturn($connMock);

        // First create the select query builder, then two update query builders (for non-deleted events)
        $connMock->expects($this->exactly(3)) // 1 for select, 2 for updates (only non-deleted events)
            ->method('createQueryBuilder')
            ->willReturnOnConsecutiveCalls($selectBuilderMock, $queryBuilderMock, $queryBuilderMock);

        // Prepare the select statement
        $selectBuilderMock->expects($this->once())
            ->method('select')
            ->with('id, deleted')
            ->willReturnSelf();

        $selectBuilderMock->expects($this->once())
            ->method('from')
            ->with(MAUTIC_TABLE_PREFIX.Event::TABLE_NAME)
            ->willReturnSelf();

        $selectExprMock = $this->createMock(Expr::class);
        $selectBuilderMock->expects($this->once())
            ->method('expr')
            ->willReturn($selectExprMock);

        $selectExprMock->expects($this->once())
            ->method('in')
            ->with('id', ':eventIds')
            ->willReturn('id IN (:eventIds)');

        $selectBuilderMock->expects($this->once())
            ->method('where')
            ->with('id IN (:eventIds)')
            ->willReturnSelf();

        $selectBuilderMock->expects($this->once())
            ->method('setParameter')
            ->with('eventIds', [123, 789, 101, 303], Connection::PARAM_INT_ARRAY)
            ->willReturnSelf();

        // Mock the execute and fetchAllAssociative
        $selectBuilderMock->expects($this->once())
            ->method('execute')
            ->willReturn($statementMock);

        $statementMock->expects($this->once())
            ->method('fetchAllAssociative')
            ->willReturn([
                ['id' => 123, 'deleted' => null],        // Not deleted
                ['id' => 789, 'deleted' => null],        // Not deleted
                ['id' => 101, 'deleted' => '2023-01-01'], // Already deleted
                ['id' => 303, 'deleted' => '2023-01-01'], // Already deleted
            ]);

        // Only updates for non-deleted events (123, 789)
        $queryBuilderMock->expects($this->exactly(2))
            ->method('update')
            ->with(MAUTIC_TABLE_PREFIX.Event::TABLE_NAME)
            ->willReturnSelf();

        // Set calls - 2 for deleted + 1 for redirect (only event 123 has redirect)
        $queryBuilderMock->expects($this->exactly(3))
            ->method('set')
            ->willReturnSelf();

        // Every update needs a where clause with expression
        $queryBuilderMock->expects($this->exactly(2))
            ->method('where')
            ->willReturnSelf();

        // Need expr calls for the where clauses
        $expressionMock = $this->createMock(Expr::class);
        $queryBuilderMock->expects($this->exactly(2))
            ->method('expr')
            ->willReturn($expressionMock);

        // Each call to expr should be followed by eq
        $expressionMock->expects($this->exactly(2))
            ->method('eq')
            ->with('id', ':eventId')
            ->willReturn('id = :eventId');

        // Parameters for the updates - 2 deleted timestamps, 2 event IDs, 1 redirect
        $queryBuilderMock->expects($this->exactly(5))
            ->method('setParameter')
            ->willReturnSelf();

        // Execute should be called for each update (2 events - only the non-deleted ones)
        $queryBuilderMock->expects($this->exactly(2))
            ->method('execute');

        $class           = new ClassMetadata(Event::class);
        $eventRepository = new EventRepository($emMock, $class);
        $eventRepository->setEventsAsDeletedWithRedirect($eventData);
    }
}
