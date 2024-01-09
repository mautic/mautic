<?php

namespace Mautic\ReportBundle\Model;

use Doctrine\ORM\EntityManager;
use Mautic\ReportBundle\Entity\Report;
use Mautic\ReportBundle\Entity\Scheduler;
use Mautic\ReportBundle\Entity\SchedulerRepository;
use Mautic\ReportBundle\Scheduler\Model\SchedulerPlanner;
use Mautic\ReportBundle\Scheduler\Option\ExportOption;

class ScheduleModel
{
    /**
     * @var SchedulerRepository
     */
    private \Doctrine\ORM\EntityRepository $schedulerRepository;

    public function __construct(
        private EntityManager $entityManager,
        private SchedulerPlanner $schedulerPlanner
    ) {
        $this->schedulerRepository = $entityManager->getRepository(Scheduler::class);
    }

    /**
     * @return Scheduler[]
     */
    public function getScheduledReportsForExport(ExportOption $exportOption)
    {
        return $this->schedulerRepository->getScheduledReportsForExport($exportOption);
    }

    public function reportWasScheduled(Report $report): void
    {
        $this->schedulerPlanner->computeScheduler($report);
    }

    public function turnOffScheduler(Report $report): void
    {
        $report->setIsScheduled(false);
        $this->entityManager->persist($report);
        $this->entityManager->flush();
    }
}
