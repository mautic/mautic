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
            $fileName = $this->getFileName($scheduler);
            $handler  = $this->exportHandler->getHandler($fileName);
            $this->csvExporter->export($reportDataResult, $handler, $reportExportOptions->getPage());
            $this->exportHandler->closeHandler($handler);
            break;
          case 'xlsx':
            $filePath = $this->getFilePath($scheduler);
            $name     = $this->getName($scheduler);
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
     * @throws FileIOException
     */
    public function getFilePath(Scheduler $scheduler): string
    {
        $fileName = $this->getFileName($scheduler);

        return $this->exportHandler->getPath($fileName);
    }

    private function getName(Scheduler $scheduler): string
    {
        $date       = $scheduler->getScheduleDate();
        $dateString = $date->format('Y-m-d');
        $reportName = $scheduler->getReport()->getName();

        return $dateString.'_'.InputHelper::alphanum($reportName, false, '-');
    }

    private function getFileName(Scheduler $scheduler): string
    {
        return $this->getName($scheduler).'.'.$this->getSuffix($scheduler);
    }

    private function getSuffix(Scheduler $scheduler): ?string
    {
        return $scheduler->getReport()->getScheduleFormat();
    }
}
