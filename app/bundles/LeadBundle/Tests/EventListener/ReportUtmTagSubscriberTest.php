<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\EventListener;

use Doctrine\DBAL\Query\QueryBuilder;
use Mautic\ChannelBundle\Helper\ChannelListHelper;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\EventListener\ReportUtmTagSubscriber;
use Mautic\LeadBundle\Model\CompanyReportData;
use Mautic\LeadBundle\Report\FieldsBuilder;
use Mautic\ReportBundle\Event\ReportBuilderEvent;
use Mautic\ReportBundle\Event\ReportGeneratorEvent;
use Mautic\ReportBundle\Helper\ReportHelper;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Contracts\Translation\TranslatorInterface;

class ReportUtmTagSubscriberTest extends \PHPUnit\Framework\TestCase
{
    public function testNotRelevantContextBuilder(): void
    {
        $fieldsBuilderMock      = $this->createMock(FieldsBuilder::class);
        $companyReportDataMock  = $this->createMock(CompanyReportData::class);
        $reportBuilderEventMock = $this->createMock(ReportBuilderEvent::class);

        $reportBuilderEventMock->expects($this->once())
            ->method('checkContext')
            ->with(['lead.utmTag'])
            ->willReturn(false);

        $reportBuilderEventMock->expects($this->never())
            ->method('addTable');

        $reportUtmTagSubscriber = new ReportUtmTagSubscriber($fieldsBuilderMock, $companyReportDataMock);
        $reportUtmTagSubscriber->onReportBuilder($reportBuilderEventMock);
    }

    public function testNotRelevantContextGenerate(): void
    {
        $fieldsBuilderMock        = $this->createMock(FieldsBuilder::class);
        $companyReportDataMock    = $this->createMock(CompanyReportData::class);
        $reportGeneratorEventMock = $this->createMock(ReportGeneratorEvent::class);

        $reportGeneratorEventMock->expects($this->once())
            ->method('checkContext')
            ->with(['lead.utmTag'])
            ->willReturn(false);

        $reportGeneratorEventMock->expects($this->never())
            ->method('getQueryBuilder');

        $reportUtmTagSubscriber = new ReportUtmTagSubscriber($fieldsBuilderMock, $companyReportDataMock);
        $reportUtmTagSubscriber->onReportGenerate($reportGeneratorEventMock);
    }

    public function testReportBuilder(): void
    {
        $translatorMock        = $this->createMock(TranslatorInterface::class);
        $channelListHelperMock = new ChannelListHelper($this->createMock(EventDispatcher::class), $this->createMock(Translator::class));
        $reportHelperMock      = new ReportHelper($this->createMock(EventDispatcher::class));
        $fieldsBuilderMock     = $this->createMock(FieldsBuilder::class);
        $companyReportDataMock = $this->createMock(CompanyReportData::class);

        $leadColumns = [
            'lead.name' => [
                'label' => 'lead name',
                'type'  => 'bool',
            ],
        ];
        $companyColumns = [
            'comp.name' => [
                'label' => 'company name',
                'type'  => 'bool',
            ],
        ];

        $fieldsBuilderMock->expects($this->once())
            ->method('getLeadFieldsColumns')
            ->with('l.')
            ->willReturn($leadColumns);

        $fieldsBuilderMock
            ->expects($this->any())
            ->method('getLeadFilter')
            ->willReturn([
                'tag' => [
                    'label'     => 'mautic.core.filter.tags',
                    'type'      => 'multiselect',
                    'list'      => ['A', 'B', 'C'],
                    'operators' => [
                        'in'       => 'mautic.core.operator.in',
                        'notIn'    => 'mautic.core.operator.notin',
                        'empty'    => 'mautic.core.operator.isempty',
                        'notEmpty' => 'mautic.core.operator.isnotempty',
                    ],
                ],
            ]);

        $companyReportDataMock->expects($this->once())
            ->method('getCompanyData')
            ->with()
            ->willReturn($companyColumns);

        $reportBuilderEvent = new ReportBuilderEvent($translatorMock, $channelListHelperMock, 'lead.utmTag', [], $reportHelperMock);

        $segmentReportSubscriber = new ReportUtmTagSubscriber($fieldsBuilderMock, $companyReportDataMock);
        $segmentReportSubscriber->onReportBuilder($reportBuilderEvent);

        $expectedColumns = [
            'lead.name' => [
                'label' => '',
                'type'  => 'bool',
                'alias' => 'name',
            ],
            'comp.name' => [
                'label' => '',
                'type'  => 'bool',
                'alias' => 'name',
            ],
            'utm.utm_campaign' => [
                'label' => '',
                'type'  => 'text',
                'alias' => 'utm_campaign',
            ],
            'utm.utm_content' => [
                'label' => '',
                'type'  => 'text',
                'alias' => 'utm_content',
            ],
            'utm.utm_medium' => [
                'label' => '',
                'type'  => 'text',
                'alias' => 'utm_medium',
            ],
            'utm.utm_source' => [
                'label' => '',
                'type'  => 'text',
                'alias' => 'utm_source',
            ],
            'utm.utm_term' => [
                'label' => '',
                'type'  => 'text',
                'alias' => 'utm_term',
            ],
        ];

        $expected = [
            'lead.utmTag' => [
                'display_name' => 'mautic.lead.report.utm.utm_tag',
                'columns'      => $expectedColumns,
                'filters'      => array_merge($expectedColumns, [
                    'tag' => [
                        'label'     => '',
                        'type'      => 'multiselect',
                        'list'      => ['A', 'B', 'C'],
                        'operators' => [
                            'in'       => 'mautic.core.operator.in',
                            'notIn'    => 'mautic.core.operator.notin',
                            'empty'    => 'mautic.core.operator.isempty',
                            'notEmpty' => 'mautic.core.operator.isnotempty',
                        ],
                        'alias' => 'tag',
                    ],
                ]),
                'group'   => 'contacts',
            ],
        ];

        $this->assertSame($expected, $reportBuilderEvent->getTables());
    }

