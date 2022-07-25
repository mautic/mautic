<?php

namespace Mautic\ReportBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Mautic\ReportBundle\Scheduler\Option\ExportOption;

/**
 * SchedulerRepository.
 */
class SchedulerRepository extends EntityRepository
{
    /**
     * @return Report|null
     */
    public function getSchedulerByReport(Report $report)
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
