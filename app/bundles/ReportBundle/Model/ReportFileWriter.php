<?php

namespace Mautic\ReportBundle\Model;

use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\ReportBundle\Crate\ReportDataResult;
use Mautic\ReportBundle\Entity\Scheduler;
use Mautic\ReportBundle\Exception\FileIOException;

class ReportFileWriter
{
    /**
     * @var ExcelExporter
     */
    private $excelExporter;

    /**
     * @var CsvExporter
     */
    private $csvExporter;

    /**
     * @var ExportHandler
     */
    private $exportHandler;

    public function __construct(CsvExporter $csvExporter, ExcelExporter $excelExporter, ExportHandler $exportHandler)
    {
        $this->exportHandler     = $exportHandler;
        $this->csvExporter       = $csvExporter;
        $this->excelExporter     = $excelExporter;
    }

    /**
     * @throws FileIOException
     */
    public function writeReportData(Scheduler $scheduler, ReportDataResult $reportDataResult, ReportExportOptions $reportExportOptions)
    {
        switch ($scheduler->getReport()->getScheduleFormat()) {
          case 'csv':
            $this->exportCsv($scheduler, $reportDataResult, $reportExportOptions);
            break;
          case 'xlsx':
            $filePath = $this->getFilePath($scheduler);
            $name     = $this->getName($scheduler, $reportExportOptions);
            $this->excelExporter->export($reportDataResult, $name, $filePath);
            break;
        }
    }

    public function clear(Scheduler $scheduler)
    {
        $fileName = $this->getFileName($scheduler);
        $this->exportHandler->removeFile($fileName);
    }

    /**
     * @return string
     *
     * @throws FileIOException
     */
    public function getFilePath(Scheduler $scheduler)
    {
        $fileName = $this->getFileName($scheduler);

        return $this->exportHandler->getPath($fileName);
    }

    /**
     * @return string
     */
    private function getFileName(Scheduler $scheduler)
    {
        $date       = $scheduler->getScheduleDate();
        $dateString = $date->format('Y-m-d');
        $reportName = $scheduler->getReport()->getName();

        return $dateString.'_'.InputHelper::alphanum($reportName, false, '-').'.'.$this->getSuffix($scheduler);
    }

    private function exportCsv(Scheduler $scheduler, ReportDataResult $reportDataResult, ReportExportOptions $reportExportOptions): void
    {
        $fileName = $this->getFileName($scheduler);
        $handler  = $this->exportHandler->getHandler($fileName);
        $this->csvExporter->export($reportDataResult, $handler, $reportExportOptions->getPage());
        $this->exportHandler->closeHandler($handler);
    }

    private function getSuffix(Scheduler $scheduler): ?string
    {
        return $scheduler->getReport()->getScheduleFormat();
    }

    private function getName(Scheduler $scheduler, ReportExportOptions $reportExportOptions): string
    {
        $parts      = [$scheduler->getReport()->getName()];
        $date_parts = [$reportExportOptions->getDateFrom()->format('Y-m-d')];
        if ($reportExportOptions->getDateFrom()->format('Y-m-d') != $reportExportOptions->getDateTo()->format('Y-m-d')) {
            $date_parts[] = $reportExportOptions->getDateTo()->format('Y-m-d');
        }
        $parts[] = implode(' - ', $date_parts);

        return implode(' ', $parts);
    }
}