    public function testReportGenerateNoJoinedTables(): void
    {
        $reportGeneratorEventMock = $this->getReportGeneratorEventMock();
        $reportUtmTagSubscriber   = $this->getReportUtmTagSubscriber();
        $queryBuilderMock         = $this->getQueryBuilderMock();

        $reportGeneratorEventMock->expects($this->once())
            ->method('getQueryBuilder')
            ->willReturn($queryBuilderMock);

        $reportUtmTagSubscriber->onReportGenerate($reportGeneratorEventMock);
    }

    public function testReportGenerateWithUsers(): void
    {
        $reportGeneratorEventMock = $this->getReportGeneratorEventMock();
        $reportUtmTagSubscriber   = $this->getReportUtmTagSubscriber();
        $queryBuilderMock         = $this->getQueryBuilderMock();

        $reportGeneratorEventMock->expects($this->once())
            ->method('getQueryBuilder')
            ->willReturn($queryBuilderMock);
        $matcher = $this->exactly(2);

        $reportGeneratorEventMock->expects($matcher)
            ->method('usesColumn')->willReturnCallback(function (...$parameters) use ($matcher) {
                if (1 === $matcher->numberOfInvocations()) {
                    $this->assertSame(['u.first_name', 'u.last_name'], $parameters[0]);
                }
                if (2 === $matcher->numberOfInvocations()) {
                    $this->assertSame('i.ip_address', $parameters[0]);
                }

                return true;
            });

        $reportUtmTagSubscriber->onReportGenerate($reportGeneratorEventMock);
    }

    private function getReportUtmTagSubscriber(): ReportUtmTagSubscriber
    {
        $fieldsBuilderMock      = $this->createMock(FieldsBuilder::class);
        $companyReportDataMock  = $this->createMock(CompanyReportData::class);
        $reportUtmTagSubscriber = new ReportUtmTagSubscriber($fieldsBuilderMock, $companyReportDataMock);

        return $reportUtmTagSubscriber;
    }

    /**
     * @return ReportGeneratorEvent|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getReportGeneratorEventMock()
    {
        $reportGeneratorEventMock = $this->createMock(ReportGeneratorEvent::class);

        $reportGeneratorEventMock->expects($this->once())
            ->method('checkContext')
            ->with(['lead.utmTag'])
            ->willReturn(true);

        return $reportGeneratorEventMock;
    }

    /**
     * @return QueryBuilder|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getQueryBuilderMock()
    {
        $queryBuilderMock = $this->createMock(QueryBuilder::class);

        $queryBuilderMock->expects($this->once())
            ->method('from')
            ->with(MAUTIC_TABLE_PREFIX.'lead_utmtags', 'utm')
            ->willReturn($queryBuilderMock);
        $matcher = $this->any();

        $queryBuilderMock->expects($matcher)->method('leftJoin')
            ->willReturnCallback(function (...$parameters) use ($matcher, $queryBuilderMock) {
                if (1 === $matcher->numberOfInvocations()) {
                    $this->assertSame('utm', $parameters[0]);
                    $this->assertSame(MAUTIC_TABLE_PREFIX.'leads', $parameters[1]);
                    $this->assertSame('l', $parameters[2]);
                    $this->assertSame('l.id = utm.lead_id', $parameters[3]);
                }

                return $queryBuilderMock;
            });

        return $queryBuilderMock;
    }
}
