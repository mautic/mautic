<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ReportBundle\Model;

use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\ReportBundle\Crate\ReportDataResult;
use Mautic\ReportBundle\Entity\Scheduler;
use Mautic\ReportBundle\Exception\FileIOException;

class ReportFileWriter
{
    /**
     * @var CsvExporter
     */
    private $csvExporter;

    /**
     * @var ExportHandler
     */
    private $exportHandler;

    public function __construct(CsvExporter $csvExporter, ExportHandler $exportHandler)
    {
        $this->csvExporter   = $csvExporter;
        $this->exportHandler = $exportHandler;
    }

    /**
     * @param Scheduler           $scheduler
     * @param ReportDataResult    $reportDataResult
     * @param ReportExportOptions $reportExportOptions
     *
     * @throws FileIOException
     */
    public function writeReportData(Scheduler $scheduler, ReportDataResult $reportDataResult, ReportExportOptions $reportExportOptions)
    {
        $fileName = $this->getFileName($scheduler);
        $handler  = $this->exportHandler->getHandler($fileName);
        $this->csvExporter->export($reportDataResult, $handler, $reportExportOptions->getPage());
        $this->exportHandler->closeHandler($handler);
    }

    public function clear(Scheduler $scheduler)
    {
        $fileName = $this->getFileName($scheduler);
        $this->exportHandler->removeFile($fileName);
    }

    /**
     * @param Scheduler $scheduler
     *
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
     * @param Scheduler $scheduler
     *
     * @return string
     */
    private function getFileName(Scheduler $scheduler)
    {
        $date       = $scheduler->getScheduleDate();
        $dateString = $date->format('Y-m-d');
        $reportName = $scheduler->getReport()->getName();

        return $dateString.'_'.InputHelper::alphanum($reportName, false, '-');
    }
}
