<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Entity;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\EventRepository;
use PHPUnit\Framework\TestCase;

final class EventRepositoryTest extends TestCase
{
    public function testDecreaseFailedCount(): void
    {
        $emMock           = $this->createMock(EntityManager::class);
        $connMock         = $this->createMock(Connection::class);
        $queryBuilderMock = $this->createMock(QueryBuilder::class);
        $expressionMock   = $this->createMock(Expr::class);
        $emMock->expects($this->at(0))
            ->method('getConnection')
            ->willReturn($connMock);
        $connMock->expects($this->at(0))
            ->method('createQueryBuilder')
            ->willReturn($queryBuilderMock);
        $queryBuilderMock->expects($this->at(2))
            ->method('expr')
            ->willReturn($expressionMock);
        $expressionMock->expects($this->at(0))
            ->method('eq')
            ->with('id', ':id')
            ->willReturn('id = :id');
        $queryBuilderMock->expects($this->at(4))
            ->method('expr')
            ->willReturn($expressionMock);
        $expressionMock->expects($this->at(1))
            ->method('gt')
            ->with('failed_count', 0)
            ->willReturn('failed_count > 0');
        $queryBuilderMock->expects($this->at(0))
            ->method('update')
            ->with(MAUTIC_TABLE_PREFIX.'campaign_events')
            ->willReturn($queryBuilderMock);
        $queryBuilderMock->expects($this->at(1))
            ->method('set')
            ->with('failed_count', 'failed_count - 1')
            ->willReturn($queryBuilderMock);
        $queryBuilderMock->expects($this->at(3))
            ->method('where')
            ->with('id = :id')
            ->willReturn($queryBuilderMock);
        $queryBuilderMock->expects($this->at(5))
            ->method('andWhere')
            ->with('failed_count > 0')
            ->willReturn($queryBuilderMock);
        $queryBuilderMock->expects($this->at(6))
            ->method('setParameter')
            ->with('id', 42)
            ->willReturn($queryBuilderMock);
        $queryBuilderMock->expects($this->at(7))
            ->method('execute');

        $class           = new ClassMetadata(Event::class);
        $eventRepository = new EventRepository($emMock, $class);
        $eventMock       = $this->createMock(Event::class);
        $eventMock->expects($this->at(0))
            ->method('getId')
            ->willReturn(42);
        $eventRepository->decreaseFailedCount($eventMock);
    }
}
