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
    private readonly \Doctrine\ORM\EntityRepository $schedulerRepository;

    public function __construct(
        private readonly EntityManager $entityManager,
        private readonly SchedulerPlanner $schedulerPlanner,
    ) {
        $this->schedulerRepository = $entityManager->getRepository(Scheduler::class);
    }

    /**
     * Avoid the default AbstractCommonModel::getRepository() as it caches it to a static property.
     */
    public function getRepository(): SchedulerRepository
    {
        return $this->schedulerRepository;
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
