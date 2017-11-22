<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

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
    private $schedulerRepository;

    /**
     * @var SchedulerPlanner
     */
    private $schedulerPlanner;

    public function __construct(EntityManager $entityManager, SchedulerPlanner $schedulerPlanner)
    {
        $this->schedulerRepository = $entityManager->getRepository(Scheduler::class);
        $this->schedulerPlanner    = $schedulerPlanner;
    }

    public function getScheduledReportsForExport(ExportOption $exportOption)
    {
        return $this->schedulerRepository->getScheduledReportsForExport($exportOption);
    }

    public function reportWasScheduled(Report $report)
    {
        $this->schedulerPlanner->computeScheduler($report);
    }
}
