<?php

declare(strict_types=1);

namespace Mautic\ReportBundle\Tests\Scheduler\Model;

use Doctrine\ORM\EntityManager;
use Mautic\ReportBundle\Entity\Report;
use Mautic\ReportBundle\Entity\Scheduler;
use Mautic\ReportBundle\Entity\SchedulerRepository;
use Mautic\ReportBundle\Scheduler\Date\DateBuilder;
use Mautic\ReportBundle\Scheduler\Exception\NoScheduleException;
use Mautic\ReportBundle\Scheduler\Model\SchedulerPlanner;

final class SchedulerPlannerTest extends \PHPUnit\Framework\TestCase
{
    public function testComputeSchedule(): void
    {
        $dateBuilder = $this->createMock(DateBuilder::class);

        $schedulerRepository = $this->createMock(SchedulerRepository::class);

        $entityManager = $this->createMock(EntityManager::class);

        $schedulerPlanner = new SchedulerPlanner($dateBuilder, $entityManager, $schedulerRepository);

        $report = new Report();

        $oldScheduler = new Scheduler($report, new \DateTime());

        $schedulerRepository->expects($this->once())
            ->method('getSchedulerByReport')
            ->with($report)
            ->willReturn($oldScheduler);

        $entityManager->expects($this->once())
            ->method('remove')
            ->with($oldScheduler);

        $entityManager->expects($this->exactly(2))
            ->method('flush')
            ->with();

        $dateOfNextSchedule = new \DateTime();

        $dateBuilder->expects($this->once())
            ->method('getNextEvent')
            ->with($report)
            ->willReturn($dateOfNextSchedule);

        $entityManager->expects($this->once())
            ->method('persist')
            ->with($this->callback(function ($scheduler) use ($report, $dateOfNextSchedule): bool {
                $this->assertInstanceOf(Scheduler::class, $scheduler);
                $this->assertSame($report, $scheduler->getReport());
                $this->assertSame($dateOfNextSchedule, $scheduler->getScheduleDate());

                return true;
            }));

        $schedulerPlanner->computeScheduler($report);
    }

    public function testNoSchedule(): void
    {
        $dateBuilder = $this->createMock(DateBuilder::class);

        $schedulerRepository = $this->createMock(SchedulerRepository::class);

        $entityManager = $this->createMock(EntityManager::class);

        $schedulerPlanner = new SchedulerPlanner($dateBuilder, $entityManager, $schedulerRepository);

        $report = new Report();

        $oldScheduler = new Scheduler($report, new \DateTime());

        $schedulerRepository->expects($this->once())
            ->method('getSchedulerByReport')
            ->with($report)
            ->willReturn($oldScheduler);

        $entityManager->expects($this->once())
            ->method('remove')
            ->with($oldScheduler);

        $entityManager->expects($this->once())
            ->method('flush')
            ->with();

        $dateBuilder->expects($this->once())
            ->method('getNextEvent')
            ->with($report)
            ->willThrowException(new NoScheduleException());

        $schedulerPlanner->computeScheduler($report);
    }

    public function testNoRemoveNoSchedule(): void
    {
        $dateBuilder = $this->createMock(DateBuilder::class);

        $schedulerRepository = $this->createMock(SchedulerRepository::class);

        $entityManager = $this->createStub(EntityManager::class);

        $schedulerPlanner = new SchedulerPlanner($dateBuilder, $entityManager, $schedulerRepository);

        $report = new Report();

        $schedulerRepository->expects($this->once())
            ->method('getSchedulerByReport')
            ->with($report)
            ->willReturn(null);

        $dateBuilder->expects($this->once())
            ->method('getNextEvent')
            ->with($report)
            ->willThrowException(new NoScheduleException());

        $schedulerPlanner->computeScheduler($report);
    }
}
