<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Entity;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\ResultStatement;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr;
use Mautic\LeadBundle\Entity\LeadListRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LeadListRepositoryTest extends TestCase
{
    /**
     * @var Connection|MockObject
     */
    private $connection;

    /**
     * @var MockObject
     */
    private $stmt;

    /**
     * @var LeadListRepository
     */
    private $repository;

    /**
     * @var QueryBuilder|MockObject
     */
    private $queryBuilderMock;

    /**
     * @var Expr|MockObject
     */
    private $expressionMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connection       = $this->createMock(Connection::class);
        $entityManager          = $this->createMock(EntityManager::class);
        $classMetadata          = $this->createMock(ClassMetadata::class);
        $this->stmt             = $this->createMock(ResultStatement::class);
        $this->queryBuilderMock = $this->createMock(QueryBuilder::class);
        $this->expressionMock   = $this->createMock(Expr::class);

        $this->repository = new LeadListRepository($entityManager, $classMetadata);

        $entityManager->method('getConnection')->willReturn($this->connection);
    }

    public function testGetMultipleLeadCounts(): void
    {
        $listIds = [765, 766];
        $counts  = [100, 200];

        $queryResult = [
            [
                'leadlist_id' => $listIds[0],
                'thecount'    => $counts[0],
            ],
            [
                'leadlist_id' => $listIds[1],
                'thecount'    => $counts[1],
            ],
        ];

        $this->mockGetLeadCount($queryResult);

        $this->queryBuilderMock->expects(self::once())
            ->method('from')
            ->with(MAUTIC_TABLE_PREFIX.'lead_lists_leads', 'l')
            ->willReturnSelf();

        $this->expressionMock->expects(self::once())
            ->method('in')
            ->with('l.leadlist_id', $listIds)
            ->willReturnSelf();

        $this->expressionMock
            ->method('eq')
            ->with('l.manually_removed', ':false')
            ->willReturnSelf();

        self::assertSame(array_combine($listIds, $counts), $this->repository->getLeadCount($listIds));
    }

    public function testGetSingleLeadCount(): void
    {
        $listIds     = [765];
        $counts      = [100];
        $queryResult = [
            [
                'leadlist_id' => $listIds[0],
                'thecount'    => $counts[0],
            ],
        ];

        $this->mockGetLeadCount($queryResult);

        $fromPart = [
            [
                'alias' => 'l',
                'table' => MAUTIC_TABLE_PREFIX.'lead_lists_leads',
            ],
        ];

        $this->queryBuilderMock->expects(self::once())
            ->method('getQueryPart')
            ->willReturn($fromPart);

        $this->queryBuilderMock->expects(self::exactly(2))
            ->method('from')
            ->withConsecutive(
                [
                    MAUTIC_TABLE_PREFIX.'lead_lists_leads',
                    'l',
                ],
                [
                    MAUTIC_TABLE_PREFIX.'lead_lists_leads',
                    'l USE INDEX ('.MAUTIC_TABLE_PREFIX.'manually_removed)',
                ]
            )
            ->willReturnOnConsecutiveCalls($this->queryBuilderMock, $this->queryBuilderMock);

        $this->expressionMock->expects(self::exactly(2))
            ->method('eq')
            ->withConsecutive(['l.leadlist_id', $listIds[0]], ['l.manually_removed', ':false'])
            ->willReturnSelf();

        self::assertSame($counts[0], $this->repository->getLeadCount($listIds));
    }

    /**
     * @param array<mixed> $queryResult
     */
    private function mockGetLeadCount(array $queryResult): void
    {
        $this->connection
            ->method('createQueryBuilder')
            ->willReturn($this->queryBuilderMock);

        $this->queryBuilderMock->expects(self::once())
            ->method('select')
            ->with('count(l.lead_id) as thecount, l.leadlist_id')
            ->willReturnSelf();

        $this->queryBuilderMock->expects(self::exactly(2))
            ->method('expr')
            ->willReturn($this->expressionMock);

        $this->queryBuilderMock->expects(self::once())
            ->method('setParameter')
            ->with('false', false, 'boolean')
            ->willReturnSelf();

        $this->queryBuilderMock->expects(self::once())
            ->method('where')
            ->with($this->expressionMock)
            ->willReturnSelf();

        $this->queryBuilderMock->expects(self::once())
            ->method('execute')
            ->willReturn($this->stmt);

        $this->stmt->expects(self::once())
            ->method('fetchAll')
            ->willReturn($queryResult);
    }
}
