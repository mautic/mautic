<?php

namespace Mautic\FormBundle\Tests\EventListener;

use Doctrine\DBAL\Query\QueryBuilder;
use Mautic\ChannelBundle\Helper\ChannelListHelper;
use Mautic\CoreBundle\Helper\Chart\ChartQuery;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Test\AbstractMauticTestCase;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\FormBundle\Entity\Field;
use Mautic\FormBundle\Entity\Form;
use Mautic\FormBundle\Entity\FormRepository;
use Mautic\FormBundle\Entity\SubmissionRepository;
use Mautic\FormBundle\EventListener\ReportSubscriber;
use Mautic\FormBundle\Model\FormModel;
use Mautic\LeadBundle\Model\CompanyReportData;
use Mautic\ReportBundle\Event\ReportBuilderEvent;
use Mautic\ReportBundle\Event\ReportGeneratorEvent;
use Mautic\ReportBundle\Event\ReportGraphEvent;
use Mautic\ReportBundle\Helper\ReportHelper;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Contracts\Translation\TranslatorInterface;

class ReportSubscriberTest extends AbstractMauticTestCase
{
    /**
     * @var CompanyReportData|MockObject
     */
    private MockObject $companyReportData;

    /**
     * @var SubmissionRepository|MockObject
     */
    private MockObject $submissionRepository;

    /**
     * @var FormModel|MockObject
     */
    private MockObject $formModel;

    /**
     * @var FormRepository|MockObject
     */
    private MockObject $formRepository;

    private ReportHelper $reportHelper;

    /**
     * @var CoreParametersHelper|MockObject
     */
    private MockObject $coreParametersHelper;

    /**
     * @var TranslatorInterface|MockObject
     */
    private MockObject $translator;

    private ReportSubscriber $subscriber;

    public function setUp(): void
    {
        $this->configParams['form_results_data_sources'] = true;

        parent::setUp();

        $this->companyReportData    = $this->createMock(CompanyReportData::class);
        $this->submissionRepository = $this->createMock(SubmissionRepository::class);
        $this->formModel            = $this->createMock(FormModel::class);
        $this->formRepository       = $this->createMock(FormRepository::class);
        $this->reportHelper         = new ReportHelper($this->createMock(EventDispatcher::class));
        $this->coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $this->translator           = $this->createMock(TranslatorInterface::class);
        $this->subscriber           = new ReportSubscriber(
            $this->companyReportData,
            $this->submissionRepository,
            $this->formModel,
            $this->reportHelper,
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

        $mockEvent->expects($this->once())
            ->method('getLeadColumns')
            ->willReturn([]);

        $mockEvent->expects($this->once())
            ->method('getIpColumn')
            ->willReturn([]);

        $mockEvent->expects($this->exactly(3))
            ->method('checkContext')
            ->willReturnOnConsecutiveCalls(true, true, false);

        $setTables = [];
        $setGraphs = [];

        $mockEvent->expects($this->exactly(2))
            ->method('addTable')
            ->willReturnCallback(function () use (&$setTables): void {
                $args = func_get_args();

                $setTables[] = $args;
            });

        $mockEvent->expects($this->exactly(3))
            ->method('addGraph')
            ->willReturnCallback(function () use (&$setGraphs): void {
                $args = func_get_args();

                $setGraphs[] = $args;
            });

        $this->companyReportData->expects($this->once())
            ->method('getCompanyData')
            ->with()
            ->willReturn([]);

        $this->subscriber->onReportBuilder($mockEvent);

        $this->assertCount(2, $setTables);
        $this->assertCount(3, $setGraphs);
    }

    public function testOnReportBuilderWithWrongContext(): void
    {
        $reportBuilderEvent = new ReportBuilderEvent(
            $this->translator,
            $this->createMock(ChannelListHelper::class),
            'test',
            [],
            $this->reportHelper,
            ''
        );

        $this->subscriber->onReportBuilder($reportBuilderEvent);

        Assert::assertCount(0, $reportBuilderEvent->getTables());
    }

    public function testOnReportBuilderAddsFormAndFormResultReports(): void
    {
        $reportBuilderEvent = new ReportBuilderEvent(
            $this->translator,
            $this->createMock(ChannelListHelper::class),
            ReportSubscriber::CONTEXT_FORM_RESULT,
            [],
            $this->reportHelper,
            ''
        );

        $field = new Field();
        $field->setAlias('email');
        $field->setType('string');
        $field->setLabel('Email');
        $field->setMappedObject('contact');
        $field->setMappedField('email');

        $form = new Form();
        $form->addField('email', $field);
        $field->setForm($form);

        $this->formModel->expects($this->once())
            ->method('getRepository')
            ->willReturn($this->formRepository);

        $this->formModel->expects($this->once())
            ->method('getCustomComponents')
            ->willReturn(['viewOnlyFields' => ['button', 'captcha', 'freetext', 'freehtml', 'pagebreak', 'plugin.loginSocial']]);

        $this->formRepository->expects($this->once())
            ->method('getEntities')
            ->willReturn([[$form, 1]]);

        $this->formRepository->expects($this->once())
            ->method('getResultsTableName')
            ->willReturn('test');

        $this->subscriber->onReportBuilder($reportBuilderEvent);

        $tables = $reportBuilderEvent->getTables();

        Assert::assertCount(2, $tables);
        Assert::assertArrayHasKey('form.results.test', $tables);
        Assert::assertCount(3, $tables['form.results.test']['columns']);
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
