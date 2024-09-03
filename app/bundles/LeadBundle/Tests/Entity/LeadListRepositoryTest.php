<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Entity;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\Query\Expr;
use Mautic\CoreBundle\Test\Doctrine\RepositoryConfiguratorTrait;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\LeadListRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Cache\InvalidArgumentException;

class LeadListRepositoryTest extends TestCase
{
    use RepositoryConfiguratorTrait;

    private LeadListRepository $repository;

    /**
     * @var QueryBuilder&MockObject
     */
    private MockObject $queryBuilderMock;

    /**
     * @var Expr&MockObject
     */
    private MockObject $expressionMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connection       = $this->createMock(Connection::class);
        $this->queryBuilderMock = $this->createMock(QueryBuilder::class);
        $this->expressionMock   = $this->createMock(Expr::class);
        $this->repository       = $this->configureRepository(LeadList::class);
    }

    public function testIsContactInAnySegmentFalse(): void
    {
        $contactId = 1;
        $this->mockIsContactInAnySegment($contactId, []);
        self::assertFalse($this->repository->isContactInAnySegment($contactId));
    }

    public function testIsContactInAnySegmentTrue(): void
    {
        $contactId = 1;
        $this->mockIsContactInAnySegment($contactId, [1]);
        self::assertTrue($this->repository->isContactInAnySegment($contactId));
    }

    public function testIsNotContactInAnySegmentTrue(): void
    {
        $contactId = 1;
        $this->mockIsContactInAnySegment($contactId, []);
        self::assertTrue($this->repository->isNotContactInAnySegment($contactId));
    }

    public function testIsNotContactInAnySegmentFalse(): void
    {
        $contactId = 1;
        $this->mockIsContactInAnySegment($contactId, [1]);
        self::assertFalse($this->repository->isNotContactInAnySegment($contactId));
    }

    public function testIsContactInSegmentsNone(): void
    {
        $contactId          = 1;
        $expectedSegmentIds = [1];
        $queryResult        = [];
        $this->mockIsContactInSegments($contactId, $expectedSegmentIds, $queryResult);
        self::assertFalse($this->repository->isContactInSegments($contactId, $expectedSegmentIds));
    }

    public function testIsContactInSegmentsOne(): void
    {
        $contactId          = 1;
        $expectedSegmentIds = [1, 2];
        $queryResult        = [1];
        $this->mockIsContactInSegments($contactId, $expectedSegmentIds, $queryResult);
        self::assertTrue($this->repository->isContactInSegments($contactId, $expectedSegmentIds));
    }

    public function testIsContactInSegmentsAll(): void
    {
        $contactId          = 1;
        $expectedSegmentIds = [1, 2];
        $queryResult        = [1, 2];
        $this->mockIsContactInSegments($contactId, $expectedSegmentIds, $queryResult);
        self::assertTrue($this->repository->isContactInSegments($contactId, $expectedSegmentIds));
    }

    public function testIsNotContactInSegmentsNone(): void
    {
        $contactId          = 1;
        $expectedSegmentIds = [1];
        $queryResult        = [0];
        $this->mockIsContactInSegments($contactId, $expectedSegmentIds, $queryResult);
        self::assertTrue($this->repository->isNotContactInSegments($contactId, $expectedSegmentIds));
    }

    public function testIsNotContactInSegmentsOne(): void
    {
        $contactId          = 1;
        $expectedSegmentIds = [1, 2];
        $queryResult        = [1];
        $this->mockIsContactInSegments($contactId, $expectedSegmentIds, $queryResult);
        self::assertFalse($this->repository->isNotContactInSegments($contactId, $expectedSegmentIds));
    }

    public function testIsNotContactInSegmentsAll(): void
    {
        $contactId          = 1;
        $expectedSegmentIds = [1, 2];
        $queryResult        = [1, 2];
        $this->mockIsContactInSegments($contactId, $expectedSegmentIds, $queryResult);
        self::assertFalse($this->repository->isNotContactInSegments($contactId, $expectedSegmentIds));
    }

    /**
     * @param array<int> $queryResult
     */
    private function mockIsContactInAnySegment(int $contactId, array $queryResult): void
    {
        $prefix = MAUTIC_TABLE_PREFIX;
        $sql    = <<<SQL
            SELECT leadlist_id 
            FROM {$prefix}lead_lists_leads
            WHERE lead_id = ?
                AND manually_removed = 0
            LIMIT 1
SQL;
        $this->connection->expects(self::once())
            ->method('executeQuery')
            ->with($sql, [$contactId], [\PDO::PARAM_INT])
            ->willReturn($this->result);
        $this->result->expects(self::once())
            ->method('fetchFirstColumn')
            ->willReturn($queryResult);
    }

    /**
     * @param array<int> $expectedSegmentIds
     * @param array<int> $queryResult
     */
    private function mockIsContactInSegments(int $contactId, array $expectedSegmentIds, array $queryResult): void
    {
        $prefix = MAUTIC_TABLE_PREFIX;
        $sql    = <<<SQL
            SELECT leadlist_id 
            FROM {$prefix}lead_lists_leads
            WHERE lead_id = ?
                AND leadlist_id IN (?)
                AND manually_removed = 0
SQL;
        $this->connection->expects(self::once())
            ->method('executeQuery')
            ->with(
                $sql,
                [$contactId, $expectedSegmentIds],
                [\PDO::PARAM_INT, ArrayParameterType::INTEGER]
            )
            ->willReturn($this->result);

        $this->result->expects(self::once())
            ->method('fetchFirstColumn')
            ->willReturn($queryResult);
    }

    /**
     * @throws InvalidArgumentException
     */
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

        $this->mockGetLeadCount($queryResult, false);

        $this->queryBuilderMock->expects(self::once())
            ->method('from')
            ->with(MAUTIC_TABLE_PREFIX.'lead_lists_leads', 'l')
            ->willReturnSelf();

        $this->expressionMock->expects(self::once())
            ->method('in')
            ->with('l.leadlist_id', $listIds)
            ->willReturnSelf();

        $this->expressionMock->expects(self::once())
            ->method('eq')
            ->with('l.manually_removed', ':false')
            ->willReturnSelf();

        $this->queryBuilderMock->expects(self::once())
            ->method('setParameter')
            ->withConsecutive(
                ['false', false, 'boolean']
            )
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
    private function mockGetLeadCount(array $queryResult, bool $addParam = true): void
    {
        $this->connection->method('createQueryBuilder')
            ->willReturn($this->queryBuilderMock);

        $this->queryBuilderMock->expects(self::once())
            ->method('select')
            ->with('count(l.lead_id) as thecount, l.leadlist_id')
            ->willReturnSelf();

        $this->queryBuilderMock->expects(self::exactly(2))
            ->method('expr')
            ->willReturn($this->expressionMock);

        if ($addParam) {
            $this->queryBuilderMock->expects(self::once())
                ->method('setParameter')
                ->with('false', false, 'boolean')
                ->willReturnSelf();
        }

        $this->queryBuilderMock->expects(self::once())
            ->method('where')
            ->with($this->expressionMock)
            ->willReturnSelf();

        $this->queryBuilderMock->expects(self::once())
            ->method('executeQuery')
            ->willReturn($this->result);

        $this->result->expects(self::once())
            ->method('fetchAllAssociative')
            ->willReturn($queryResult);
    }
}
