<?php

namespace Mautic\PageBundle\Tests\EventListener;

use DateTime;
use Doctrine\DBAL\Driver\PDOStatement;
use Doctrine\DBAL\Query\Expression\ExpressionBuilder;
use Doctrine\DBAL\Query\QueryBuilder;
use Mautic\CoreBundle\Helper\Chart\ChartQuery;
use Mautic\LeadBundle\Model\CompanyReportData;
use Mautic\PageBundle\Entity\HitRepository;
use Mautic\PageBundle\EventListener\ReportSubscriber;
use Mautic\ReportBundle\Entity\Report;
use Mautic\ReportBundle\Event\ReportBuilderEvent;
use Mautic\ReportBundle\Event\ReportGeneratorEvent;
use Mautic\ReportBundle\Event\ReportGraphEvent;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\TranslatorInterface;

class ReportSubscriberTest extends TestCase
{
    /**
     * @var CompanyReportData|\PHPUnit\Framework\MockObject\MockObject
     */
    private $companyReportData;

    /**
     * @var HitRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    private $hitRepository;

    /**
     * @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $translator;

    /**
     * @var ReportSubscriber
     */
    private $subscriber;

    public function setUp(): void
    {
        parent::setUp();

        $this->companyReportData = $this->createMock(CompanyReportData::class);
        $this->hitRepository     = $this->createMock(HitRepository::class);
        $this->translator        = $this->createMock(TranslatorInterface::class);
        $this->subscriber        = new ReportSubscriber(
            $this->companyReportData,
            $this->hitRepository,
            $this->translator
        );
    }

    public function testOnReportBuilderAddsPageAndPageHitReports(): void
    {
        $mockEvent = $this->getMockBuilder(ReportBuilderEvent::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
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

        $mockEvent->expects($this->exactly(3))
            ->method('checkContext')
            ->willReturn(true);

        $setTables = [];
        $setGraphs = [];

        $mockEvent->expects($this->exactly(3))
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

        $this->companyReportData->expects($this->once())
            ->method('getCompanyData')
            ->with()
            ->willReturn([]);

        $this->subscriber->onReportBuilder($mockEvent);

        $this->assertCount(3, $setTables);
        $this->assertCount(9, $setGraphs);
    }

    public function testOnReportGeneratePagesContext(): void
    {
        $mockEvent = $this->getMockBuilder(ReportGeneratorEvent::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'getContext',
                'getQueryBuilder',
                'addCategoryLeftJoin',
                'setQueryBuilder',
                'getReport',
            ])
            ->getMock();

        $reportMock = $this->createMock(Report::class);
        $reportMock->expects($this->once())
            ->method('getGroupBy')
            ->willReturn('');

        $mockQueryBuilder = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['from', 'leftJoin'])
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

        $mockEvent->expects($this->once())
            ->method('getReport')
            ->willReturn($reportMock);

        $this->subscriber->onReportGenerate($mockEvent);
    }

    public function testOnReportGeneratePageHitsContext(): void
    {
        $mockEvent = $this->getMockBuilder(ReportGeneratorEvent::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'getContext',
                'getQueryBuilder',
                'addCategoryLeftJoin',
                'addIpAddressLeftJoin',
                'addLeadLeftJoin',
                'addCampaignByChannelJoin',
                'applyDateFilters',
                'setQueryBuilder',
                'getReport',
            ])
            ->getMock();

        $reportMock = $this->createMock(Report::class);
        $reportMock->expects($this->once())
            ->method('getGroupBy')
            ->willReturn('');

        $mockQueryBuilder = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['from', 'leftJoin'])
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

        $mockEvent->expects($this->once())
            ->method('getReport')
            ->willReturn($reportMock);

        $this->subscriber->onReportGenerate($mockEvent);
    }

    public function testOnReportGraphGenerateBadContextWillReturn(): void
    {
        $mockEvent = $this->getMockBuilder(ReportGraphEvent::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['checkContext', 'getRequestedGraphs'])
            ->getMock();

        $mockEvent->expects($this->once())
            ->method('checkContext')
            ->willReturn(false);

        $mockEvent->expects($this->never())
            ->method('getRequestedGraphs');

        $this->subscriber->onReportGraphGenerate($mockEvent);
    }

    public function testOnReportGraphGenerate(): void
    {
        $mockEvent = $this->getMockBuilder(ReportGraphEvent::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'checkContext',
                'getQuerybuilder',
                'getOptions',
                'getRequestedGraphs',
            ])
            ->getMock();

        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturnArgument(0);

        $mockExprBuilder = $this->getMockBuilder(ExpressionBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockQueryBuilder = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['expr', 'execute'])
            ->getMock();

        $mockStmt = $this->getMockBuilder(PDOStatement::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['fetchAll'])
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
            ->onlyMethods([
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
            'translator' => $this->translator,
            'dateFrom'   => new DateTime(),
            'dateTo'     => new DateTime(),
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

        $this->hitRepository->expects($this->exactly(2))
            ->method('getMostVisited')
            ->willReturn(['a', 'b', 'c']);

        $this->hitRepository->expects($this->once())
            ->method('getReferers')
            ->willReturn(['a', 'b', 'c']);

        $this->hitRepository->expects($this->once())
            ->method('getDwellTimeLabels')
            ->willReturn(
                [
                    [
                        'from'  => new DateTime(),
                        'till'  => new DateTime(),
                        'label' => 'My Chart',
                    ],
                ]
            );

        $this->subscriber->onReportGraphGenerate($mockEvent);
    }
}
