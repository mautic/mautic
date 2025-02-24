<?php

namespace Mautic\ReportBundle\Tests\Adapter;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\ReportBundle\Adapter\ReportDataAdapter;
use Mautic\ReportBundle\Entity\Report;
use Mautic\ReportBundle\Model\ReportExportOptions;
use Mautic\ReportBundle\Model\ReportModel;
use Mautic\ReportBundle\Tests\Fixtures;

class ReportDataAdapterTest extends \PHPUnit\Framework\TestCase
{
    public function testNoEmailsProvided(): void
    {
        $reportModelMock = $this->getMockBuilder(ReportModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $coreParametersHelperMock = $this->getMockBuilder(CoreParametersHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $coreParametersHelperMock->expects($this->once())
            ->method('get')
            ->with('report_export_batch_size')
            ->willReturn(11);

        $reportDataAdapter = new ReportDataAdapter($reportModelMock);

        $report              = new Report();
        $reportExportOptions = new ReportExportOptions($coreParametersHelperMock);

        $options = [
            'paginate'        => true,
            'limit'           => 11,
            'ignoreGraphData' => true,
            'page'            => 1,
            'dateTo'          => null,
            'dateFrom'        => null,
        ];

        $reportModelMock->expects($this->once())
            ->method('getReportData')
            ->with($report, null, $options)
            ->willReturn(Fixtures::getValidReportResult());

        $result = $reportDataAdapter->getReportData($report, $reportExportOptions);

        $this->assertSame(Fixtures::getValidReportData(), $result->getData());
        $this->assertSame(Fixtures::getValidReportHeaders(), $result->getHeaders());
        $this->assertSame(Fixtures::getValidReportTotalResult(), $result->getTotalResults());
        $this->assertSame(Fixtures::getStringType(), $result->getType('city'));
        $this->assertSame(Fixtures::getDateType(), $result->getType('date_identified'));
        $this->assertSame(Fixtures::getEmailType(), $result->getType('email'));
    }
}
