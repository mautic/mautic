<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ReportBundle\Tests\Model;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\ReportBundle\Adapter\ReportDataAdapter;
use Mautic\ReportBundle\Crate\ReportDataResult;
use Mautic\ReportBundle\Entity\Report;
use Mautic\ReportBundle\Entity\Scheduler;
use Mautic\ReportBundle\Model\ReportExporter;
use Mautic\ReportBundle\Model\ReportExportOptions;
use Mautic\ReportBundle\Model\ReportFileWriter;
use Mautic\ReportBundle\Model\ScheduleModel;
use Mautic\ReportBundle\Scheduler\Option\ExportOption;
use Mautic\ReportBundle\Tests\Fixtures;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ReportExporterTest extends \PHPUnit_Framework_TestCase
{
    public function testProcessExport()
    {
        $exportOption = new ExportOption(null);

        $reportDataResult = new ReportDataResult(Fixtures::getValidReportResult());

        $report1    = new Report();
        $report2    = new Report();
        $scheduler1 = new Scheduler($report1, new \DateTime());
        $scheduler2 = new Scheduler($report2, new \DateTime());
        $schedulers = [
            $scheduler1,
            $scheduler2,
        ];

        $coreParametersHelper = $this->getMockBuilder(CoreParametersHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $coreParametersHelper->expects($this->once())
            ->method('getParameter')
            ->with('report_export_batch_size')
            ->willReturn(2); //Batch size

        $schedulerModel = $this->getMockBuilder(ScheduleModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $reportDataAdapter = $this->getMockBuilder(ReportDataAdapter::class)
            ->disableOriginalConstructor()
            ->getMock();

        $reportExportOptions = new ReportExportOptions($coreParametersHelper);

        $reportFileWriter = $this->getMockBuilder(ReportFileWriter::class)
            ->disableOriginalConstructor()
            ->getMock();

        $eventDispatcher = $this->getMockBuilder(EventDispatcherInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $schedulerModel->expects($this->once())
            ->method('getScheduledReportsForExport')
            ->with($exportOption)
            ->willReturn($schedulers);

        /*
         * $reportDataResult->getData() has 11 results
         * Batch size is 2 -> report will be processed 6 times (last process takes only 1 result)
         * We have 2 scheduler = 2 report => 6 * 2 = 12 calls of getReportData
         * If test fails here, check content of $reportDataResult->getData() and follow the calculation
         */
        $reportDataAdapter->expects($this->exactly(12))
            ->method('getReportData')
            ->willReturn($reportDataResult);

        $reportFileWriter->expects($this->exactly(12))
            ->method('writeReportData');

        $reportFileWriter->expects($this->exactly(2))
            ->method('getFilePath')
            ->willReturn('my-path');

        $eventDispatcher->expects($this->exactly(2))
            ->method('dispatch');

        $reportFileWriter->expects($this->exactly(2))
            ->method('clear');

        $schedulerModel->expects($this->exactly(2))
            ->method('reportWasScheduled');

        $reportExporter = new ReportExporter($schedulerModel, $reportDataAdapter, $reportExportOptions, $reportFileWriter, $eventDispatcher);

        $reportExporter->processExport($exportOption);
    }
}
