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
use Mautic\ReportBundle\Crate\ReportDataResult;
use Mautic\ReportBundle\Entity\Report;
use Mautic\ReportBundle\Entity\Scheduler;
use Mautic\ReportBundle\Model\CsvExporter;
use Mautic\ReportBundle\Model\ExportHandler;
use Mautic\ReportBundle\Model\ReportExportOptions;
use Mautic\ReportBundle\Model\ReportFileWriter;
use Mautic\ReportBundle\Tests\Fixtures;

class ReportFileWriterTest extends \PHPUnit_Framework_TestCase
{
    public function testWriteReportData()
    {
        $csvExporter = $this->getMockBuilder(CsvExporter::class)
            ->disableOriginalConstructor()
            ->getMock();

        $exportHandler = $this->getMockBuilder(ExportHandler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $handler = 'Handler';

        $report    = new Report();
        $scheduler = new Scheduler($report, new \DateTime());

        $reportDataResult = new ReportDataResult(Fixtures::getValidReportResult());

        $coreParametersHelper = $this->getMockBuilder(CoreParametersHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $coreParametersHelper->expects($this->once())
            ->method('getParameter')
            ->with('report_export_batch_size')
            ->willReturn(3);

        $reportExportOptions = new ReportExportOptions($coreParametersHelper);

        $exportHandler->expects($this->once())
            ->method('getHandler')
            ->willReturn($handler);

        $csvExporter->expects($this->once())
            ->method('export')
            ->with($reportDataResult, $handler, 1)
            ->willReturn($handler);

        $exportHandler->expects($this->once())
            ->method('closeHandler')
            ->willReturn($handler);

        $reportFileWriter = new ReportFileWriter($csvExporter, $exportHandler);

        $reportFileWriter->writeReportData($scheduler, $reportDataResult, $reportExportOptions);
    }

    public function testClear()
    {
        $csvExporter = $this->getMockBuilder(CsvExporter::class)
            ->disableOriginalConstructor()
            ->getMock();

        $exportHandler = $this->getMockBuilder(ExportHandler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $report    = new Report();
        $scheduler = new Scheduler($report, new \DateTime());

        $exportHandler->expects($this->once())
            ->method('removeFile');

        $reportFileWriter = new ReportFileWriter($csvExporter, $exportHandler);

        $reportFileWriter->clear($scheduler);
    }

    public function testGetFilePath()
    {
        $csvExporter = $this->getMockBuilder(CsvExporter::class)
            ->disableOriginalConstructor()
            ->getMock();

        $exportHandler = $this->getMockBuilder(ExportHandler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $report    = new Report();
        $scheduler = new Scheduler($report, new \DateTime());

        $exportHandler->expects($this->once())
            ->method('getPath');

        $reportFileWriter = new ReportFileWriter($csvExporter, $exportHandler);

        $reportFileWriter->getFilePath($scheduler);
    }
}
