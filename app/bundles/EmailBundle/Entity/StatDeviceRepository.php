<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\CoreBundle\Helper\DateTimeHelper;

/**
 * Class StatDeviceRepository
 *
 * @package Mautic\EmailBundle\Entity
 */
class StatDeviceRepository extends CommonRepository
{
    /**
     * @param           $emailIds
     * @param \DateTime $fromDate
     *
     * @return array
     */
    public function getDeviceStats($statIds, \DateTime $fromDate = null, \DateTime $toDate = null, $canViewOthers = false)
    {
        $sq = $this->_em->getConnection()->createQueryBuilder();
        $sq->select('count(es.id) as count, d.device as device')
            ->from(MAUTIC_TABLE_PREFIX.'email_stats_devices', 'es')
            ->join('es',MAUTIC_TABLE_PREFIX.'lead_devices','d','d.id=es.device_id');
        if ($statIds) {
            if (!is_array($statIds)) {
                $statIds = array((int) $statIds);
            }
            $sq->where(
                $sq->expr()->in('es.stat_id', $statIds)
            );
        }
        if (!$canViewOthers) {
            $sq->join('es', MAUTIC_TABLE_PREFIX.'email_stats', 'em', 'em.id=es.stat_id')
                ->join('es', MAUTIC_TABLE_PREFIX.'emails', 'e', 'e.id=em.email_id and e.created_by  = :userId')
            ->setParameter('userId', $this->currentUser->getId());
        }
        if ($fromDate !== null) {
            //make sure the date is UTC
            $dt = new DateTimeHelper($fromDate);
            $sq->andWhere(
                $sq->expr()->gte('es.date_opened', $sq->expr()->literal($dt->toUtcString()))
            );
        }
        if ($toDate !== null) {
            //make sure the date is UTC
            $dt = new DateTimeHelper($toDate);
            $sq->andWhere(
                $sq->expr()->lte('es.date_opened', $sq->expr()->literal($dt->toUtcString()))
            );
        }

        $sq->groupBy('d.device, es.stat_id');


        //get a total number of sent emails first
        $totalCounts = $sq->execute()->fetchAll();

        return $totalCounts;
    }
}