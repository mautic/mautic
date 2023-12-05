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
    private \Mautic\ReportBundle\Scheduler\Date\DateBuilder $dateBuilder;

    /**
     * @var SchedulerRepository
     */
    private \Doctrine\ORM\EntityRepository $schedulerRepository;

    private \Doctrine\ORM\EntityManager $entityManager;

    public function __construct(DateBuilder $dateBuilder, EntityManager $entityManager)
    {
        $this->dateBuilder         = $dateBuilder;
        $this->entityManager       = $entityManager;
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
        } catch (NoScheduleException $e) {
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
