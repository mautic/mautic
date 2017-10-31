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
use Mautic\ReportBundle\Adapter\ReportDataAdapter;
use Mautic\ReportBundle\Entity\Report;
use Mautic\ReportBundle\Entity\Scheduler;
use Mautic\ReportBundle\Entity\SchedulerRepository;
use Mautic\ReportBundle\Scheduler\Option\ExportOption;

class ReportExporter
{
    /**
     * @var SchedulerRepository
     */
    private $schedulerRepository;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var ReportDataAdapter
     */
    private $reportDataAdapter;

    public function __construct(EntityManager $entityManager, ReportDataAdapter $reportDataAdapter)
    {
        $this->entityManager       = $entityManager;
        $this->schedulerRepository = $entityManager->getRepository(Scheduler::class);
        $this->reportDataAdapter   = $reportDataAdapter;
    }

    public function processExport(ExportOption $exportOption)
    {
        $schedulers = $this->schedulerRepository->getScheduledReportsForExport($exportOption);
        foreach ($schedulers as $scheduler) {
            $this->processReport($scheduler->getReport());
        }
    }

    private function processReport(Report $report)
    {
        $data = $this->reportDataAdapter->getRportData($report);
        //dump($data);exit;

        //WIP
    }
}
