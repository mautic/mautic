<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Entity;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\CampaignRepository;
use Mautic\CoreBundle\Test\Doctrine\RepositoryConfiguratorTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CampaignRepositoryTest extends TestCase
{
    use RepositoryConfiguratorTrait;

    /**
     * @var MockObject&QueryBuilder
     */
    private \PHPUnit\Framework\MockObject\MockObject $queryBuilder;

    private CampaignRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->queryBuilder = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['select', 'from', 'where', 'setParameter', 'andWhere', 'getQuery', 'getRootAliases'])
            ->getMock();

        $this->repository = $this->configureRepository(Campaign::class);

        $this->entityManager->method('createQueryBuilder')->willReturn($this->queryBuilder);
    }

    public function testFetchEmailIdsById(): void
    {
        $id = 2;

        $queryResult = [
            1 => ['channelId' => 1],
            2 => ['channelId' => 2],
        ];

        $expectedResult = [1, 2];

        $this->entityManager
            ->method('createQueryBuilder')
            ->willReturn($this->queryBuilder);

        $this->queryBuilder->expects(self::once())
            ->method('select')
            ->with('e.channelId')
            ->willReturn($this->queryBuilder);

        $this->queryBuilder->expects(self::once())
            ->method('from')
            ->with(\Mautic\CampaignBundle\Entity\Campaign::class, $this->repository->getTableAlias(), $this->repository->getTableAlias().'.id')
            ->willReturn($this->queryBuilder);

        $this->queryBuilder->expects(self::once())
            ->method('where')
            ->with($this->repository->getTableAlias().'.id = :id')
            ->willReturn($this->queryBuilder);

        $this->queryBuilder->expects(self::once())
            ->method('setParameter')
            ->with('id', $id)
            ->willReturn($this->queryBuilder);

        $this->queryBuilder->method('getRootAliases')
            ->willReturn(['e']);

        $this->queryBuilder->expects(self::once())
            ->method('andWhere')
            ->with('e.channelId IS NOT NULL')
            ->willReturn($this->queryBuilder);

        $query = $this->getMockBuilder(AbstractQuery::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setHydrationMode', 'getResult'])
            ->getMockForAbstractClass();

        $query->expects(self::once())
            ->method('setHydrationMode')
            ->with(Query::HYDRATE_ARRAY)
            ->willReturn($query);

        $this->queryBuilder->expects(self::once())
            ->method('getQuery')
            ->willReturn($query);

        $query->expects(self::once())
            ->method('getResult')
            ->willReturn($queryResult);

        $result = $this->repository->fetchEmailIdsById($id);

        $this->assertEquals($expectedResult, $result);
    }
}
