<?php

namespace Mautic\EmailBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\CoreBundle\Helper\DateTimeHelper;

/**
 * Class StatDeviceRepository.
 */
class StatDeviceRepository extends CommonRepository
{
    /**
     * @param           $emailIds
     * @param \DateTime $fromDate
     *
     * @return array
     */
    public function getDeviceStats($emailIds, \DateTime $fromDate = null, \DateTime $toDate = null)
    {
        $qb = $this->getEntityManager()->getConnection()->createQueryBuilder();

        $qb->select('count(es.id) as count, d.device as device, es.list_id')
            ->from(MAUTIC_TABLE_PREFIX.'email_stats_devices', 'ed')
            ->join('ed', MAUTIC_TABLE_PREFIX.'lead_devices', 'd', 'd.id = ed.device_id')
            ->join('ed', MAUTIC_TABLE_PREFIX.'email_stats', 'es', 'es.id = ed.stat_id');
        if (null != $emailIds) {
            if (!is_array($emailIds)) {
                $emailIds = [(int) $emailIds];
            }
            $qb->where(
                $qb->expr()->in('es.email_id', $emailIds)
            );
        }

        $qb->groupBy('es.list_id, d.device');

        if (null !== $fromDate) {
            //make sure the date is UTC
            $dt = new DateTimeHelper($fromDate);
            $qb->andWhere(
                $qb->expr()->gte('es.date_read', $qb->expr()->literal($dt->toUtcString()))
            );
        }
        if (null !== $toDate) {
            //make sure the date is UTC
            $dt = new DateTimeHelper($toDate);
            $qb->andWhere(
                $qb->expr()->lte('es.date_read', $qb->expr()->literal($dt->toUtcString()))
            );
        }

        return $qb->execute()->fetchAll();
    }
}
