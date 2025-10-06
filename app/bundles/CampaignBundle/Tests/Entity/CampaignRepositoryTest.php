<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Entity;

use Doctrine\DBAL\Query\QueryBuilder as DbalQueryBuilder;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\CampaignRepository;
use Mautic\CoreBundle\Test\Doctrine\RepositoryConfiguratorTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class CampaignRepositoryTest extends TestCase
{
    use RepositoryConfiguratorTrait;

    /**
     * @var MockObject&QueryBuilder
     */
    private MockObject $queryBuilder;

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
        $this->connection->method('createQueryBuilder')->willReturnCallback(fn () => new DbalQueryBuilder($this->connection));

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(fn ($id) => match ($id) {
            'mautic.campaign.campaign.searchcommand.isexpired' => 'is:expired',
            'mautic.campaign.campaign.searchcommand.ispending' => 'is:pending',
            default                                            => $id,
        });
        $this->repository->setTranslator($translator);
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
            ->with(Campaign::class, $this->repository->getTableAlias(), $this->repository->getTableAlias().'.id')
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

        $query = $this->getMockBuilder(Query::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setHydrationMode', 'getResult'])
            ->getMock();

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

    public function testAddSearchCommandWhereClauseHandlesExpirationFilters(): void
    {
        $qb     = $this->connection->createQueryBuilder();
        $filter = (object) ['command' => 'is:expired', 'string' => '', 'not' => false, 'strict' => false];

        $method = new \ReflectionMethod(CampaignRepository::class, 'addSearchCommandWhereClause');
        $method->setAccessible(true);

        [$expr, $params] = $method->invoke($this->repository, $qb, $filter);

        self::assertSame(
            '(c.isPublished = :par1) AND (c.publishDown IS NOT NULL) AND (c.publishDown <> \'\') AND (c.publishDown < CURRENT_TIMESTAMP())',
            (string) $expr
        );
        self::assertSame(['par1' => true], $params);
    }

    public function testAddSearchCommandWhereClauseHandlesPendingFilters(): void
    {
        $qb     = $this->connection->createQueryBuilder();
        $filter = (object) ['command' => 'is:pending', 'string' => '', 'not' => false, 'strict' => false];

        $method = new \ReflectionMethod(CampaignRepository::class, 'addSearchCommandWhereClause');
        $method->setAccessible(true);

        [$expr, $params] = $method->invoke($this->repository, $qb, $filter);

        self::assertSame(
            '(c.isPublished = :par1) AND (c.publishUp IS NOT NULL) AND (c.publishUp <> \'\') AND (c.publishUp > CURRENT_TIMESTAMP())',
            (string) $expr
        );
        self::assertSame(['par1' => true], $params);
    }

    public function testGetSearchCommandsContainsExpirationFilters(): void
    {
        $commands = $this->repository->getSearchCommands();
        self::assertContains('mautic.campaign.campaign.searchcommand.isexpired', $commands);
        self::assertContains('mautic.campaign.campaign.searchcommand.ispending', $commands);
    }
}
