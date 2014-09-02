<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PointBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\CoreBundle\Helper\DateTimeHelper;

/**
 * ActionRepository
 */
class RangeActionRepository extends CommonRepository
{

    /**
     * Get array of published actions based on type
     *
     * @param $type
     *
     * @return array
     */
    public function getPublishedByType($type)
    {
        $now = new \DateTime();
        $q = $this->createQueryBuilder('a')
            ->select('partial a.{id, type, name, properties, settings}, partial r.{id, name, fromScore, toScore, color}')
            ->join('a.range', 'r')
            ->orderBy('a.order');

        //make sure the published up and down dates are good
        $q->where(
            $q->expr()->andX(
                $q->expr()->eq('a.type', ':type'),
                $q->expr()->eq('r.isPublished', true),
                $q->expr()->orX(
                    $q->expr()->isNull('r.publishUp'),
                    $q->expr()->gte('r.publishUp', ':now')
                ),
                $q->expr()->orX(
                    $q->expr()->isNull('r.publishDown'),
                    $q->expr()->lte('r.publishDown', ':now')
                )
            )
        )
            ->setParameter('now', $now)
            ->setParameter('type', $type);

        $results = $q->getQuery()->getResult();
        return $results;
    }

    /**
     * @param $type
     * @param $leadId
     *
     * @return array
     */
    public function getCompletedLeadActions($type, $leadId)
    {
        $now = new DateTimeHelper();
        $q = $this->_em->getConnection()->createQueryBuilder()
            ->select('a.type')
            ->from(MAUTIC_TABLE_PREFIX . 'point_range_action_lead_xref', 'x')
            ->innerJoin('x', MAUTIC_TABLE_PREFIX . 'point_range_actions', 'a', 'x.action_id = a.id')
            ->innerJoin('a', MAUTIC_TABLE_PREFIX . 'point_ranges', 'p', 'a.range_id = r.id');

        //make sure the published up and down dates are good
        $q->where(
            $q->expr()->andX(
                $q->expr()->eq('a.type', ':type'),
                $q->expr()->eq('r.is_published', 1),
                $q->expr()->orX(
                    $q->expr()->isNull('r.publish_up'),
                    $q->expr()->gte('r.publish_up', ':now')
                ),
                $q->expr()->orX(
                    $q->expr()->isNull('r.publish_down'),
                    $q->expr()->lte('r.publish_down', ':now')
                ),
                $q->expr()->eq('x.lead_id', $leadId)
            )
        )
            ->setParameter('now', $now->toUtcString())
            ->setParameter('type', $type);

        $results = $q->execute()->fetchAll();

        $return = array();
        foreach ($results as $r) {
            $return[] = $r['type'];
        }

        return $return;
    }
}
