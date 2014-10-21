<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\Entity;

use Doctrine\ORM\Query;
use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\CoreBundle\Helper\DateTimeHelper;

/**
 * Class HitRepository
 *
 * @package Mautic\PageBundle\Entity
 */
class HitRepository extends CommonRepository
{

    /**
     * Get a count of unique hits for the current tracking ID
     *
     * @param $pageId
     * @param $trackingId
     *
     * @return int
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getHitCountForTrackingId($pageId, $trackingId)
    {
        $count = $this->createQueryBuilder('h')
            ->select('count(h.id) as num')
            ->where('IDENTITY(h.page) = ' .$pageId)
            ->andWhere('h.trackingId = :id')
            ->setParameter('id', $trackingId)
            ->getQuery()
            ->getSingleResult();

        return (int) $count['num'];
    }

    /**
     * Get a lead's page hits
     *
     * @param integer $leadId
     * @param array   $ipIds
     *
     * @return array
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getLeadHits($leadId, array $ipIds = array())
    {
        $query = $this->createQueryBuilder('h')
            ->select('IDENTITY(h.page) AS page_id, h.dateHit')
            ->where('h.lead = ' . $leadId);

        if (!empty($ipIds)) {
            $query->orWhere('h.ipAddress IN (' . implode(',', $ipIds) . ')');
        }

        return $query->getQuery()
            ->getArrayResult();
    }

    /**
     * Get hit count per day for last 30 days
     *
     * @param integer $pageId
     *
     * @return array
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getHitsForLast30Days($pageId)
    {
        $date = new \DateTime();
        $oneDay = new \DateInterval('P1D');
        $days = array();

        // Prefill $days array keys
        for ($i = 0; $i < 30; $i++) {
            $days[$date->format('Y-m-d')] = 0;
            $date->sub($oneDay);
        }
        
        $query = $this->createQueryBuilder('h');
        
        $query->select('IDENTITY(h.page), h.dateHit')
            ->where($query->expr()->eq('IDENTITY(h.page)', (int) $pageId))
            ->andwhere($query->expr()->gte('h.dateHit', ':date'))
            ->setParameter('date', $date);

        $hits = $query->getQuery()->getArrayResult();

        // Group hits by date
        foreach ($hits as $hit) {
            $day = $hit['dateHit']->format('Y-m-d');
            $days[$day]++;
        }

        return $days;
    }

    /**
     * Get new, unique and returning visitors
     *
     * @param array      $args
     * @return array
     */
    public function getNewReturningVisitorsCount($args = array())
    {
        $results = array();
        $results['returning'] = $this->getReturningCount($args);
        $results['unique'] = $this->getUniqueCount($args);
        $results['new'] = $results['unique'] - $results['returning'];

        return $results;
    }

    /**
     * Count returning visitors
     *
     * @param array      $args
     * @return integer
     */
    public function getReturningCount($args = array())
    {
        $q = $this->createQueryBuilder('h');
        $q->select('COUNT(h.ipAddress) as returning')
            ->groupBy('h.ipAddress')
            ->having($q->expr()->gt('COUNT(h.ipAddress)', 1));
        $results = $q->getQuery()->getResult();

        return count($results);
    }

    /**
     * Count how many unique visitors hit pages
     *
     * @param array      $args
     * @return integer
     */
    public function getUniqueCount($args = array())
    {
        $q = $this->createQueryBuilder('h');
        $q->select('COUNT(DISTINCT h.ipAddress) as unique');
        $results = $q->getQuery()->getSingleResult();

        if (!isset($results['unique'])) {
            return 0;
        }

        return (int) $results['unique'];
    }

