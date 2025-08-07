<?php

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

class ReportFileWriterTest extends \PHPUnit\Framework\TestCase
{
    public function testWriteReportData(): void
    {
        $csvExporter = $this->createMock(CsvExporter::class);

        $exportHandler = $this->createMock(ExportHandler::class);

        $handler = 'Handler';
        $report  = new Report();

        $report->setName('Report A');

        $scheduler = new Scheduler($report, new \DateTime());

        $reportDataResult = new ReportDataResult(Fixtures::getValidReportResult());

        $coreParametersHelper = $this->createMock(CoreParametersHelper::class);

        $coreParametersHelper->expects($this->once())
            ->method('get')
            ->with('report_export_batch_size')
            ->willReturn(3);

        $reportExportOptions = new ReportExportOptions($coreParametersHelper);

        $exportHandler->expects($this->once())
            ->method('getHandler')
            ->willReturn($handler);

        $csvExporter->expects($this->once())
            ->method('export')
            ->with($reportDataResult, $handler, 1);

        $exportHandler->expects($this->once())
            ->method('closeHandler');

        $reportFileWriter = new ReportFileWriter($csvExporter, $exportHandler);

        $reportFileWriter->writeReportData($scheduler, $reportDataResult, $reportExportOptions);
    }

    public function testClear(): void
    {
        $csvExporter = $this->createMock(CsvExporter::class);

        $exportHandler = $this->createMock(ExportHandler::class);

        $report    = new Report();
        $scheduler = new Scheduler($report, new \DateTime());

        $report->setName('Report A');

        $exportHandler->expects($this->once())
            ->method('removeFile');

        $reportFileWriter = new ReportFileWriter($csvExporter, $exportHandler);

        $reportFileWriter->clear($scheduler);
    }

    public function testGetFilePath(): void
    {
        $csvExporter = $this->createMock(CsvExporter::class);

        $exportHandler = $this->createMock(ExportHandler::class);

        $report    = new Report();
        $scheduler = new Scheduler($report, new \DateTime());

        $report->setName('Report A');

        $exportHandler->expects($this->once())
            ->method('getPath');

        $reportFileWriter = new ReportFileWriter($csvExporter, $exportHandler);

        $reportFileWriter->getFilePath($scheduler);
    }
}
