<?php

declare(strict_types=1);

namespace Mautic\ReportBundle\Tests\Functional;

use Mautic\ChannelBundle\Helper\ChannelListHelper;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\PageBundle\Entity\Hit;
use Mautic\ReportBundle\Builder\MauticReportBuilder;
use Mautic\ReportBundle\Entity\Report;
use Mautic\ReportBundle\Event\ReportGeneratorEvent;
use Mautic\ReportBundle\ReportEvents;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\EventDispatcher\EventDispatcher;

final class MauticReportBuilderFunctionalTest extends MauticMysqlTestCase
{
    #[DataProvider('provideDateGrouping')]
    public function testDateGroupingPreservesDailyCountsWhenSorted(string $grouping): void
    {
        foreach (['2024-01-06 09:00:00', '2024-01-06 17:00:00', '2024-01-07 09:00:00'] as $date) {
            $hit = new Hit();
            $hit->setDateHit(new \DateTime($date, new \DateTimeZone('UTC')));
            $hit->setTrackingId(bin2hex(random_bytes(8)));
            $hit->setUrl('https://example.com/grouped-date');
            $hit->setCode(200);
            $this->em->persist($hit);
        }
        $this->em->flush();

        $report = new Report();
        $report->setColumns(['ph.url', 'ph.date_hit', 'ph.id']);
        $report->setGroupBy('selected_formula' === $grouping ? ['ph.url'] : ['ph.date_hit']);
        $report->setAggregators([['column' => 'ph.id', 'function' => 'COUNT']]);
        $report->setTableOrder([['column' => 'ph.date_hit', 'direction' => 'DESC']]);

        $dispatcher = new EventDispatcher();
        $tableName  = $this->em->getClassMetadata(Hit::class)->getTableName();
        $dispatcher->addListener(ReportEvents::REPORT_ON_GENERATE, static function (ReportGeneratorEvent $event) use ($tableName): void {
            $event->getQueryBuilder()->from($tableName, 'ph');
        });
        $builder = new MauticReportBuilder($dispatcher, $this->connection, $report, $this->getContainer()->get(ChannelListHelper::class));
        $query   = $builder->getQuery([
            'columns' => [
                'ph.url'      => [],
                'ph.date_hit' => [
                    'alias' => 'hit_date',
                    'group_by_formula' === $grouping ? 'groupByFormula' : 'formula' => 'DATE(ph.date_hit)',
                ],
                'ph.id' => [],
            ],
        ]);
        $rows = $query->executeQuery()->fetchAllAssociative();

        $this->assertCount(2, $rows);
        $this->assertSame(['2024-01-07', '2024-01-06'], array_column($rows, 'hit_date'));
        $this->assertSame([1, 2], array_map(intval(...), array_column($rows, 'COUNT ph.id')));
    }

    public static function provideDateGrouping(): iterable
    {
        yield ['group_by_formula'];
        yield ['formula'];
        yield ['selected_formula'];
    }
}
