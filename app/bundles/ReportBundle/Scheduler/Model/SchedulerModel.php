<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ReportBundle\Scheduler\Model;

use Doctrine\ORM\EntityManager;
use Mautic\ReportBundle\Entity\Report;
use Mautic\ReportBundle\Entity\Scheduler;
use Mautic\ReportBundle\Entity\SchedulerRepository;
use Mautic\ReportBundle\Scheduler\Date\DateBuilder;
use Mautic\ReportBundle\Scheduler\Exception\NoScheduleException;

class SchedulerModel
{
    /**
     * @var DateBuilder
     */
    private $dateBuilder;

    /**
     * @var SchedulerRepository
     */
    private $schedulerRepository;

    /**
     * @var EntityManager
     */
    private $entityManager;

    public function __construct(DateBuilder $dateBuilder, EntityManager $entityManager)
    {
        $this->dateBuilder         = $dateBuilder;
        $this->entityManager       = $entityManager;
        $this->schedulerRepository = $entityManager->getRepository(Scheduler::class);
    }

    public function computeScheduler(Report $report)
    {
        $this->removeSchedulerOfReport($report);
        $this->planScheduler($report);
    }

    private function planScheduler(Report $report)
    {
        try {
            $date = $this->dateBuilder->getNexEvent($report);
        } catch (NoScheduleException $e) {
            return;
        }

        $scheduler = new Scheduler($report, $date);
        $this->entityManager->persist($scheduler);
        $this->entityManager->flush();
    }

    private function removeSchedulerOfReport(Report $report)
    {
        $scheduler = $this->schedulerRepository->getSchedulerByReport($report);
        if (!$scheduler) {
            return;
        }

        $this->entityManager->remove($scheduler);
        $this->entityManager->flush();
    }
}
