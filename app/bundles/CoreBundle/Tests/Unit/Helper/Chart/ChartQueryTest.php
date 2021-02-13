<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Tests\Unit\Helper\Chart;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Mautic\CoreBundle\Doctrine\GeneratedColumn\GeneratedColumn;
use Mautic\CoreBundle\Doctrine\GeneratedColumn\GeneratedColumns;
use Mautic\CoreBundle\Doctrine\Provider\GeneratedColumnsProviderInterface;
use Mautic\CoreBundle\Helper\Chart\ChartQuery;
use PHPUnit\Framework\MockObject\MockObject;

class ChartQueryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \DateTime
     */
    private $dateFrom;

    /**
     * @var \DateTime
     */
    private $dateTo;

    /**
     * @var MockObject|Connection
     */
    private $connection;

    /**
     * @var MockObject|QueryBuilder
     */
    private $queryBuilder;

    /**
     * @var string
     */
    private $dateColumn;

    /**
     * @var string
     */
    private $unit;

    /**
     * @var ChartQuery
     */
    private $chartQuery;

    protected function setUp(): void
    {
        parent::setUp();

        defined('MAUTIC_TABLE_PREFIX') or define('MAUTIC_TABLE_PREFIX', '');

        $this->dateFrom     = new \DateTime('2018-01-01 12:00:00');
        $this->dateTo       = new \DateTime('2018-02-01 12:00:00');
        $this->unit         = 'd';
        $this->dateColumn   = 'date_sent';
        $this->connection   = $this->createMock(Connection::class);
        $this->queryBuilder = $this->createMock(QueryBuilder::class);

        $this->connection->method('createQueryBuilder')->willReturn($this->queryBuilder);
    }

    public function testClassicDateColumn()
    {
        $this->createChartQuery();

        $this->queryBuilder->expects($this->once())
            ->method('select')
            ->with('DATE_FORMAT(t.date_sent, \'%Y-%m-%d\') AS date, COUNT(*) AS count');

        $this->queryBuilder->expects($this->once())
            ->method('groupBy')
            ->with('DATE_FORMAT(t.date_sent, \'%Y-%m-%d\')');

        $this->queryBuilder->expects($this->once())
            ->method('orderBy')
            ->with('DATE_FORMAT(t.date_sent, \'%Y-%m-%d\')');

        $this->queryBuilder->expects($this->once())
            ->method('setMaxResults')
            ->with(32);

        $this->chartQuery->prepareTimeDataQuery('email_stats', $this->dateColumn);
    }

    public function testGeneratedDateColumn()
    {
        $this->createChartQuery();

        $generatedColumn          = new GeneratedColumn('email_stats', 'generated_sent_date', 'DATE', 'CONCAT(YEAR(date_sent), "-", LPAD(MONTH(date_sent), 2, "0"), "-", LPAD(DAY(date_sent), 2, "0"))');
        $generatedColumns         = new GeneratedColumns();
        $generatedColumnsProvider = $this->createMock(GeneratedColumnsProviderInterface::class);

        $generatedColumn->addIndexColumn('email_id');
        $generatedColumn->setOriginalDateColumn($this->dateColumn, $this->unit);
        $generatedColumns->add($generatedColumn);

        $generatedColumnsProvider->expects($this->once())
            ->method('getGeneratedColumns')
            ->willReturn($generatedColumns);

        $this->chartQuery->setGeneratedColumnProvider($generatedColumnsProvider);

        $this->queryBuilder->expects($this->once())
            ->method('select')
            ->with('t.generated_sent_date AS date, COUNT(*) AS count');

        $this->queryBuilder->expects($this->once())
            ->method('groupBy')
            ->with('t.generated_sent_date');

        $this->queryBuilder->expects($this->once())
            ->method('orderBy')
            ->with('t.generated_sent_date');

        $this->chartQuery->prepareTimeDataQuery('email_stats', $this->dateColumn);
    }

    public function testPhpOrderingInCompleteTimeDataHour(): void
    {
        $this->dateFrom = new \DateTime('2020-12-01 00:00:00.000000');
        $this->dateTo   = new \DateTime('2020-12-02 13:31:55.492167');
        $this->unit     = 'H';
        $expectedResult = [
            0  => 0,
            1  => 0,
            2  => 0,
            3  => 0,
            4  => 0,
            5  => 0,
            6  => 0,
            7  => 0,
            8  => 0,
            9  => 0,
            10 => 0,
            11 => 0,
            12 => 0,
            13 => 0,
            14 => 0,
            15 => 0,
            16 => 0,
            17 => 0,
            18 => 0,
            19 => 0,
            20 => 0,
            21 => 0,
            22 => 0,
            23 => 0,
            24 => 0,
            25 => 0,
            26 => 0,
            27 => 0,
            28 => 0,
            29 => 0,
            30 => 0,
            31 => 0,
            32 => '1',
            33 => '2',
            34 => 0,
            35 => '3',
            36 => 0,
            37 => 0,
        ];

        $rawData = [
            0 => [
                'count' => '1',
                'date'  => '2020-12-02 08:00',
            ],
            1 => [
                'count' => '2',
                'date'  => '2020-12-02 09:00',
            ],
            2 => [
                'count' => '3',
                'date'  => '2020-12-02 11:00',
            ],
        ];

        $this->assertTimeDataWithoutSqlOrder($expectedResult, $rawData);

        $rawData = [
            0 => [
                'count' => '3',
                'date'  => '2020-12-02 11:00',
            ],
            1 => [
                'count' => '2',
                'date'  => '2020-12-02 09:00',
            ],
            2 => [
                'count' => '1',
                'date'  => '2020-12-02 08:00',
            ],
        ];

        $this->assertTimeDataWithoutSqlOrder($expectedResult, $rawData);
    }

    public function testPhpOrderingInCompleteTimeDataDay(): void
    {
        $this->dateFrom = new \DateTime('2020-11-18 12:00:00');
        $this->dateTo   = new \DateTime('2020-12-02 12:00:00');
        $this->unit     = 'd';
        $expectedResult = [
            0  => 0,
            1  => 0,
            2  => 0,
            3  => 0,
            4  => 0,
            5  => 0,
            6  => 0,
            7  => 0,
            8  => 0,
            9  => 0,
            10 => 0,
            11 => '1',
            12 => '2',
            13 => 0,
            14 => '3',
        ];

        $rawData = [
            0 => [
                'count' => '1',
                'date'  => '2020-11-29',
            ],
            1 => [
                'count' => '2',
                'date'  => '2020-11-30',
            ],
            2 => [
                'count' => '3',
                'date'  => '2020-12-02',
            ],
        ];

        $this->assertTimeDataWithoutSqlOrder($expectedResult, $rawData);

        $rawData = [
            0 => [
                'count' => '1',
                'date'  => '2020-11-29',
            ],
            1 => [
                'count' => '2',
                'date'  => '2020-11-30',
            ],
            2 => [
                'count' => '3',
                'date'  => '2020-12-02',
            ],
        ];

        $this->assertTimeDataWithoutSqlOrder($expectedResult, $rawData);
    }

    public function testPhpOrderingInCompleteTimeDataWeek(): void
    {
        $this->dateFrom = new \DateTime('2020-10-31 12:00:00');
        $this->dateTo   = new \DateTime('2020-12-02 12:00:00');
        $this->unit     = 'W';
        $expectedResult = [
            0 => 0,
            1 => 0,
            2 => 0,
            3 => '2',
            4 => '1',
            5 => 0,
        ];

        $rawData = [
            0 => [
                'count' => '1',
                'date'  => '2020 48',
            ],
            1 => [
                'count' => '2',
                'date'  => '2020 47',
            ],
        ];

        $this->assertTimeDataWithoutSqlOrder($expectedResult, $rawData);

        $rawData = [
            0 => [
                'count' => '2',
                'date'  => '2020 47',
            ],
            1 => [
                'count' => '1',
                'date'  => '2020 48',
            ],
        ];

        $this->assertTimeDataWithoutSqlOrder($expectedResult, $rawData);
    }

    private function createChartQuery(): void
    {
        $this->chartQuery = new ChartQuery($this->connection, $this->dateFrom, $this->dateTo, $this->unit);
    }

    private function assertTimeDataWithoutSqlOrder($expectedResult, $data): void
    {
        $this->createChartQuery();
        self::assertSame(
            $expectedResult,
            $this->chartQuery->completeTimeData($data, false, false)
        );
    }
}
