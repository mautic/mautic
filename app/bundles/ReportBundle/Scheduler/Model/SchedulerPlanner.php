<?php

namespace Mautic\ReportBundle\Scheduler\Model;

use Doctrine\ORM\EntityManager;
use Mautic\ReportBundle\Entity\Report;
use Mautic\ReportBundle\Entity\Scheduler;
use Mautic\ReportBundle\Entity\SchedulerRepository;
use Mautic\ReportBundle\Scheduler\Date\DateBuilder;
use Mautic\ReportBundle\Scheduler\Exception\NoScheduleException;

class SchedulerPlanner
{
    /**
     * @var SchedulerRepository
     */
    private \Doctrine\ORM\EntityRepository $schedulerRepository;

    public function __construct(
        private DateBuilder $dateBuilder,
        private EntityManager $entityManager
    ) {
        $this->schedulerRepository = $entityManager->getRepository(Scheduler::class);
    }

    public function computeScheduler(Report $report): void
    {
        $this->removeSchedulerOfReport($report);
        $this->planScheduler($report);
    }

    private function planScheduler(Report $report): void
    {
        try {
            $date = $this->dateBuilder->getNextEvent($report);
        } catch (NoScheduleException) {
            return;
        }

        $scheduler = new Scheduler($report, $date);
        $this->entityManager->persist($scheduler);
        $this->entityManager->flush();
    }

    private function removeSchedulerOfReport(Report $report): void
    {
        $scheduler = $this->schedulerRepository->getSchedulerByReport($report);
        if (!$scheduler) {
            return;
        }

        $this->entityManager->remove($scheduler);
        $this->entityManager->flush();
    }
}
