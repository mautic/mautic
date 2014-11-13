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
use Mautic\CoreBundle\Helper\GraphHelper;

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
     * @param Page|Redirect $page
     * @param $trackingId
     *
     * @return int
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getHitCountForTrackingId($page, $trackingId)
    {
        $q = $this->createQueryBuilder('h')
            ->select('count(h.id) as num');

        if ($page instanceof Page) {
            $q->where('IDENTITY(h.page) = ' .$page->getId());
        } elseif ($page instanceof Redirect) {
            $q->where('IDENTITY(h.redirect) = ' .$page->getId());
        }

        $q->andWhere('h.trackingId = :id')
        ->setParameter('id', $trackingId);

        $count = $q->getQuery()->getSingleResult();

        return (int) $count['num'];
    }

    /**
     * Get a lead's page hits
     *
     * @param integer $leadId
     * @param array   $options
     *
     * @return array
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getLeadHits($leadId, array $options = array())
    {
        $query = $this->createQueryBuilder('h');
        $query->select('IDENTITY(h.page) AS page_id, h.dateHit')
            ->where('h.lead = ' . (int) $leadId);

        if (!empty($options['ipIds'])) {
            $query->orWhere('h.ipAddress IN (' . implode(',', $options['ipIds']) . ')');
        }

        if (isset($options['filters']['search']) && $options['filters']['search']) {
            $query->leftJoin('h.page', 'p')
                ->andWhere($query->expr()->like('p.title', $query->expr()->literal('%' . $options['filters']['search'] . '%')));
        }

        return $query->getQuery()->getArrayResult();
    }

    /**
     * Get hit per time period
     *
     * @param array $args
     *
     * @return array
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getHits($amount, $unit, $args = array())
    {
        $data = GraphHelper::prepareLineGraphData($amount, $unit, array('viewed'));

        $query = $this->createQueryBuilder('h');

        $query->select('IDENTITY(h.page), h.dateHit');

        if (isset($args['page_id'])) {
            $query->andWhere($query->expr()->eq('IDENTITY(h.page)', (int) $args['page_id']));
        }

        if (isset($args['source'])) {
            $query->andWhere($query->expr()->eq('h.source', $query->expr()->literal($args['source'])));
        }

        if (isset($args['source_id'])) {
            $query->andWhere($query->expr()->eq('h.sourceId', (int) $args['source_id']));
        }

        $query->andwhere($query->expr()->gte('h.dateHit', ':date'))
            ->setParameter('date', $data['fromDate']);

        $hits = $query->getQuery()->getArrayResult();

        return GraphHelper::mergeLineGraphData($data, $hits, $unit, 0, 'dateHit');
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
        $q->select('COUNT(h.trackingId) as returning')
            ->groupBy('h.trackingId')
            ->having($q->expr()->gt('COUNT(h.trackingId)', 1));
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
        $q->select('COUNT(DISTINCT h.trackingId) as unique');
        $results = $q->getQuery()->getSingleResult();

        if (!isset($results['unique'])) {
            return 0;
        }

        return (int) $results['unique'];
    }

    /**
     * Count how many visitors hit some page in last X $seconds
     *
     * @param integer      $seconds
     * @return integer
     */
    public function countVisitors($seconds = 60, $notLeft = false)
    {
        $now = new \DateTime();
        $viewingTime = new \DateInterval('PT'.$seconds.'S');
        $now->sub($viewingTime);
        $query = $this->createQueryBuilder('h');

        $query->select('count(h.code) as visitors');

        if ($seconds) {
            $query->where($query->expr()->gte('h.dateHit', ':date'))
                ->setParameter('date', $now);
        }

        if ($notLeft) {
            $query->andWhere($query->expr()->isNull('h.dateLeft'));
        }

        $result = $query->getQuery()->getSingleResult();

        if (!isset($result['visitors'])) {
            return 0;
        }

        return (int) $result['visitors'];
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
    public function getDwellTimes($pageIds = null, \DateTime $fromDate = null, $q = null)
    {
        if (!$q) {
            $q  = $this->_em->getConnection()->createQueryBuilder();
        }

        $q->select('h.id, h.page_id, h.date_hit, h.date_left, h.tracking_id, h.page_language')
            ->from(MAUTIC_TABLE_PREFIX.'page_hits', 'h')
            ->leftJoin('h', MAUTIC_TABLE_PREFIX.'pages', 'p', 'h.page_id = p.id');

        if ($pageIds) {
            $inIds = (!is_array($pageIds)) ? array($pageIds) : $pageIds;
            $q->where(
                $q->expr()->andX(
                    $q->expr()->in('h.page_id', $inIds)
                )
            );
        }

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
        $trackingIds = array();
        $languages = array();
        foreach ($results as $r) {
            $dateHit  = new \DateTime($r['date_hit']);
            $dateLeft = new \DateTime($r['date_left']);
            if ($pageIds) {
                $times[$r['page_id']][] = ($dateLeft->getTimestamp() - $dateHit->getTimestamp());
                if (!isset($trackingIds[$r['page_id']])) {
                    $trackingIds[$r['page_id']] = array();
                }
                if (array_key_exists($r['tracking_id'], $trackingIds[$r['page_id']])) {
                    $trackingIds[$r['page_id']][$r['tracking_id']]++;
                } else {
                    $trackingIds[$r['page_id']][$r['tracking_id']] = 1;
                }
                if (!isset($languages[$r['page_id']])) {
                    $languages[$r['page_id']] = array();
                }
                if (array_key_exists($r['page_language'], $languages)) {
                    $languages[$r['page_id']][$r['page_language']]++;
                } else {
                    $languages[$r['page_id']][$r['page_language']] = 1;
                }
            } else {
                $times[] = ($dateLeft->getTimestamp() - $dateHit->getTimestamp());
                if (array_key_exists($r['tracking_id'], $trackingIds)) {
                    $trackingIds[$r['tracking_id']]++;
                } else {
                    $trackingIds[$r['tracking_id']] = 1;
                }
                if (array_key_exists($r['page_language'], $languages)) {
                    $languages[$r['page_language']]++;
                } else {
                    $languages[$r['page_language']] = 1;
                }
            }
        }

        //now loop to create stats
        $stats = array();
        if ($pageIds) {
            foreach ($times as $pid => $time) {
                $stats[$pid] = $this->countStats($time);
                $stats[$pid]['returning'] = $this->countReturning($trackingIds[$pid]);
                $stats[$pid]['new'] = count($trackingIds[$pid]) - $stats[$pid]['returning'];
                $stats[$pid]['newVsReturning'] = $this->getNewVsReturningGraphData($stats[$pid]['new'], $stats[$pid]['returning']);
                $stats[$pid]['languages'] = $this->getLaguageGraphData($languages[$pid]);
            }
        } else {
            $stats = $this->countStats($times);
            $stats['returning'] = $this->countReturning($trackingIds);
            $stats['new'] = count($trackingIds) - $stats['returning'];
            $stats['newVsReturning'] = $this->getNewVsReturningGraphData($stats['new'], $stats['returning']);
            $stats['languages'] = $this->getLaguageGraphData($languages);
        }

        return (!is_array($pageIds) && array_key_exists('$pageIds', $stats)) ? $stats[$pageIds] : $stats;
    }

    /**
     * Count returning visitors
     *
     * @param array $visitors
     *
     * @return array
     */
    public function countReturning($visitors)
    {
        $returning = 0;
        foreach ($visitors as $visitor) {
            if ($visitor > 1) {
                $returning++;
            }
        }

        return $returning;
    }

    /**
     * Count stats from hit times
     *
     * @param array $times
     *
     * @return array
     */
    public function countStats($times)
    {
        $stats = array(
            'sum'     => array_sum($times),
            'min'     => count($times) ? min($times) : 0,
            'max'     => count($times) ? max($times) : 0,
            'average' => count($times) ? round(array_sum($times) / count($times)) : 0,
            'count'   => count($times)
        );
        if ($times) {
            $timesOnSite = GraphHelper::getTimesOnSite();
            foreach ($times as $seconds) {
                foreach($timesOnSite as $tkey => $tos) {
                    if ($seconds > $tos['from'] && $seconds <= $tos['till']) {
                        $timesOnSite[$tkey]['value']++;
                    }
                }
            }
            $stats['timesOnSite'] = $timesOnSite;
        } else {
            $stats['timesOnSite'] = array();
        }


        return $stats;
    }

    /**
     * Prepare data structure for New vs Returning graph
     *
     * @param integer $new
     * @param integer $returning
     *
     * @return array
     */
    public function getNewVsReturningGraphData($new, $returning)
    {
        return GraphHelper::preparePieGraphData(array('new' => $new, 'returning' => $returning));
    }

    /**
     * Prepare data structure for New vs Returning graph
     *
     * @param integer $new
     * @param integer $returning
     *
     * @return array
     */
    public function getLaguageGraphData($languages)
    {
        return GraphHelper::preparePieGraphData($languages);
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

    /**
     * Get list of referers ordered by it's count
     *
     * @param QueryBuilder $query
     * @param integer $limit
     * @param integer $offset
     *
     * @return array
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getReferers($query, $limit = 10, $offset = 0)
    {
        $query->select('h.referer, count(h.referer) as sessions')
            ->from(MAUTIC_TABLE_PREFIX.'page_hits', 'h')
            ->leftJoin('h', MAUTIC_TABLE_PREFIX.'pages', 'p', 'h.page_id = p.id')
            ->groupBy('h.referer')
            ->orderBy('sessions', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        $results = $query->execute()->fetchAll();

        return $results;
    }

    /**
     * Get list of referers ordered by it's count
     *
     * @param QueryBuilder $query
     * @param integer $limit
     * @param integer $offset
     *
     * @return array
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getMostVisited($query, $limit = 10, $offset = 0, $column = 'p.hits', $as = '')
    {
        if ($as) {
            $as = ' as ' . $as;
        }
        $query->select('p.title, p.id, ' . $column . $as)
            ->from(MAUTIC_TABLE_PREFIX.'pages', 'p')
            ->leftJoin('p', MAUTIC_TABLE_PREFIX.'page_hits', 'h', 'h.page_id = p.id')
            ->groupBy('p.id')
            ->orderBy($column, 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        $results = $query->execute()->fetchAll();

        return $results;
    }
}
