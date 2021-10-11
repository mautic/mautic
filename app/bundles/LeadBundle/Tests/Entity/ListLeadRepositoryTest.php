<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Entity;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Mautic\LeadBundle\Entity\ListLeadRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ListLeadRepositoryTest extends TestCase
{
    /**
     * @var ListLeadRepository
     */
    private $listLeadRepository;

    /**
     * @var EntityManager|MockObject
     */
    private $entityManager;

    /**
     * @var QueryBuilder|MockObject
     */
    private $queryBuilder;

    /**
     * @var Query|MockObject
     */
    private $query;

    public function setUp(): void
    {
        $classMetadata            = $this->createMock(ClassMetadata::class);
        $this->entityManager      = $this->createMock(EntityManager::class);
        $this->listLeadRepository = new ListLeadRepository($this->entityManager, $classMetadata);
        $this->queryBuilder       = $this->createMock(QueryBuilder::class);
        $this->query              = $this->createMock(AbstractQuery::class);
    }

    public function testGetContactsCountBySegment(): void
    {
        $segmentId = 1;
        $count     = 100;
        $filters   = ['manually_removed' => 0];

        $this->entityManager->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($this->queryBuilder);

        $this->queryBuilder->expects($this->exactly(2))
            ->method('select')
            ->withConsecutive(['ll'], ['count(ll.list) as count'])
            ->willReturn($this->queryBuilder);

        $this->queryBuilder
            ->expects($this->once())
            ->method('from')
            ->willReturn($this->queryBuilder);

        $this->queryBuilder->expects($this->once())
            ->method('where')
            ->with('ll.list = :segmentId')
            ->willReturn($this->queryBuilder);

        $this->queryBuilder->expects($this->exactly(2))
            ->method('setParameter')
            ->withConsecutive(['segmentId', $segmentId], ['manuallyRemoved', 0])
            ->willReturn($this->queryBuilder);

        $this->queryBuilder->expects($this->once())
            ->method('andWhere')
            ->with('ll.manuallyRemoved=:manuallyRemoved')
            ->willReturn($this->queryBuilder);

        $this->queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($this->query);

        $this->query->expects($this->once())
            ->method('getSingleScalarResult')
            ->willReturn($count);

        $result = $this->listLeadRepository->getContactsCountBySegment($segmentId, $filters);
        $this->assertTrue(is_int($result));
        $this->assertSame($count, $result);
    }
}
