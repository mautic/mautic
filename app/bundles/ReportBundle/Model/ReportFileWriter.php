<?php

namespace Mautic\ReportBundle\Model;

use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\ReportBundle\Crate\ReportDataResult;
use Mautic\ReportBundle\Entity\Scheduler;
use Mautic\ReportBundle\Exception\FileIOException;

class ReportFileWriter
{
    public function __construct(
        private CsvExporter $csvExporter,
        private ExportHandler $exportHandler,
    ) {
    }

    /**
     * @throws FileIOException
     */
    public function writeReportData(Scheduler $scheduler, ReportDataResult $reportDataResult, ReportExportOptions $reportExportOptions): void
    {
        $fileName = $this->getFileName($scheduler);
        $handler  = $this->exportHandler->getHandler($fileName);
        $this->csvExporter->export($reportDataResult, $handler, $reportExportOptions->getPage());
        $this->exportHandler->closeHandler($handler);
    }

    public function clear(Scheduler $scheduler): void
    {
        $fileName = $this->getFileName($scheduler);
        $this->exportHandler->removeFile($fileName);
    }

    /**
     * @throws FileIOException
     */
    public function getFilePath(Scheduler $scheduler): string
    {
        $fileName = $this->getFileName($scheduler);

        return $this->exportHandler->getPath($fileName);
    }

    private function getFileName(Scheduler $scheduler): string
    {
        $date       = $scheduler->getScheduleDate();
        $dateString = $date->format('Y-m-d');
        $reportName = $scheduler->getReport()->getName();

        return $dateString.'_'.InputHelper::alphanum($reportName, false, '-');
    }
}
