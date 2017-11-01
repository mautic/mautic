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

use Mautic\ReportBundle\Adapter\ReportDataAdapter;
use Mautic\ReportBundle\Entity\Scheduler;
use Mautic\ReportBundle\Event\ReportScheduleSendEvent;
use Mautic\ReportBundle\Exception\FileIOException;
use Mautic\ReportBundle\ReportEvents;
use Mautic\ReportBundle\Scheduler\Option\ExportOption;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ReportExporter
{
    /**
     * @var ScheduleModel
     */
    private $schedulerModel;

    /**
     * @var ReportDataAdapter
     */
    private $reportDataAdapter;

    /**
     * @var ReportExportOptions
     */
    private $reportExportOptions;

    /**
     * @var ReportFileWriter
     */
    private $reportFileWriter;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    public function __construct(
        ScheduleModel $schedulerModel,
        ReportDataAdapter $reportDataAdapter,
        ReportExportOptions $reportExportOptions,
        ReportFileWriter $reportFileWriter,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->schedulerModel      = $schedulerModel;
        $this->reportDataAdapter   = $reportDataAdapter;
        $this->reportExportOptions = $reportExportOptions;
        $this->reportFileWriter    = $reportFileWriter;
        $this->eventDispatcher     = $eventDispatcher;
    }

    /**
     * @param ExportOption $exportOption
     *
     * @throws FileIOException
     */
    public function processExport(ExportOption $exportOption)
    {
        $schedulers = $this->schedulerModel->getScheduledReportsForExport($exportOption);
        foreach ($schedulers as $scheduler) {
            $this->processReport($scheduler);
        }
    }

    /**
     * @param Scheduler $scheduler
     *
     * @throws FileIOException
     */
    private function processReport(Scheduler $scheduler)
    {
        $report = $scheduler->getReport();
        $this->reportExportOptions->beginExport();
        while (true) {
            $data = $this->reportDataAdapter->getReportData($report, $this->reportExportOptions);

            $this->reportFileWriter->writeReportData($scheduler, $data, $this->reportExportOptions);

            $totalResults = $data->getTotalResults();
            unset($data);

            if ($this->reportExportOptions->getNumberOfProcessedResults() >= $totalResults) {
                break;
            }

            $this->reportExportOptions->nextBatch();
        }

        $file = $this->reportFileWriter->getFilePath($scheduler);

        $event = new ReportScheduleSendEvent($scheduler, $file);
        $this->eventDispatcher->dispatch(ReportEvents::REPORT_SCHEDULE_SEND, $event);

        $this->reportFileWriter->clear($scheduler);
    }
}
