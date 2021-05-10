<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Segment\Stat\ChartQuery;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\Expression\ExpressionBuilder;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Statement;
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

        $this->chartQuery = new class($this->connection, $dateFrom, $dateTo, $filters) extends SegmentContactsLineChartQuery {
            /**
             * @var QueryBuilder
             */
            private $queryBuilder;

            public function setQueryBuilder(QueryBuilder $queryBuilder)
            {
                $this->queryBuilder = $queryBuilder;
            }

            public function prepareTimeDataQuery($table, $column, $filters = [], $countColumn = '*', $isEnumerable = true, bool $useSqlOrder = true)
            {
                return $this->queryBuilder;
            }

            public function setDateRange(\DateTime $dateFrom, \DateTime $dateTo)
            {
            }

            public function completeTimeData($rawData, $countAverage = false)
            {
                return [];
            }
        };

        $this->chartQuery->setQueryBuilder($this->queryBuilder);
        $this->connection->expects($this->any())
            ->method('createQueryBuilder')
            ->willReturn($this->queryBuilder);
    }

    public function testThatGetDataFromLeadListLeadsDoesntQueryDBTwice(): void
    {
        $this->queryBuilder->expects($this->any())
            ->method('getQueryPart')
            ->willReturn([]);

        $statement = $this->createMock(Statement::class);

        $this->queryBuilder->expects($this->once())
            ->method('execute')
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

        $statement = $this->createMock(Statement::class);
        $this->queryBuilder->expects($this->once())
            ->method('execute')
            ->willReturn($statement);

        $reflectionObject = new \ReflectionObject($this->chartQuery);
        $method           = $reflectionObject->getMethod('getFirstDateAddedSegmentEventLog');
        $method->setAccessible(true);
        $method->invokeArgs($this->chartQuery, [static::SEGMENT_ID]);
        $method->invokeArgs($this->chartQuery, [static::SEGMENT_ID]);
    }
}
