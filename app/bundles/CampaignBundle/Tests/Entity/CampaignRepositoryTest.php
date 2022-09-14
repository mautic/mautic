<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Entity;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Mautic\CampaignBundle\Entity\CampaignRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CampaignRepositoryTest extends TestCase
{
    /**
     * @var EntityManager|MockObject
     */
    private $entityManager;

    /**
     * @var ClassMetadata|MockObject
     */
    private $classMetadata;

    /**
     * @var Connection|MockObject
     */
    private $connection;

    /**
     * @var CampaignRepository
     */
    private $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entityManager = $this->createMock(EntityManager::class);
        $this->classMetadata = $this->createMock(ClassMetadata::class);
        $this->connection    = $this->createMock(Connection::class);
        $this->repository    = new CampaignRepository($this->entityManager, $this->classMetadata);
    }

    public function testFetchEmailIdsById(): void
    {
        $id          = 2;
        $queryResult = [
            1 => [
                'channelId' => 1,
            ],
            2 => [
                'channelId' => 2,
            ],
        ];

        $expectedResult = [
            1,
            2,
        ];

        $queryBuilder = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['select', 'from', 'where', 'setParameter', 'andWhere'])
            ->addMethods(['getQuery'])
            ->getMock();

        $this->entityManager
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $queryBuilder->expects(self::once())
            ->method('select')
            ->with('e.channelId')
            ->willReturn($queryBuilder);

        $queryBuilder->expects(self::once())
            ->method('from')
            ->with('MauticCampaignBundle:Campaign', $this->repository->getTableAlias(), $this->repository->getTableAlias().'.id')
            ->willReturn($queryBuilder);

        $queryBuilder->expects(self::once())
            ->method('where')
            ->with($this->repository->getTableAlias().'.id = :id')
            ->willReturn($queryBuilder);

        $queryBuilder->expects(self::once())
            ->method('setParameter')
            ->with('id', $id)
            ->willReturn($queryBuilder);

        $queryBuilder->expects(self::once())
            ->method('andWhere')
            ->with('e.channelId IS NOT NULL')
            ->willReturn($queryBuilder);

        $query = $this->getMockBuilder(AbstractQuery::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setHydrationMode', 'getResult'])
            ->getMockForAbstractClass();

        $query->expects(self::once())
            ->method('setHydrationMode')
            ->with(Query::HYDRATE_ARRAY)
            ->willReturn($query);

        $queryBuilder->expects(self::once())
            ->method('getQuery')
            ->willReturn($query);

        $query->expects(self::once())
            ->method('getResult')
            ->willReturn($queryResult);

        $result = $this->repository->fetchEmailIdsById($id);

        $this->assertEquals($expectedResult, $result);
    }
}
