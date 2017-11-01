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
use Mautic\ReportBundle\Entity\Scheduler;
use Mautic\ReportBundle\Entity\SchedulerRepository;
use Mautic\ReportBundle\Scheduler\Option\ExportOption;

class ScheduleModel
{
    /**
     * @var SchedulerRepository
     */
    private $schedulerRepository;

    /**
     * @var EntityManager
     */
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager       = $entityManager;
        $this->schedulerRepository = $entityManager->getRepository(Scheduler::class);
    }

    public function getScheduledReportsForExport(ExportOption $exportOption)
    {
        return $this->schedulerRepository->getScheduledReportsForExport($exportOption);
    }
}
