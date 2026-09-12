<?php

declare(strict_types=1);

namespace Mautic\ReportBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\ReportBundle\Scheduler\Option\ExportOption;

/**
 * @extends CommonRepository<Scheduler>
 */
class SchedulerRepository extends CommonRepository
{
    /**
     * @return Scheduler|null
     */
    public function getSchedulerByReport(Report $report): ?object
    {
        return $this->findOneBy(['report' => $report]);
    }

    /**
     * @return array|Scheduler[]
     */
    public function getScheduledReportsForExport(ExportOption $exportOption): mixed
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
