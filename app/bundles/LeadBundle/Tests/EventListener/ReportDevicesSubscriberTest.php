<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\EventListener;

use Doctrine\DBAL\Query\QueryBuilder;
use Mautic\ChannelBundle\Helper\ChannelListHelper;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\EventListener\ReportDevicesSubscriber;
use Mautic\LeadBundle\Model\CompanyReportData;
use Mautic\LeadBundle\Report\FieldsBuilder;
use Mautic\ReportBundle\Event\ReportBuilderEvent;
use Mautic\ReportBundle\Event\ReportGeneratorEvent;
use Mautic\ReportBundle\Helper\ReportHelper;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Contracts\Translation\TranslatorInterface;

class ReportDevicesSubscriberTest extends \PHPUnit\Framework\TestCase
{
    public function testNotRelevantContextBuilder(): void
    {
        $fieldsBuilderMock      = $this->createMock(FieldsBuilder::class);
        $companyReportDataMock  = $this->createMock(CompanyReportData::class);
        $reportBuilderEventMock = $this->createMock(ReportBuilderEvent::class);

        $reportBuilderEventMock->expects($this->once())
            ->method('checkContext')
            ->with([ReportDevicesSubscriber::DEVICES])
            ->willReturn(false);

        $reportBuilderEventMock->expects($this->never())
            ->method('addTable');

        $reportDevicesSubscriber = new ReportDevicesSubscriber($fieldsBuilderMock, $companyReportDataMock);
        $reportDevicesSubscriber->onReportBuilder($reportBuilderEventMock);
    }

    public function testNotRelevantContextGenerate(): void
    {
        $fieldsBuilderMock        = $this->createMock(FieldsBuilder::class);
        $companyReportDataMock    = $this->createMock(CompanyReportData::class);
        $reportGeneratorEventMock = $this->createMock(ReportGeneratorEvent::class);

        $reportGeneratorEventMock->expects($this->once())
            ->method('checkContext')
            ->with([ReportDevicesSubscriber::DEVICES])
            ->willReturn(false);

        $reportGeneratorEventMock->expects($this->never())
            ->method('getQueryBuilder');

        $reportDevicesSubscriber = new ReportDevicesSubscriber($fieldsBuilderMock, $companyReportDataMock);
        $reportDevicesSubscriber->onReportGenerate($reportGeneratorEventMock);
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

        $leadFilters = [
            'filter' => [
                'label' => 'second',
                'type'  => 'text',
            ],
        ];

        $fieldsBuilderMock->expects($this->once())
            ->method('getLeadFieldsColumns')
            ->with('l.')
            ->willReturn($leadColumns);

        $fieldsBuilderMock->expects($this->once())
            ->method('getLeadFilter')
            ->with('l.', 's.')
            ->willReturn($leadFilters);

        $companyReportDataMock->expects($this->once())
            ->method('getCompanyData')
            ->with()
            ->willReturn($companyColumns);

        $reportBuilderEvent = new ReportBuilderEvent($translatorMock, $channelListHelperMock, ReportDevicesSubscriber::DEVICES, [], $reportHelperMock);

        $segmentReportSubscriber = new ReportDevicesSubscriber($fieldsBuilderMock, $companyReportDataMock);
        $segmentReportSubscriber->onReportBuilder($reportBuilderEvent);

        $expected = [
            ReportDevicesSubscriber::DEVICES => [
                'display_name' => 'mautic.lead.report.devices',
                'columns'      => [
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
                    'dev.date_added' => [
                        'label' => '',
                        'type'  => 'datetime',
                        'alias' => 'date_added',
                    ],
                    'dev.client_info' => [
                        'label' => '',
                        'type'  => 'html',
                        'alias' => 'client_info',
                    ],
                    'dev.device' => [
                        'label' => '',
                        'type'  => 'text',
                        'alias' => 'device',
                    ],
                    'dev.device_os_name' => [
                        'label' => '',
                        'type'  => 'text',
                        'alias' => 'device_os_name',
                    ],
                    'dev.device_os_version' => [
                        'label' => '',
                        'type'  => 'text',
                        'alias' => 'device_os_version',
                    ],
                    'dev.device_os_platform' => [
                        'label' => '',
                        'type'  => 'text',
                        'alias' => 'device_os_platform',
                    ],
                    'dev.device_brand' => [
                        'label' => '',
                        'type'  => 'text',
                        'alias' => 'device_brand',
                    ],
                    'dev.device_model' => [
                        'label' => '',
                        'type'  => 'text',
                        'alias' => 'device_model',
                    ],
                ],
                'filters' => [
                    'filter' => [
                        'label' => '',
                        'type'  => 'text',
                        'alias' => 'filter',
                    ],
                    'dev.date_added' => [
                        'label' => '',
                        'type'  => 'datetime',
                        'alias' => 'date_added',
                    ],
                    'dev.device' => [
                        'label' => '',
                        'type'  => 'text',
                        'alias' => 'device',
                    ],
                    'dev.device_os_name' => [
                        'label' => '',
                        'type'  => 'text',
                        'alias' => 'device_os_name',
                    ],
                    'dev.device_os_version' => [
                        'label' => '',
                        'type'  => 'text',
                        'alias' => 'device_os_version',
                    ],
                    'dev.device_os_platform' => [
                        'label' => '',
                        'type'  => 'text',
                        'alias' => 'device_os_platform',
                    ],
                    'dev.device_brand' => [
                        'label' => '',
                        'type'  => 'text',
                        'alias' => 'device_brand',
                    ],
                    'dev.device_model' => [
                        'label' => '',
                        'type'  => 'text',
                        'alias' => 'device_model',
                    ],
                ],
                'group' => 'contacts',
            ],
        ];

        $actual = $reportBuilderEvent->getTables();
        $this->assertSame($expected, $actual);
    }

    public function testReportGenerate(): void
    {
        $reportGeneratorEventMock  = $this->getReportGeneratorEventMock();
        $reportDevicesSubscriber   = $this->getReportDevicesSubscriber();
        $queryBuilderMock          = $this->getQueryBuilderMock();

        $reportGeneratorEventMock->expects($this->once())
            ->method('getQueryBuilder')
            ->willReturn($queryBuilderMock);

        $reportDevicesSubscriber->onReportGenerate($reportGeneratorEventMock);
    }

    private function getReportDevicesSubscriber(): ReportDevicesSubscriber
    {
        $fieldsBuilderMock      = $this->createMock(FieldsBuilder::class);
        $companyReportDataMock  = $this->createMock(CompanyReportData::class);

        return new ReportDevicesSubscriber($fieldsBuilderMock, $companyReportDataMock);
    }

    /**
     * @return ReportGeneratorEvent|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getReportGeneratorEventMock()
    {
        $reportGeneratorEventMock = $this->createMock(ReportGeneratorEvent::class);

        $reportGeneratorEventMock->expects($this->once())
            ->method('checkContext')
            ->with([ReportDevicesSubscriber::DEVICES])
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
            ->with(MAUTIC_TABLE_PREFIX.'lead_devices', 'dev')
            ->willReturn($queryBuilderMock);

        $queryBuilderMock->method('leftJoin')
            ->withConsecutive(['dev', MAUTIC_TABLE_PREFIX.'leads', 'l', 'l.id = dev.lead_id'])
            ->willReturn($queryBuilderMock);

        return $queryBuilderMock;
    }
}
