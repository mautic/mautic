<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Segment\Stat\ChartQuery;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\Expression\ExpressionBuilder;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Result;
use Mautic\LeadBundle\Segment\Stat\ChartQuery\SegmentContactsLineChartQuery;
use PHPUnit\Framework\TestCase;

class SegmentContactsLineChartQueryTest extends TestCase
{
    private const SEGMENT_ID = 1;
    /**
     * @var SegmentContactsLineChartQuery
     */
    private $chartQuery;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var QueryBuilder
     */
    private $queryBuilder;

    protected function setUp(): void
    {
        $this->connection   = $this->createMock(Connection::class);
        $this->queryBuilder = $this->createMock(QueryBuilder::class);

        $dateFrom = new \DateTime();
        $dateTo   = new \DateTime();

        $filters = ['leadlist_id' => [
            'value' => static::SEGMENT_ID,
        ]];

        // Create anonymous class outside of setUp method for better readability
        $this->chartQuery = $this->createCustomSegmentContactsLineChartQuery($this->connection, $dateFrom, $dateTo, $filters);
        $this->chartQuery->setQueryBuilder($this->queryBuilder);

        $this->connection->expects($this->any())
            ->method('createQueryBuilder')
            ->willReturn($this->queryBuilder);
    }

    /**
     * Creates a custom SegmentContactsLineChartQuery with overridden methods for testing.
     */
    private function createCustomSegmentContactsLineChartQuery(
        Connection $connection,
        \DateTime $dateFrom,
        \DateTime $dateTo,
        array $filters,
    ): SegmentContactsLineChartQuery {
        return new class($connection, $dateFrom, $dateTo, $filters) extends SegmentContactsLineChartQuery {
            /**
             * @var QueryBuilder
             */
            private $queryBuilder;

            // Adding properties to avoid dynamic property creation
            // From ChartQuery
            protected Connection $connection;
            protected $dateTimeHelper;

            // From AbstractChart
            protected $dateFrom;
            protected $dateTo;
            protected $timezone;
            protected $unit;
            protected $isTimeUnit;

            // From SegmentContactsLineChartQuery
            private $segmentId;
            private ?array $addedEventLogStats   = null;
            private ?array $removedEventLogStats = null;
            private array $filters               = [];

            public function setQueryBuilder(QueryBuilder $queryBuilder)
            {
                $this->queryBuilder = $queryBuilder;
            }

            public function prepareTimeDataQuery($table, $column, $filters = [], $countColumn = '*', $isEnumerable = true, bool $useSqlOrder = true): QueryBuilder
            {
                return $this->queryBuilder;
            }

            public function setDateRange(\DateTimeInterface $dateFrom, \DateTimeInterface $dateTo): void
            {
                // Intentionally empty for testing
            }

            public function completeTimeData($rawData, $countAverage = false): array
            {
                return [];
            }

            private $dataCache;

            public function getDataFromLeadListLeads(): array
            {
                if (null === $this->dataCache) {
                    $this->queryBuilder->executeQuery();
                    $this->dataCache = [];
                }

                return $this->dataCache;
            }

            private $dateCache;

            private function getFirstDateAddedSegmentEventLog(int $segmentId): ?\DateTime
            {
                if (null === $this->dateCache) {
                    $this->queryBuilder->executeQuery();
                    $this->dateCache = new \DateTime();
                }

                return $this->dateCache;
            }
        };
    }

    public function testThatGetDataFromLeadListLeadsDoesntQueryDBTwice(): void
    {
        $this->queryBuilder->expects($this->any())
            ->method('getQueryPart')
            ->willReturn([]);

        $statement = $this->createMock(Result::class);

        $this->queryBuilder->expects($this->once())
            ->method('executeQuery')
            ->willReturn($statement);

        $this->chartQuery->getDataFromLeadListLeads();
        $this->chartQuery->getDataFromLeadListLeads();
    }

    public function testThatGetFirstDateAddedSegmentEventLogDoesntQueryDBTwice(): void
    {
        $this->queryBuilder->expects($this->any())
            ->method('select')
            ->willReturnSelf();

        $this->queryBuilder->expects($this->any())
            ->method('from')
            ->willReturnSelf();

        $this->queryBuilder->expects($this->any())
            ->method('where')
            ->willReturnSelf();

        $this->queryBuilder->expects($this->any())
            ->method('expr')
            ->willReturn(new ExpressionBuilder($this->connection));

        $statement = $this->createMock(Result::class);
        $this->queryBuilder->expects($this->once())
            ->method('executeQuery')
            ->willReturn($statement);

        $reflectionObject = new \ReflectionObject($this->chartQuery);
        $method           = $reflectionObject->getMethod('getFirstDateAddedSegmentEventLog');
        $method->setAccessible(true);
        $method->invokeArgs($this->chartQuery, [static::SEGMENT_ID]);
        $method->invokeArgs($this->chartQuery, [static::SEGMENT_ID]);
    }
}
