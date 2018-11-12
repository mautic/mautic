<?php

/*
 * @copyright   2015 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Tests\Helper\Chart;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Mautic\CoreBundle\Helper\Chart\ChartQuery;

class ChartQueryTest extends \PHPUnit_Framework_TestCase
{
    private $connection;
    private $queryBuilder;
    private $dateColumn;
    private $unit;
    private $chartQuery;

    protected function setUp()
    {
        parent::setUp();

        defined('MAUTIC_TABLE_PREFIX') or define('MAUTIC_TABLE_PREFIX', '');

        $dateFrom           = new \DateTime('2018-01-01 12:00:00');
        $dateTo             = new \DateTime('2018-02-01 12:00:00');
        $this->unit         = 'd';
        $this->dateColumn   = 'date_sent';
        $this->connection   = $this->createMock(Connection::class);
        $this->queryBuilder = $this->createMock(QueryBuilder::class);
        $this->chartQuery   = new ChartQuery($this->connection, $dateFrom, $dateTo, $this->unit);

        $this->connection->method('createQueryBuilder')->willReturn($this->queryBuilder);
    }

    public function testClassicDateColumn()
    {
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
        $this->chartQuery->addGeneratedColumn('generated_sent_date', $this->dateColumn, $this->unit);

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
}
