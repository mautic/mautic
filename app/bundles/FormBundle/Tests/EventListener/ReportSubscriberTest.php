<?php

namespace Mautic\FormBundle\Tests\EventListener;

use Doctrine\DBAL\Query\QueryBuilder;
use Mautic\CoreBundle\Helper\Chart\ChartQuery;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Test\AbstractMauticTestCase;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\FormBundle\Entity\Form;
use Mautic\FormBundle\Entity\FormRepository;
use Mautic\FormBundle\Entity\SubmissionRepository;
use Mautic\FormBundle\EventListener\ReportSubscriber;
use Mautic\LeadBundle\Model\CompanyReportData;
use Mautic\ReportBundle\Event\ReportBuilderEvent;
use Mautic\ReportBundle\Event\ReportGeneratorEvent;
use Mautic\ReportBundle\Event\ReportGraphEvent;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Contracts\Translation\TranslatorInterface;

class ReportSubscriberTest extends AbstractMauticTestCase
{
    /**
     * @var CompanyReportData|MockObject
     */
    private $companyReportData;

    /**
     * @var SubmissionRepository|MockObject
     */
    private $submissionRepository;

    /**
     * @var FormRepository|MockObject
     */
    private $formRepository;

    /**
     * @var CoreParametersHelper|MockObject
     */
    private $coreParametersHelper;

    /**
     * @var TranslatorInterface|MockObject
     */
    private $translator;

    /**
     * @var ReportSubscriber
     */
    private $subscriber;

    public function setUp(): void
    {
        $this->configParams['form_results_data_sources'] = true;

        parent::setUp();

        $this->companyReportData    = $this->createMock(CompanyReportData::class);
        $this->submissionRepository = $this->createMock(SubmissionRepository::class);
        $this->formRepository       = $this->createMock(FormRepository::class);
        $this->coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $this->translator           = $this->createMock(TranslatorInterface::class);
        $this->subscriber           = new ReportSubscriber(
            $this->companyReportData,
            $this->submissionRepository,
            $this->formRepository,
            $this->coreParametersHelper,
            $this->translator
        );
    }

    public function testOnReportBuilderAddsFormAndFormSubmissionReports(): void
    {
        $mockEvent = $this->getMockBuilder(ReportBuilderEvent::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'checkContext',
                'addGraph',
                'getStandardColumns',
                'getCategoryColumns',
                'getCampaignByChannelColumns',
                'getLeadColumns',
                'getIpColumn',
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
            ->method('getLeadColumns')
            ->willReturn([]);

        $mockEvent->expects($this->once())
            ->method('getIpColumn')
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

        $mockEvent->expects($this->exactly(3))
            ->method('addGraph')
            ->willReturnCallback(function () use (&$setGraphs) {
                $args = func_get_args();

                $setGraphs[] = $args;
            });

        $form = $this->getMockBuilder(Form::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'getFields',
            ])
            ->getMock();

        $form->expects($this->once())
            ->method('getFields')
            ->willReturn([]);

        $this->formRepository->expects($this->once())
            ->method('getEntities')
            ->willReturn([[$form, 1]]);

        $this->companyReportData->expects($this->exactly(2))
            ->method('getCompanyData')
            ->with()
            ->willReturn([]);

        $this->subscriber->onReportBuilder($mockEvent);

        $this->assertCount(3, $setTables);
        $this->assertCount(3, $setGraphs);
    }

    public function testOnReportGenerateFormsContext(): void
    {
        $mockQueryBuilder = $this->createMock(QueryBuilder::class);
        $mockEvent        = $this->getMockBuilder(ReportGeneratorEvent::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'getContext',
                'getQueryBuilder',
                'addCategoryLeftJoin',
                'setQueryBuilder',
            ])
            ->getMock();

        $mockQueryBuilder->expects($this->once())
            ->method('from')
            ->willReturn($mockQueryBuilder);

        $mockEvent->expects($this->once())
            ->method('getQueryBuilder')
            ->willReturn($mockQueryBuilder);

        $mockEvent->expects($this->once())
            ->method('getContext')
            ->willReturn('forms');

        $this->subscriber->onReportGenerate($mockEvent);
    }

    public function testOnReportGenerateFormSubmissionContext(): void
    {
        $mockQueryBuilder = $this->createMock(QueryBuilder::class);
        $mockEvent        = $this->getMockBuilder(ReportGeneratorEvent::class)
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
            ])
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
            ->willReturn('form.submissions');

        $this->subscriber->onReportGenerate($mockEvent);
    }

    public function testOnReportGenerateFormResultsContext(): void
    {
        $mockQueryBuilder = $this->createMock(QueryBuilder::class);
        $mockEvent        = $this->getMockBuilder(ReportGeneratorEvent::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'getContext',
                'getQueryBuilder',
                'addLeadLeftJoin',
                'setQueryBuilder',
            ])
            ->getMock();

        $mockQueryBuilder->expects($this->once())
            ->method('from')
            ->willReturn($mockQueryBuilder);

        $mockQueryBuilder->expects($this->once())
            ->method('leftJoin')
            ->willReturn($mockQueryBuilder);

        $mockEvent->expects($this->once())
            ->method('getQueryBuilder')
            ->willReturn($mockQueryBuilder);

        $mockEvent->expects($this->once())
            ->method('getContext')
            ->willReturn('form.results');

        $this->subscriber->onReportGenerate($mockEvent);
    }

    public function testOnReportGraphGenerateBadContextWillReturn(): void
    {
        $mockEvent = $this->createMock(ReportGraphEvent::class);

        $mockEvent->expects($this->once())
            ->method('checkContext')
            ->willReturn(false);

        $mockEvent->expects($this->never())
            ->method('getRequestedGraphs');

        $this->subscriber->onReportGraphGenerate($mockEvent);
    }

    public function testOnReportGraphGenerate(): void
    {
        $mockEvent        = $this->createMock(ReportGraphEvent::class);
        $mockTrans        = $this->createMock(Translator::class);
        $mockQueryBuilder = $this->createMock(QueryBuilder::class);
        $mockChartQuery   = $this->createMock(ChartQuery::class);

        $mockTrans->expects($this->any())
            ->method('trans')
            ->willReturnArgument(0);

        $mockEvent->expects($this->once())
            ->method('getQueryBuilder')
            ->willReturn($mockQueryBuilder);

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
                    'mautic.form.graph.line.submissions',
                    'mautic.form.table.top.referrers',
                    'mautic.form.table.most.submitted',
                ]
            );

        $this->submissionRepository->expects($this->once())
            ->method('getTopReferrers')
            ->willReturn(['a', 'b', 'c']);

        $this->submissionRepository->expects($this->once())
            ->method('getMostSubmitted')
            ->willReturn(['a', 'b', 'c']);

        $this->subscriber->onReportGraphGenerate($mockEvent);
    }
}
