<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

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
        if ($emailIds != null) {
            if (!is_array($emailIds)) {
                $emailIds = [(int) $emailIds];
            }
            $qb->where(
                $qb->expr()->in('es.email_id', $emailIds)
            );
        }

        $qb->groupBy('es.list_id, d.device');

        if ($fromDate !== null) {
            //make sure the date is UTC
            $dt = new DateTimeHelper($fromDate);
            $qb->andWhere(
                $qb->expr()->gte('es.date_read', $qb->expr()->literal($dt->toUtcString()))
            );
        }
        if ($toDate !== null) {
            //make sure the date is UTC
            $dt = new DateTimeHelper($toDate);
            $qb->andWhere(
                $qb->expr()->lte('es.date_read', $qb->expr()->literal($dt->toUtcString()))
            );
        }

        return $qb->execute()->fetchAll();
    }
}
