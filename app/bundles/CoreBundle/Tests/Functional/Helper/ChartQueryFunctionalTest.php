<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\Helper;

use Mautic\CoreBundle\Doctrine\DatabasePlatform;
use Mautic\CoreBundle\Helper\Chart\ChartQuery;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use PHPUnit\Framework\Attributes\DataProvider;

final class ChartQueryFunctionalTest extends MauticMysqlTestCase
{
    public function testDistinctClickCountExcludesIncompleteTuples(): void
    {
        $expression = DatabasePlatform::getDistinctMultiColumnExpression(
            $this->connection->getDatabasePlatform(),
            't.email_id',
            't.redirect_id',
            't.lead_id'
        );
        $sql = 'SELECT COUNT('.$expression.') FROM (
            SELECT 1 AS email_id, 2 AS redirect_id, 3 AS lead_id
            UNION ALL SELECT 1, 2, 3
            UNION ALL SELECT 1, 2, 4
            UNION ALL SELECT NULL, 2, 3
            UNION ALL SELECT 1, NULL, 3
            UNION ALL SELECT 1, 2, NULL
            UNION ALL SELECT NULL, NULL, NULL
        ) t';

        $this->assertSame(2, (int) $this->connection->fetchOne($sql));
    }

    #[DataProvider('provideCalendarWeeks')]
    public function testWeeklyGroupingMatchesSundayBasedCalendar(string $date, string $offset, string $expected): void
    {
        $platform   = $this->connection->getDatabasePlatform();
        $column     = 'CAST(:chart_date AS '.$platform->getDateTimeTypeDeclarationSQL([]).')';
        $expression = DatabasePlatform::getDateConstructExpression($platform, $column, 'W', false, $offset);

        $this->assertSame($expected, $this->connection->fetchOne('SELECT '.$expression, ['chart_date' => $date]));
    }

    public static function provideCalendarWeeks(): iterable
    {
        yield 'before first Sunday' => ['2024-01-01 12:00:00', '+00:00', '2024 00'];
        yield 'Saturday' => ['2024-01-06 12:00:00', '+00:00', '2024 00'];
        yield 'first Sunday' => ['2024-01-07 00:00:00', '+00:00', '2024 01'];
        yield 'year starts on Sunday' => ['2023-01-01 12:00:00', '+00:00', '2023 01'];
        yield 'final Sunday' => ['2023-12-31 12:00:00', '+00:00', '2023 53'];
        yield 'leap year end' => ['2024-12-31 12:00:00', '+00:00', '2024 52'];
        yield 'offset crosses Sunday' => ['2024-01-06 23:30:00', '+01:00', '2024 01'];
        yield 'offset crosses year' => ['2024-01-01 01:00:00', '-03:00', '2023 53'];
    }

    #[DataProvider('provideBestHours')]
    public function testBestHoursUsesTheRequestedOffset(string $offset, int $format, string $expected): void
    {
        $platform   = $this->connection->getDatabasePlatform();
        $column     = 'CAST(:chart_date AS '.$platform->getDateTimeTypeDeclarationSQL([]).')';
        $expression = DatabasePlatform::getBestHoursSelectExpression($platform, 't.date_read', $format, $offset);
        $sql        = 'SELECT '.$expression.' FROM (SELECT 1 AS id, '.$column.' AS date_read) t GROUP BY t.date_read';
        $result     = $this->connection->fetchAssociative($sql, ['chart_date' => '2024-01-06 23:30:00']);

        $this->assertSame($expected, $result['hour']);
        $this->assertSame(1, (int) $result['count']);
    }

    public static function provideBestHours(): iterable
    {
        yield 'positive offset crosses midnight' => ['+02:00', 24, '01:00-02:00'];
        yield 'negative offset' => ['-03:00', 24, '20:00-21:00'];
        yield 'zero offset crosses midnight' => ['+00:00', 24, '23:00-00:00'];
        yield 'twelve hour format' => ['-03:00', 12, '08 PM-09 PM'];
    }

    #[DataProvider('provideDateDifferences')]
    public function testDateDifferenceCountsWholeSeconds(string $from, string $to, int $expected): void
    {
        $platform   = $this->connection->getDatabasePlatform();
        $type       = $platform->getDateTimeTypeDeclarationSQL([]);
        $expression = DatabasePlatform::getDateDiffInSeconds($platform, 'CAST(:date_from AS '.$type.')', 'CAST(:date_to AS '.$type.')');
        $actual     = $this->connection->fetchOne('SELECT '.$expression, ['date_from' => $from, 'date_to' => $to]);

        $this->assertSame($expected, (int) $actual);
    }

    public static function provideDateDifferences(): iterable
    {
        yield 'forward' => ['2024-01-06 23:59:30', '2024-01-07 00:00:30', 60];
        yield 'backward' => ['2024-01-07 00:00:30', '2024-01-06 23:59:30', -60];
        yield 'equal' => ['2024-01-06 23:59:30', '2024-01-06 23:59:30', 0];
    }

    public function testPageTimeSpentIsPositive(): void
    {
        $platform   = $this->connection->getDatabasePlatform();
        $type       = $platform->getDateTimeTypeDeclarationSQL([]);
        $expression = DatabasePlatform::getTimeSpentFormula($platform, 't.');
        $sql        = 'SELECT '.$expression.' FROM (SELECT CAST(:date_hit AS '.$type.') AS date_hit, CAST(:date_left AS '.$type.') AS date_left) t';

        $this->assertSame('00:01:00', $this->connection->fetchOne($sql, ['date_hit' => '2024-01-06 23:59:30', 'date_left' => '2024-01-07 00:00:30']));
    }

    public function testAverageDoesNotLimitTheIntegerPart(): void
    {
        $expression = DatabasePlatform::getAggregatorExpression($this->connection->getDatabasePlatform(), 'AVG', 'amount');
        $sql = 'SELECT '.$expression.' FROM (SELECT 1000000 AS amount UNION ALL SELECT 1000002 AS amount) totals';

        $this->assertSame('1000001.0000', $this->connection->fetchOne($sql));
    }

    public function testGetCountQueryWithUniqueOptionsExecutesAgainstDatabase(): void
    {
        $lead = new Lead();
        $lead->setEmail('chart-query-functional@example.test');
        $lead->setDateAdded(new \DateTime('now', new \DateTimeZone('UTC')));
        $lead->setDateIdentified(new \DateTime('now', new \DateTimeZone('UTC')));
        $this->em->persist($lead);
        $this->em->flush();

        $chartQuery = new ChartQuery(
            $this->connection,
            new \DateTime('-1 day', new \DateTimeZone('UTC')),
            new \DateTime('+1 day', new \DateTimeZone('UTC')),
            'd'
        );

        $countQuery = $chartQuery->getCountQuery(
            'leads',
            'id',
            null,
            [],
            ['getUnique' => true]
        );

        $count = $chartQuery->fetchCount($countQuery);

        $this->assertGreaterThanOrEqual(1, $count, 'Expected at least one unique lead in the date range.');
    }
}
