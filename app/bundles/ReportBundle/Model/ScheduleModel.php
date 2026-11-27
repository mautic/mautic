<?php

declare(strict_types=1);

namespace Mautic\ReportBundle\Model;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\ReportBundle\Entity\Report;
use Mautic\ReportBundle\Entity\Scheduler;
use Mautic\ReportBundle\Entity\SchedulerRepository;
use Mautic\ReportBundle\Scheduler\Model\SchedulerPlanner;
use Mautic\ReportBundle\Scheduler\Option\ExportOption;

class ScheduleModel
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SchedulerPlanner $schedulerPlanner,
        private readonly SchedulerRepository $schedulerRepository,
    ) {
    }

    public function getRepository(): SchedulerRepository
    {
        return $this->schedulerRepository;
    }

    /**
     * @return Scheduler[]
     */
    public function getScheduledReportsForExport(ExportOption $exportOption): array
    {
        return $this->schedulerRepository->getScheduledReportsForExport($exportOption);
    }

    public function saveEntity(Scheduler $scheduler): void
    {
        $this->entityManager->persist($scheduler);
        $this->entityManager->flush();
    }

    public function reportWasScheduled(Report $report, bool $scheduleNextDownloadJob = true): void
    {
        $this->schedulerPlanner->computeScheduler($report, $scheduleNextDownloadJob);
    }

    public function turnOffScheduler(Report $report): void
    {
        $report->setIsScheduled(false);
        $this->entityManager->persist($report);
        $this->entityManager->flush();
    }
}
