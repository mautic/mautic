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
class ActionRepository extends CommonRepository
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
            ->select('partial a.{id, type, name, properties, settings}, partial p.{id, name}')
            ->join('a.point', 'p')
            ->orderBy('a.order');

        //make sure the published up and down dates are good
        $q->where(
            $q->expr()->andX(
                $q->expr()->eq('a.type', ':type'),
                $q->expr()->eq('p.isPublished', true),
                $q->expr()->orX(
                    $q->expr()->isNull('p.publishUp'),
                    $q->expr()->gte('p.publishUp', ':now')
                ),
                $q->expr()->orX(
                    $q->expr()->isNull('p.publishDown'),
                    $q->expr()->lte('p.publishDown', ':now')
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
            ->from(MAUTIC_TABLE_PREFIX . 'point_action_lead_xref', 'x')
            ->innerJoin('x', MAUTIC_TABLE_PREFIX . 'point_actions', 'a', 'x.action_id = a.id')
            ->innerJoin('a', MAUTIC_TABLE_PREFIX . 'points', 'p', 'a.point_id = p.id');

        //make sure the published up and down dates are good
        $q->where(
            $q->expr()->andX(
                $q->expr()->eq('a.type', ':type'),
                $q->expr()->eq('p.is_published', 1),
                $q->expr()->orX(
                    $q->expr()->isNull('p.publish_up'),
                    $q->expr()->gte('p.publish_up', ':now')
                ),
                $q->expr()->orX(
                    $q->expr()->isNull('p.publish_down'),
                    $q->expr()->lte('p.publish_down', ':now')
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
