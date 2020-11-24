<?php

namespace Mautic\LeadBundle\Tests\Entity;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Mautic\LeadBundle\Entity\ListLeadRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ListLeadRepositoryTest extends TestCase
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

    public function setUp()
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

        $this
            ->entityManager
            ->expects($this->at(0))
            ->method('createQueryBuilder')
            ->willReturn($this->queryBuilder);

        $this
            ->queryBuilder
            ->expects($this->at(0))
            ->method('select')
            ->with('ll')
            ->willReturn($this->queryBuilder);

        $this
            ->queryBuilder
            ->expects($this->at(1))
            ->method('from')
            ->willReturn($this->queryBuilder);

        $this
            ->queryBuilder
            ->expects($this->at(2))
            ->method('select')
            ->with('count(ll.list) as count')
            ->willReturn($this->queryBuilder);

        $this
            ->queryBuilder
            ->expects($this->at(3))
            ->method('where')
            ->with('ll.list = :segmentId')
            ->willReturn($this->queryBuilder);

        $this
            ->queryBuilder
            ->expects($this->at(4))
            ->method('setParameter')
            ->with('segmentId', $segmentId)
            ->willReturn($this->queryBuilder);

        $this
            ->queryBuilder
            ->expects($this->at(5))
            ->method('andWhere')
            ->with('ll.manuallyRemoved=:manuallyRemoved')
            ->willReturn($this->queryBuilder);

        $this
            ->queryBuilder
            ->expects($this->at(6))
            ->method('setParameter')
            ->with('manuallyRemoved', 0)
            ->willReturn($this->queryBuilder);

        $this
            ->queryBuilder
            ->expects($this->at(7))
            ->method('getQuery')
            ->willReturn($this->query);

        $this
            ->query
            ->expects($this->at(0))
            ->method('getSingleScalarResult')
            ->willReturn($count);

        $result = $this->listLeadRepository->getContactsCountBySegment($segmentId, $filters);
        $this->assertTrue(is_int($result));
        $this->assertSame($count, $result);
    }
}
