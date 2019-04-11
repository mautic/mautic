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

use Doctrine\ORM\EntityManager;
use Mautic\ReportBundle\Entity\Report;
use Mautic\ReportBundle\Entity\Scheduler;
use Mautic\ReportBundle\Entity\SchedulerRepository;
use Mautic\ReportBundle\Model\ScheduleModel;
use Mautic\ReportBundle\Scheduler\Model\SchedulerPlanner;
use Mautic\ReportBundle\Scheduler\Option\ExportOption;

class ScheduleModelTest extends \PHPUnit\Framework\TestCase
{
    private $schedulerRepository;
    private $entityManager;
    private $schedulerPlanner;
    private $exportOption;

    /**
     * @var ScheduleModel
     */
    private $scheduleModel;

    protected function setUp()
    {
        $this->schedulerRepository = $this->createMock(SchedulerRepository::class);
        $this->entityManager       = $this->createMock(EntityManager::class);
        $this->schedulerPlanner    = $this->createMock(SchedulerPlanner::class);
        $this->exportOption        = $this->createMock(ExportOption::class);

        $this->entityManager->expects($this->once())
            ->method('getRepository')
            ->with(Scheduler::class)
            ->willReturn($this->schedulerRepository);

        $this->scheduleModel = new ScheduleModel($this->entityManager, $this->schedulerPlanner);
    }

    public function testGetScheduledReportsForExport()
    {
        $this->schedulerRepository->expects($this->once())
            ->method('getScheduledReportsForExport')
            ->with($this->exportOption);

        $this->scheduleModel->getScheduledReportsForExport($this->exportOption);
    }

    public function testReportWasScheduled()
    {
        $report = new Report();

        $this->schedulerPlanner->expects($this->once())
            ->method('computeScheduler')
            ->with($report);

        $this->scheduleModel->reportWasScheduled($report);
    }

    public function testTurnOffScheduler()
    {
        $report = new Report();

        $report->setIsScheduled(true);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($report);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->scheduleModel->turnOffScheduler($report);

        $this->assertFalse($report->isScheduled());
    }
}
