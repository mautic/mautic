<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\Tests\EventListener;

use Doctrine\DBAL\Driver\PDOStatement;
use Doctrine\DBAL\Query\Expression\ExpressionBuilder;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Helper\Chart\ChartQuery;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\PageBundle\Entity\HitRepository;
use Mautic\PageBundle\EventListener\ReportSubscriber;
use Mautic\ReportBundle\Event\ReportBuilderEvent;
use Mautic\ReportBundle\Event\ReportGeneratorEvent;
use Mautic\ReportBundle\Event\ReportGraphEvent;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ReportSubscriberTest extends WebTestCase
{
    public function testOnReportBuilderAddsPageAndPageHitReports()
    {
        $mockEvent = $this->getMockBuilder(ReportBuilderEvent::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkContext',
                'addGraph',
                'getStandardColumns',
                'getCategoryColumns',
                'getCampaignByChannelColumns',
                'addTable',
            ])
            ->getMock();

        $mockEvent->expects($this->once())
            ->method('getStandardColumns')
            ->willReturn([]);

        $mockEvent->expects($this->once())
            ->method('getCategoryColumns')
            ->willReturn([]);

        $mockEvent->expects($this->once())
            ->method('getCampaignByChannelColumns')
            ->willReturn([]);

        $mockEvent->expects($this->exactly(2))
            ->method('checkContext')
            ->willReturn(true);

        $setTables = [];
        $setGraphs = [];

        $mockEvent->expects($this->exactly(2))
            ->method('addTable')
            ->willReturnCallback(function () use (&$setTables) {
                $args = func_get_args();

                $setTables[] = $args;
            });

        $mockEvent->expects($this->exactly(9))
            ->method('addGraph')
            ->willReturnCallback(function () use (&$setGraphs) {
                $args = func_get_args();

                $setGraphs[] = $args;
            });

        $subscriber = new ReportSubscriber();

        $subscriber->onReportBuilder($mockEvent);

        $this->assertCount(2, $setTables);
        $this->assertCount(9, $setGraphs);
    }

    public function testOnReportGeneratePagesContext()
    {
        $mockEvent = $this->getMockBuilder(ReportGeneratorEvent::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getContext',
                'getQueryBuilder',
                'addCategoryLeftJoin',
                'setQueryBuilder',
            ])
            ->getMock();

        $mockQueryBuilder = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->setMethods(['from', 'leftJoin'])
            ->getMock();

        $mockQueryBuilder->expects($this->once())
            ->method('from')
            ->willReturn($mockQueryBuilder);

        $mockQueryBuilder->expects($this->exactly(2))
            ->method('leftJoin')
            ->willReturn($mockQueryBuilder);

        $mockEvent->expects($this->once())
            ->method('getQueryBuilder')
            ->willReturn($mockQueryBuilder);

        $mockEvent->expects($this->once())
            ->method('getContext')
            ->willReturn('pages');

        $subscriber = new ReportSubscriber();

        $subscriber->onReportGenerate($mockEvent);
    }

    public function testOnReportGeneratePageHitsContext()
    {
        $mockEvent = $this->getMockBuilder(ReportGeneratorEvent::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getContext',
                'getQueryBuilder',
                'addCategoryLeftJoin',
                'addIpAddressLeftJoin',
                'addLeadLeftJoin',
                'addCampaignByChannelJoin',
                'applyDateFilters',
                'setQueryBuilder',
            ])
            ->getMock();

        $mockQueryBuilder = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->setMethods(['from', 'leftJoin'])
            ->getMock();

        $mockQueryBuilder->expects($this->once())
            ->method('from')
            ->willReturn($mockQueryBuilder);

        $mockQueryBuilder->expects($this->exactly(5))
            ->method('leftJoin')
            ->willReturn($mockQueryBuilder);

        $mockEvent->expects($this->once())
            ->method('getQueryBuilder')
            ->willReturn($mockQueryBuilder);

        $mockEvent->expects($this->once())
            ->method('getContext')
            ->willReturn('page.hits');

        $subscriber = new ReportSubscriber();

        $subscriber->onReportGenerate($mockEvent);
    }

    public function testOnReportGraphGenerateBadContextWillReturn()
    {
        $mockEvent = $this->getMockBuilder(ReportGraphEvent::class)
            ->disableOriginalConstructor()
            ->setMethods(['checkContext'])
            ->getMock();

        $mockEvent->expects($this->once())
            ->method('checkContext')
            ->willReturn(false);

        $mockEvent->expects($this->never())
            ->method('getRequestedGraphs');

        $subscriber = new ReportSubscriber();

        $subscriber->onReportGraphGenerate($mockEvent);
    }

    public function testOnReportGraphGenerate()
    {
        $mockEvent = $this->getMockBuilder(ReportGraphEvent::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkContext',
                'getQuerybuilder',
                'getOptions',
                'getRequestedGraphs',
            ])
            ->getMock();

        $mockTrans = $this->getMockBuilder(Translator::class)
            ->disableOriginalConstructor()
            ->setMethods(['trans'])
            ->getMock();

        $mockTrans->expects($this->any())
            ->method('trans')
            ->willReturnArgument(0);

        $mockExprBuilder = $this->getMockBuilder(ExpressionBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockQueryBuilder = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->setMethods(['expr', 'execute'])
            ->getMock();

        $mockStmt = $this->getMockBuilder(PDOStatement::class)
            ->disableOriginalConstructor()
            ->setMethods(['fetchAll'])
            ->getMock();

        $mockStmt->expects($this->exactly(2))
            ->method('fetchAll')
            ->willReturn(
                [
                    [
                        'device'        => 'iPhone',
                        'page_language' => 'en_US',
                        'the_count'     => 3,
                    ],
                    [
                        'device'        => 'iPad',
                        'page_language' => 'en_GB',
                        'the_count'     => 4,
                    ],
                ]
            );

        $mockQueryBuilder->expects($this->any())
            ->method('expr')
            ->willReturn($mockExprBuilder);

        $mockQueryBuilder->expects($this->any())
            ->method('execute')
            ->willReturn($mockStmt);

        $mockEvent->expects($this->once())
            ->method('getQueryBuilder')
            ->willReturn($mockQueryBuilder);

        $mockChartQuery = $this->getMockBuilder(ChartQuery::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'modifyCountQuery',
                'modifyTimeDataQuery',
                'loadAndBuildTimeData',
                'fetchCount',
                'fetchCountDateDiff',
            ])
            ->getMock();

        $mockChartQuery->expects($this->any())
            ->method('loadAndBuildTimeData')
            ->willReturn(['a', 'b', 'c']);

        $mockChartQuery->expects($this->any())
            ->method('fetchCount')
            ->willReturn(2);

        $mockChartQuery->expects($this->any())
            ->method('fetchCountDateDiff')
            ->willReturn(2);

        $graphOptions = [
            'chartQuery' => $mockChartQuery,
            'translator' => $mockTrans,
            'dateFrom'   => new \DateTime(),
            'dateTo'     => new \DateTime(),
        ];

        $mockEvent->expects($this->once())
            ->method('checkContext')
            ->willReturn(true);

        $mockEvent->expects($this->any())
            ->method('getOptions')
            ->willReturn($graphOptions);

        $mockEvent->expects($this->once())
            ->method('getRequestedGraphs')
            ->willReturn(
                [
                    'mautic.page.graph.line.hits',
                    'mautic.page.graph.line.time.on.site',
                    'mautic.page.graph.pie.time.on.site',
                    'mautic.page.graph.pie.new.vs.returning',
                    'mautic.page.graph.pie.languages',
                    'mautic.page.graph.pie.devices',
                    'mautic.page.table.referrers',
                    'mautic.page.table.most.visited',
                    'mautic.page.table.most.visited.unique',
                ]
            );

        $mockHitRepo = $this->getMockBuilder(HitRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['getMostVisited', 'getReferers', 'getDwellTimeLabels'])
            ->getMock();

        $mockHitRepo->expects($this->exactly(2))
            ->method('getMostVisited')
            ->willReturn(['a', 'b', 'c']);

        $mockHitRepo->expects($this->once())
            ->method('getReferers')
            ->willReturn(['a', 'b', 'c']);

        $mockHitRepo->expects($this->once())
            ->method('getDwellTimeLabels')
            ->willReturn(
                [
                    [
                        'from'  => new \DateTime(),
                        'till'  => new \DateTime(),
                        'label' => 'My Chart',
                    ],
                ]
            );

        $mockEntityManager = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRepository'])
            ->getMock();

        $mockEntityManager->expects($this->any())
            ->method('getRepository')
            ->willReturnMap([
                ['MauticPageBundle:Hit', $mockHitRepo],
            ]);

        $subscriber = new ReportSubscriber();

        $subscriber->setEntityManager($mockEntityManager);
        $subscriber->setTranslator($mockTrans);

        $subscriber->onReportGraphGenerate($mockEvent);
    }
}
