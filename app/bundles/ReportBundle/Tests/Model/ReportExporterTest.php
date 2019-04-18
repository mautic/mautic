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
use Mautic\ReportBundle\ReportEvents;
use Mautic\ReportBundle\Scheduler\Enum\SchedulerEnum;
use Mautic\ReportBundle\Scheduler\Option\ExportOption;
use Mautic\ReportBundle\Tests\Fixtures;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ReportExporterTest extends \PHPUnit_Framework_TestCase
{
    public function testProcessExport()
    {
        $batchSize            = 3;
        $exportOption         = new ExportOption(null);
        $reportDataResult     = new ReportDataResult(Fixtures::getValidReportResult());
        $coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $report1              = new Report();
        $report2              = new Report();
        $reportNow            = new Report();
        $scheduler1           = new Scheduler($report1, new \DateTime());
        $scheduler2           = new Scheduler($report2, new \DateTime());
        $schedulerNow         = new Scheduler($reportNow, new \DateTime());
        $schedulers           = [
            $scheduler1,
            $scheduler2,
            $schedulerNow,
        ];

        $reportNow->setScheduleUnit(SchedulerEnum::UNIT_NOW);

        $coreParametersHelper->expects($this->once())
            ->method('getParameter')
            ->with('report_export_batch_size')
            ->willReturn($batchSize);

        $schedulerModel      = $this->createMock(ScheduleModel::class);
        $reportDataAdapter   = $this->createMock(ReportDataAdapter::class);
        $reportExportOptions = new ReportExportOptions($coreParametersHelper);
        $reportFileWriter    = $this->createMock(ReportFileWriter::class);
        $eventDispatcher     = $this->createMock(EventDispatcherInterface::class);

        $schedulerModel->expects($this->once())
            ->method('getScheduledReportsForExport')
            ->with($exportOption)
            ->willReturn($schedulers);

        $schedulerModel->expects($this->once())
            ->method('turnOffScheduler')
            ->with($reportNow);

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

        $reportFileWriter->expects($this->exactly($batchSize))
            ->method('getFilePath')
            ->willReturn('my-path');

        $eventDispatcher->expects($this->exactly($batchSize))
            ->method('dispatch')
            ->with(ReportEvents::REPORT_SCHEDULE_SEND);

        $schedulerModel->expects($this->exactly($batchSize))
            ->method('reportWasScheduled');

        $reportExporter = new ReportExporter(
            $schedulerModel,
            $reportDataAdapter,
            $reportExportOptions,
            $reportFileWriter,
            $eventDispatcher
        );

        $reportExporter->processExport($exportOption);
    }
}
