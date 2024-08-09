<?php

namespace Mautic\ReportBundle\Model;

use Mautic\ReportBundle\Adapter\ReportDataAdapter;
use Mautic\ReportBundle\Entity\Scheduler;
use Mautic\ReportBundle\Event\ReportScheduleSendEvent;
use Mautic\ReportBundle\Exception\FileIOException;
use Mautic\ReportBundle\ReportEvents;
use Mautic\ReportBundle\Scheduler\Enum\SchedulerEnum;
use Mautic\ReportBundle\Scheduler\Option\ExportOption;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ReportExporter
{
    public function __construct(
        private ScheduleModel $schedulerModel,
        private ReportDataAdapter $reportDataAdapter,
        private ReportExportOptions $reportExportOptions,
        private ReportFileWriter $reportFileWriter,
        private EventDispatcherInterface $eventDispatcher
    ) {
    }

    /**
     * @throws FileIOException
     */
    public function processExport(ExportOption $exportOption): void
    {
        $schedulers = $this->schedulerModel->getScheduledReportsForExport($exportOption);
        foreach ($schedulers as $scheduler) {
            $this->processReport($scheduler);
        }
    }

    /**
     * @throws FileIOException
     */
    private function processReport(Scheduler $scheduler): void
    {
        $report = $scheduler->getReport();

        $dateTo = clone $scheduler->getScheduleDate();
        $dateTo->setTime(0, 0, 0);

        $dateFrom = clone $dateTo;
        switch ($report->getScheduleUnit()) {
            case SchedulerEnum::UNIT_NOW:
                $dateFrom->sub(new \DateInterval('P10Y'));
                $this->schedulerModel->turnOffScheduler($report);
                break;
            case SchedulerEnum::UNIT_DAILY:
                $dateFrom->sub(new \DateInterval('P1D'));
                break;
            case SchedulerEnum::UNIT_WEEKLY:
                $dateFrom->sub(new \DateInterval('P7D'));
                break;
            case SchedulerEnum::UNIT_MONTHLY:
                $dateFrom->sub(new \DateInterval('P1M'));
                break;
        }

        $this->reportExportOptions->setDateFrom($dateFrom);
        $this->reportExportOptions->setDateTo($dateTo->sub(new \DateInterval('PT1S')));

        // just published reports, but schedule continue
        if ($report->isPublished()) {
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

            $file  = $this->reportFileWriter->getFilePath($scheduler);
            $event = new ReportScheduleSendEvent($scheduler, $file);
            $this->eventDispatcher->dispatch($event, ReportEvents::REPORT_SCHEDULE_SEND);
        }

        $this->schedulerModel->reportWasScheduled($report);
    }
}
