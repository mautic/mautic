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

class ScheduleModelTest extends \PHPUnit_Framework_TestCase
{
    public function testGetScheduledReportsForExport()
    {
        $schedulerRepository = $this->getMockBuilder(SchedulerRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $entityManager = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $schedulerPlanner = $this->getMockBuilder(SchedulerPlanner::class)
            ->disableOriginalConstructor()
            ->getMock();

        $exportOption = $this->getMockBuilder(ExportOption::class)
            ->disableOriginalConstructor()
            ->getMock();

        $entityManager->expects($this->once())
            ->method('getRepository')
            ->with(Scheduler::class)
            ->willReturn($schedulerRepository);

        $schedulerRepository->expects($this->once())
            ->method('getScheduledReportsForExport')
            ->with($exportOption);

        $scheduleModel = new ScheduleModel($entityManager, $schedulerPlanner);

        $scheduleModel->getScheduledReportsForExport($exportOption);
    }

    public function testReportWasScheduled()
    {
        $schedulerRepository = $this->getMockBuilder(SchedulerRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $entityManager = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $schedulerPlanner = $this->getMockBuilder(SchedulerPlanner::class)
            ->disableOriginalConstructor()
            ->getMock();

        $entityManager->expects($this->once())
            ->method('getRepository')
            ->with(Scheduler::class)
            ->willReturn($schedulerRepository);

        $report = new Report();

        $schedulerPlanner->expects($this->once())
            ->method('computeScheduler')
            ->with($report);

        $scheduleModel = new ScheduleModel($entityManager, $schedulerPlanner);

        $scheduleModel->reportWasScheduled($report);
    }
}
