<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ReportBundle\Tests\Adapter;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\ReportBundle\Adapter\ReportDataAdapter;
use Mautic\ReportBundle\Entity\Report;
use Mautic\ReportBundle\Model\ReportExportOptions;
use Mautic\ReportBundle\Model\ReportModel;
use Mautic\ReportBundle\Tests\Fixures;

class ReportDataAdapterTest extends \PHPUnit_Framework_TestCase
{
    public function testNoEmailsProvided()
    {
        $reportModelMock = $this->getMockBuilder(ReportModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $coreParametersHelperMock = $this->getMockBuilder(CoreParametersHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $coreParametersHelperMock->expects($this->once())
            ->method('getParameter')
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
        ];

        $reportModelMock->expects($this->once())
            ->method('getReportData')
            ->with($report, null, $options)
            ->willReturn(Fixures::getValidReportResult());

        $result = $reportDataAdapter->getReportData($report, $reportExportOptions);

        $this->assertSame(Fixures::getValidReportData(), $result->getData());
        $this->assertSame(Fixures::getValidReportHeaders(), $result->getHeaders());
        $this->assertSame(Fixures::getValidReportTotalResult(), $result->getTotalResults());
        $this->assertSame(Fixures::getStringType(), $result->getType('city'));
        $this->assertSame(Fixures::getDateType(), $result->getType('date_identified'));
        $this->assertSame(Fixures::getEmailType(), $result->getType('email'));
    }
}