    /**
     * Get the number of bounces
     *
     * @param $pageIds
     * @param $fromDate
     *
     * @return array
     */
    public function getBounces($pageIds, \DateTime $fromDate = null)
    {
        $inIds = (!is_array($pageIds)) ? array($pageIds) : $pageIds;

        $sq = $this->_em->getConnection()->createQueryBuilder();
        $sq->select('h.page_id, count(*) as hits')
            ->from(MAUTIC_TABLE_PREFIX.'page_hits', 'h')
            ->leftJoin('h', MAUTIC_TABLE_PREFIX.'pages', 'p', 'h.page_id = p.id')
            ->andWhere($sq->expr()->in('h.page_id', $inIds))
            ->andWhere($sq->expr()->eq('h.code', '200'));
        if ($fromDate !== null) {
            //make sure the date is UTC
            $dt = new DateTimeHelper($fromDate);
            $sq->andWhere(
                $sq->expr()->gte('h.date_hit', $sq->expr()->literal($dt->toUtcString()))
            );
        }

        //the total hits and bounce rates may return different pages based on available results so create an array
        //to keep from having PHP notices of non-existant keys
        $return  = array();
        foreach ($inIds as $id) {
            $return[$id] = array(
                'totalHits' => 0,
                'bounces'   => 0,
                'rate'      => 0
            );
        }
        $sq->groupBy('h.tracking_id');
        //get a total number of hits first
        $results = $sq->execute()->fetchAll();

        foreach ($results as $t) {
            //if there are no hits, an array with a null page_id will be returned which must be accounted for
            if ($t['page_id'] != null) {
                $return[$t['page_id']]['totalHits'] += (int)$t['hits'];
            }
        }

        //now get a bounce count
        $sq->having('hits = 1');

        $q  = $this->_em->getConnection()->createQueryBuilder();
        $q->select('h2.page_id, SUM(hits) as bounces')
            ->from(sprintf('(%s)', $sq->getSQL()), 'h2')
            ->groupBy('h2.page_id');
        $results = $q->execute()->fetchAll();

        foreach ($results as $r) {
            $return[$r['page_id']]['bounces'] = (int) $r['bounces'];
            $return[$r['page_id']]['rate']    = ($return[$r['page_id']]['totalHits']) ?
                round(($r['bounces'] / $return[$r['page_id']]['totalHits']) * 100, 2) :
                0;
        }

        return (!is_array($pageIds)) ? $return[$pageIds] : $return;
    }

    /**
     * Get the number of bounces
     *
     * @param $pageIds
     * @param $fromDate
     *
     * @return array
     */
    public function getDwellTimes($pageIds, \DateTime $fromDate = null)
    {
        $inIds = (!is_array($pageIds)) ? array($pageIds) : $pageIds;

        $q  = $this->_em->getConnection()->createQueryBuilder();
        $q->select('h.id, h.page_id, h.date_hit, h.date_left, h.tracking_id')
            ->from(MAUTIC_TABLE_PREFIX.'page_hits', 'h')
            ->leftJoin('h', MAUTIC_TABLE_PREFIX.'pages', 'p', 'h.page_id = p.id')
            ->where(
                $q->expr()->andX(
                    $q->expr()->in('h.page_id', $inIds),
                    $q->expr()->isNotNull('h.date_left')
                )
            );

        if ($fromDate !== null) {
            //make sure the date is UTC
            $dt = new DateTimeHelper($fromDate);
            $q->andWhere(
                $q->expr()->gte('h.date_hit', $q->expr()->literal($dt->toUtcString()))
            );
        }
        $q->orderBy('h.date_hit', 'ASC');
        $results = $q->execute()->fetchAll();

        //loop to structure
        $times = array();
        foreach ($results as $r) {
            $dateHit  = new \DateTime($r['date_hit']);
            $dateLeft = new \DateTime($r['date_left']);
            $times[$r['page_id']][] = ($dateLeft->getTimestamp() - $dateHit->getTimestamp());
        }

        //now loop to create stats
        $stats = array();
        foreach ($times as $pid => $time) {
            $stats[$pid] = array(
                'sum'     => array_sum($time),
                'min'     => min($time),
                'max'     => max($time),
                'average' => count($time) ? round(array_sum($time) / count($time)) : 0
            );
        }

        return (!is_array($pageIds) && array_key_exists('$pageIds', $stats)) ? $stats[$pageIds] : $stats;
    }

    /**
     * Update a hit with the the time the user left
     *
     * @param $lastHitId
     */
    public function updateHitDateLeft($lastHitId)
    {
        $dt = new DateTimeHelper();
        $q = $this->_em->getConnection()->createQueryBuilder();
        $q->update(MAUTIC_TABLE_PREFIX.'page_hits')
            ->set('date_left', ':datetime')
            ->where('id = ' . (int) $lastHitId)
            ->setParameter('datetime', $dt->toUtcString());
        $q->execute();
    }
}
