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
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ReportExporterTest extends \PHPUnit\Framework\TestCase
{
    public function testProcessExport()
    {
        $batchSize = 2;

        /** @var CoreParametersHelper|MockObject $coreParametersHelper */
        $coreParametersHelper = $this->createMock(CoreParametersHelper::class);

        /** @var ScheduleModel|MockObject $schedulerModel */
        $schedulerModel = $this->createMock(ScheduleModel::class);

        /** @var ReportDataAdapter|MockObject $reportDataAdapter */
        $reportDataAdapter = $this->createMock(ReportDataAdapter::class);

        $coreParametersHelper->expects($this->once())
            ->method('get')
            ->with('report_export_batch_size')
            ->willReturn($batchSize);

        $reportExportOptions = new ReportExportOptions($coreParametersHelper);

        /** @var ReportFileWriter|MockObject $reportFileWriter */
        $reportFileWriter = $this->createMock(ReportFileWriter::class);

        /** @var EventDispatcherInterface|MockObject $eventDispatcher */
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $exportOption     = new ExportOption(null);
        $reportDataResult = new ReportDataResult(Fixtures::getValidReportResult());
        $report1          = new Report();
        $report2          = new Report();
        $report3          = new Report();
        $reportNow        = new Report();
        $scheduler1       = new Scheduler($report1, new \DateTime());
        $scheduler2       = new Scheduler($report2, new \DateTime());
        $scheduler3       = new Scheduler($report3, new \DateTime());
        $schedulerNow     = new Scheduler($reportNow, new \DateTime());

        $report3->setIsPublished(false);
        $reportNow->setScheduleUnit(SchedulerEnum::UNIT_NOW);

        $schedulerModel->expects($this->once())
            ->method('getScheduledReportsForExport')
            ->with($exportOption)
            ->willReturn([
                $scheduler1,
                $scheduler2,
                $scheduler3,
                $schedulerNow,
            ]);

        $schedulerModel->expects($this->once())
            ->method('turnOffScheduler')
            ->with($reportNow);

        /*
         * $reportDataResult->getData() has 11 results
         * Batch size is 2 -> report will be processed 6 times (last process takes only 1 result)
         * We have 2 scheduler = 3 report => 6 * 3 = 18 calls of getReportData
         * If test fails here, check content of $reportDataResult->getData() and follow the calculation
         */
        $reportDataAdapter->expects($this->exactly(18))
            ->method('getReportData')
            ->willReturn($reportDataResult);

        $reportFileWriter->expects($this->exactly(18))
            ->method('writeReportData');

        $reportFileWriter->expects($this->exactly(3))
            ->method('getFilePath')
            ->willReturn('my-path');

        $eventDispatcher->expects($this->exactly(3))
            ->method('dispatch')
            ->with(ReportEvents::REPORT_SCHEDULE_SEND);

        $schedulerModel->expects($this->exactly(4))
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
