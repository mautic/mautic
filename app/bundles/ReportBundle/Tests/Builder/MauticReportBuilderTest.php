<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ReportBundle\Tests\Builder;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Mautic\ChannelBundle\Helper\ChannelListHelper;
use Mautic\ReportBundle\Builder\MauticReportBuilder;
use Mautic\ReportBundle\Entity\Report;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class MauticReportBuilderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject|EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var MockObject|Connection
     */
    private $connection;

    /**
     * @var MockObject|QueryBuilder
     */
    private $queryBuilder;

    /**
     * @var MockObject|ChannelListHelper
     */
    private $channelListHelper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dispatcher        = $this->createMock(EventDispatcherInterface::class);
        $this->connection        = $this->createMock(Connection::class);
        $this->queryBuilder      = new QueryBuilder($this->connection);
        $this->channelListHelper = $this->createMock(ChannelListHelper::class);

        $this->connection->method('createQueryBuilder')->willReturn($this->queryBuilder);
    }

    public function testColumnSanitization(): void
    {
        $report = new Report();
        $report->setColumns(['a.b', 'b.c']);
        $builder = $this->buildBuilder($report);
        $query   = $builder->getQuery([
            'columns' => ['a.b' => [], 'b.c' => []],
        ]);
        Assert::assertSame('SELECT `a`.`b`, `b`.`c`', $query->getSql());
    }

    private function buildBuilder(Report $report): MauticReportBuilder
    {
        return new MauticReportBuilder(
            $this->dispatcher,
            $this->connection,
            $report,
            $this->channelListHelper
        );
    }
}
