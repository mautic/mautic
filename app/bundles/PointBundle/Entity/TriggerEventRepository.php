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
class TriggerEventRepository extends CommonRepository
{

    /**
     * Get array of published triggers based on point total
     *
     * @param $points
     *
     * @return array
     */
    public function getPublishedByPointTotal($points = 0)
    {
        $now = new \DateTime();

        $q = $this->createQueryBuilder('a')
            ->select('partial a.{id, type, name, properties, settings}, partial r.{id, name, points, color}')
            ->leftJoin('a.trigger', 'r')
            ->orderBy('a.order');

        //make sure the published up and down dates are good
        $q->where(
            $q->expr()->andX(
                $q->expr()->gte('r.points', $points),
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
            ->setParameter('now', $now);

        $results = $q->getQuery()->getResult();
        return $results;
    }

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
        $q = $this->createQueryBuilder('e')
            ->select('partial e.{id, type, name, properties, settings}, partial t.{id, name, points, color}')
            ->join('e.trigger', 't')
            ->orderBy('e.order');

        //make sure the published up and down dates are good
        $q->where(
            $q->expr()->andX(
                $q->expr()->eq('e.type', ':type'),
                $q->expr()->eq('t.isPublished', true),
                $q->expr()->orX(
                    $q->expr()->isNull('t.publishUp'),
                    $q->expr()->gte('t.publishUp', ':now')
                ),
                $q->expr()->orX(
                    $q->expr()->isNull('t.publishDown'),
                    $q->expr()->lte('t.publishDown', ':now')
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
    public function getLeadTriggeredEvents($leadId)
    {
        $q = $this->_em->getConnection()->createQueryBuilder()
            ->select('e')
            ->from(MAUTIC_TABLE_PREFIX . 'point_lead_event_log', 'x')
            ->innerJoin('x', MAUTIC_TABLE_PREFIX . 'point_trigger_events', 'e', 'x.triggerevent_id = e.id')
            ->innerJoin('e', MAUTIC_TABLE_PREFIX . 'point_triggers', 't', 'e.trigger_id = t.id');

        //make sure the published up and down dates are good
        $q->where($q->expr()->eq('x.lead_id', (int) $leadId));

        $results = $q->execute()->fetchAll();

        $return = array();
        foreach ($results as $r) {
            $return[$r['id']] = $r;
        }

        return $return;
    }


    /**
     * @param $type
     * @param $eventId
     *
     * @return array
     */
    public function getLeadsForEvent($eventId)
    {
        $results = $this->_em->getConnection()->createQueryBuilder()
            ->select('e.lead_id')
            ->from(MAUTIC_TABLE_PREFIX.'point_lead_event_log', 'e')
            ->where('e.triggerevent_id = ' . (int) $eventId)
            ->execute()
            ->fetchAll();

        $return = array();
        foreach ($results as $r) {
            $return[] = $r['lead_id'];
        }

        return $return;
    }
}
