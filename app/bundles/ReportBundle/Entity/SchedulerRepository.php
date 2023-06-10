<?php

namespace Mautic\ReportBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\ReportBundle\Scheduler\Option\ExportOption;

/**
 * @extends CommonRepository<Scheduler>
 */
class SchedulerRepository extends CommonRepository
{
    public function getSchedulerByReport(Report $report): ?Scheduler
    {
        return $this->findOneBy(['report' => $report]);
    }

    /**
     * @return array|Scheduler[]
     */
    public function getScheduledReportsForExport(ExportOption $exportOption)
    {
        $qb = $this->createQueryBuilder('scheduler');
        $qb->addSelect('report')
            ->leftJoin('scheduler.report', 'report');

        $date = new \DateTime();
        $qb->andWhere('scheduler.scheduleDate <= :scheduleDate')
            ->setParameter('scheduleDate', $date);

        if ($exportOption->getReportId()) {
            $qb->andWhere('report.id = :id')
                ->setParameter('id', $exportOption->getReportId());
        }

        return $qb->getQuery()->getResult();
    }
}
