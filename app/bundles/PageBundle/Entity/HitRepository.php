<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\Entity;

use Doctrine\ORM\Query;
use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\TimelineTrait;

/**
 * Class HitRepository.
 */
class HitRepository extends CommonRepository
{
    use TimelineTrait;

    /**
     * Determine if the page hit is a unique.
     *
     * @param Page|Redirect $page
     * @param string        $trackingId
     * @param Lead          $lead
     *
     * @return bool
     */
    public function isUniquePageHit($page, $trackingId, Lead $lead = null)
    {
        $q  = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $q2 = $this->getEntityManager()->getConnection()->createQueryBuilder();

        $q2->select('null')
            ->from(MAUTIC_TABLE_PREFIX.'page_hits', 'h');

        $expr = $q2->expr()->andX();

        // If we know the lead, use that to determine uniqueness
        if ($lead !== null && $lead->getId()) {
            $expr->add(
                $q2->expr()->eq('h.lead_id', $lead->getId())
            );
        } else {
            $expr->add(
                $q2->expr()->eq('h.tracking_id', ':id')
            );
            $q->setParameter('id', $trackingId);
        }

        if ($page instanceof Page) {
            $expr->add(
                $q2->expr()->eq('h.page_id', $page->getId())
            );
        } elseif ($page instanceof Redirect) {
            $expr->add(
                $q2->expr()->eq('h.redirect_id', $page->getId())
            );
        }

        $q2->where($expr);

        $q->select('u.is_unique')
            ->from(sprintf('(SELECT (NOT EXISTS (%s)) is_unique)', $q2->getSQL()), 'u');

        return (bool) $q->execute()->fetchColumn();
    }

    /**
     * Get a lead's page hits.
     *
     * @param int   $leadId
     * @param array $options
     *
     * @return array
     */
    public function getLeadHits($leadId, array $options = [])
    {
        $query = $this->getEntityManager()->getConnection()->createQueryBuilder();

        $query->select('h.page_id, h.user_agent as userAgent, h.date_hit as dateHit, h.date_left as dateLeft, h.referer, h.source, h.source_id as sourceId, h.url, h.url_title as urlTitle, h.query, ds.client_info as clientInfo, ds.device, ds.device_os_name as deviceOsName, ds.device_brand as deviceBrand, ds.device_model as deviceModel')
            ->from(MAUTIC_TABLE_PREFIX.'page_hits', 'h')
            ->leftJoin('h', MAUTIC_TABLE_PREFIX.'pages', 'p', 'h.page_id = p.id')
            ->where('h.lead_id = '.(int) $leadId);

        if (isset($options['search']) && $options['search']) {
            $query->andWhere($query->expr()->like('p.title', $query->expr()->literal('%'.$options['search'].'%')));
        }

        $query->leftjoin('h', MAUTIC_TABLE_PREFIX.'lead_devices', 'ds', 'ds.id = h.device_id');

        if (isset($options['url']) && $options['url']) {
            $query->andWhere($query->expr()->eq('h.url', $query->expr()->literal($options['url'])));
        }

        return $this->getTimelineResults($query, $options, 'p.title', 'h.date_hit', ['query'], ['dateHit', 'dateLeft']);
    }

    /**
     * @param      $source
     * @param null $sourceId
     * @param null $fromDate
     *
     * @return array
     */
    public function getHitCountForSource($source, $sourceId = null, $fromDate = null, $code = 200)
    {
        $query = $this->createQueryBuilder('h');
        $query->select('count(distinct(h.trackingId)) as "hitCount"');
        $query->andWhere($query->expr()->eq('h.source', $query->expr()->literal($source)));

        if ($sourceId != null) {
            if (is_array($sourceId)) {
                $query->andWhere($query->expr()->in('h.sourceId', ':sourceIds'))
                    ->setParameter('sourceIds', $sourceId);
            } else {
                $query->andWhere($query->expr()->eq('h.sourceId', (int) $sourceId));
            }
        }

        if ($fromDate != null) {
            $query->andwhere($query->expr()->gte('h.dateHit', ':date'))
                ->setParameter('date', $fromDate);
        }

        $query->andWhere($query->expr()->eq('h.code', (int) $code));

        return $hits = $query->getQuery()->getArrayResult();
    }

    /**
     * Get an array of hits via an email clickthrough.
     *
     * @param           $emailIds
     * @param \DateTime $fromDate
     * @param int       $code
     *
     * @return array
     */
    public function getEmailClickthroughHitCount($emailIds, \DateTime $fromDate = null, $code = 200)
    {
        $q = $this->_em->getConnection()->createQueryBuilder();

        if (!is_array($emailIds)) {
            $emailIds = [$emailIds];
        }

        $q->select('count(distinct(h.tracking_id)) as hit_count, h.email_id')
            ->from(MAUTIC_TABLE_PREFIX.'page_hits', 'h')
            ->where($q->expr()->in('h.email_id', $emailIds))
            ->groupBy('h.email_id');

        if ($fromDate != null) {
            $dateHelper = new DateTimeHelper($fromDate);
            $q->andwhere($q->expr()->gte('h.date_hit', ':date'))
                ->setParameter('date', $dateHelper->toUtcString());
        }

        $q->andWhere($q->expr()->eq('h.code', (int) $code));

        $results = $q->execute()->fetchAll();

        $hits = [];
        foreach ($results as $r) {
            $hits[$r['email_id']] = $r['hit_count'];
        }

        return $hits;
    }

    /**
     * Count returning IP addresses.
     *
     * @return int
     */
    public function countReturningIp()
    {
        $q = $this->createQueryBuilder('h');
        $q->select('COUNT(h.ipAddress) as returning')
            ->groupBy('h.ipAddress')
            ->having($q->expr()->gt('COUNT(h.ipAddress)', 1));
        $results = $q->getQuery()->getResult();

        return count($results);
    }

    /**
     * Count email clickthrough.
     *
     * @return int
     */
    public function countEmailClickthrough()
    {
        $q = $this->createQueryBuilder('h');
        $q->select('COUNT(h.email) as clicks');
        $results = $q->getQuery()->getSingleResult();

        return $results['clicks'];
    }

    /**
     * Count how many visitors hit some page in last X $seconds.
     *
     * @param int  $seconds
     * @param bool $notLeft
     *
     * @return int
     */
    public function countVisitors($seconds = 60, $notLeft = false)
    {
        $now         = new \DateTime();
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
     * Get the latest hit.
     *
     * @param array $options
     *
     * @return \DateTime
     */
    public function getLatestHit($options)
    {
        $sq = $this->_em->getConnection()->createQueryBuilder();
        $sq->select('h.date_hit latest_hit')
            ->from(MAUTIC_TABLE_PREFIX.'page_hits', 'h');

        if (isset($options['leadId'])) {
            $sq->andWhere(
                $sq->expr()->eq('h.lead_id', $options['leadId'])
            );
        }
        if (isset($options['urls']) && $options['urls']) {
            $inUrls = (!is_array($options['urls'])) ? [$options['urls']] : $options['urls'];
            foreach ($inUrls as $k => $u) {
                $sq->andWhere($sq->expr()->like('h.url', ':url_'.$k))
                    ->setParameter('url_'.$k, $u);
            }
        }
        if (isset($options['second_to_last'])) {
            $sq->andWhere($sq->expr()->neq('h.id', $options['second_to_last']));
        } else {
            $sq->orderBy('h.date_hit', 'DESC limit 1');
        }
        $result = $sq->execute()->fetch();

        return new \DateTime($result['latest_hit'], new \DateTimeZone('UTC'));
    }

    /**
     * Get the number of bounces.
     *
     * @param array|string $pageIds
     * @param \DateTime    $fromDate
     * @param bool         $isVariantCheck
     *
     * @return array
     */
    public function getBounces($pageIds, \DateTime $fromDate = null, $isVariantCheck = false)
    {
        $inOrEq = (!is_array($pageIds)) ? 'eq' : 'in';

        $hitsColumn = ($isVariantCheck) ? 'variant_hits' : 'unique_hits';
        $q          = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $pages      = $q->select("p.id, p.$hitsColumn as totalHits, p.title")
            ->from(MAUTIC_TABLE_PREFIX.'pages', 'p')
            ->where($q->expr()->$inOrEq('p.id', $pageIds))
            ->execute()
            ->fetchAll();

        $return = [];
        foreach ($pages as $p) {
            $return[$p['id']] = [
                'totalHits' => (int) $p['totalHits'],
                'bounces'   => 0,
                'rate'      => 0,
                'title'     => $p['title'],
            ];
        }

        // Get the total number of bounces - simplified query for if date_left is null, it'll more than likely be a bounce or
        // else we would have recorded the date_left on a subsequent page hit
        $q    = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $expr = $q->expr()->andX(
            $q->expr()->$inOrEq('h.page_id', $pageIds),
            $q->expr()->eq('h.code', 200),
            $q->expr()->isNull('h.date_left')
        );

        if ($fromDate !== null) {
            //make sure the date is UTC
            $dt = new DateTimeHelper($fromDate, 'Y-m-d H:i:s', 'local');
            $expr->add(
                $q->expr()->gte('h.date_hit', $q->expr()->literal($dt->toUtcString()))
            );
        }

        $q->select('count(*) as bounces, h.page_id')
            ->from(MAUTIC_TABLE_PREFIX.'page_hits', 'h')
            ->where($expr)
            ->groupBy('h.page_id');

        $results = $q->execute()->fetchAll();

        foreach ($results as $p) {
            $return[$p['page_id']]['bounces'] = (int) $p['bounces'];
            $return[$p['page_id']]['rate']    = ($return[$p['page_id']]['totalHits']) ? round(
                ($p['bounces'] / $return[$p['page_id']]['totalHits']) * 100,
                2
            ) : 0;
        }

        return (!is_array($pageIds)) ? $return[$pageIds] : $return;
    }

    /**
     * Get array of dwell time labels with ranges.
     *
     * @return array
     */
    public function getDwellTimeLabels()
    {
        return [
            [
                'label' => '< 1m',
                'from'  => 0,
                'till'  => 60,
            ],
            [
                'label' => '1 - 5m',
                'from'  => 60,
                'till'  => 300,
            ],
            [
                'label' => '5 - 10m',
                'value' => 0,
                'from'  => 300,
                'till'  => 600,
            ],
            [
                'label' => '> 10m',
                'from'  => 600,
                'till'  => 999999,
            ],
        ];
    }

    /**
     * Get the dwell times for bunch of pages.
     *
     * @param array $pageIds
     * @param array $options
     *
     * @return array
     */
    public function getDwellTimesForPages(array $pageIds, array $options)
    {
        $q = $this->_em->getConnection()->createQueryBuilder();
        $q->from(MAUTIC_TABLE_PREFIX.'page_hits', 'ph')
            ->leftJoin('ph', MAUTIC_TABLE_PREFIX.'pages', 'p', 'ph.page_id = p.id')
            ->select('ph.page_id, ph.date_hit, ph.date_left, p.title')
            ->orderBy('ph.date_hit', 'ASC')
            ->andWhere(
                $q->expr()->andX(
                    $q->expr()->in('ph.page_id', $pageIds)
                )
            );

        if (isset($options['fromDate']) && $options['fromDate'] !== null) {
            //make sure the date is UTC
            $dt = new DateTimeHelper($options['fromDate']);
            $q->andWhere(
                $q->expr()->gte('ph.date_hit', $q->expr()->literal($dt->toUtcString()))
            );
        }

        $results = $q->execute()->fetchAll();

        //loop to structure
        $times  = [];
        $titles = [];

        foreach ($results as $r) {
            $dateHit  = $r['date_hit'] ? new \DateTime($r['date_hit']) : 0;
            $dateLeft = $r['date_left'] ? new \DateTime($r['date_left']) : 0;

            $titles[$r['page_id']]  = $r['title'];
            $times[$r['page_id']][] = $dateLeft ? ($dateLeft->getTimestamp() - $dateHit->getTimestamp()) : 0;
        }

        //now loop to create stats
        $stats = [];

        foreach ($times as $pid => $time) {
            $stats[$pid]          = $this->countStats($time);
            $stats[$pid]['title'] = $titles[$pid];
        }

        return $stats;
    }

    /**
     * Get the dwell times for bunch of URLs.
     *
     * @param string $url
     * @param array  $options
     *
     * @return array
     */
    public function getDwellTimesForUrl($url, array $options)
    {
        $q = $this->_em->getConnection()->createQueryBuilder();
        $q->from(MAUTIC_TABLE_PREFIX.'page_hits', 'ph')
            ->leftJoin('ph', MAUTIC_TABLE_PREFIX.'pages', 'p', 'ph.page_id = p.id')
            ->select('ph.id, ph.page_id, ph.date_hit, ph.date_left, ph.tracking_id, ph.page_language, p.title')
            ->orderBy('ph.date_hit', 'ASC')
            ->andWhere($q->expr()->like('ph.url', ':url'))
            ->setParameter('url', $url);

        if (isset($options['leadId']) && $options['leadId']) {
            $q->andWhere(
                $q->expr()->eq('ph.lead_id', (int) $options['leadId'])
            );
        }

        $results = $q->execute()->fetchAll();

        $times = [];

        foreach ($results as $r) {
            $dateHit  = $r['date_hit'] ? new \DateTime($r['date_hit']) : 0;
            $dateLeft = $r['date_left'] ? new \DateTime($r['date_left']) : 0;
            $times[]  = $dateLeft ? ($dateLeft->getTimestamp() - $dateHit->getTimestamp()) : -1;
        }

        return $this->countStats($times);
    }

    /**
     * Count stats from hit times.
     *
     * @param array $times
     *
     * @return array
     */
    public function countStats($times)
    {
        return [
            'sum'     => array_sum($times),
            'min'     => count($times) ? min($times) : 0,
            'max'     => count($times) ? max($times) : 0,
            'average' => count($times) ? round(array_sum($times) / count($times)) : 0,
            'count'   => count($times),
        ];
    }

    /**
     * Update a hit with the the time the user left.
     *
     * @param int $lastHitId
     */
    public function updateHitDateLeft($lastHitId)
    {
        $dt = new DateTimeHelper();
        $q  = $this->_em->getConnection()->createQueryBuilder();
        $q->update(MAUTIC_TABLE_PREFIX.'page_hits')
            ->set('date_left', ':datetime')
            ->where('id = '.(int) $lastHitId)
            ->setParameter('datetime', $dt->toUtcString());
        $q->execute();
    }

    /**
     * Get list of referers ordered by it's count.
     *
     * @param \Doctrine\DBAL\Query\QueryBuilder $query
     * @param int                               $limit
     * @param int                               $offset
     *
     * @return array
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getReferers($query, $limit = 10, $offset = 0)
    {
        $query->select('ph.referer, count(ph.referer) as sessions')
            ->groupBy('ph.referer')
            ->orderBy('sessions', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        return $query->execute()->fetchAll();
    }

    /**
     * Get list of referers ordered by it's count.
     *
     * @param \Doctrine\DBAL\Query\QueryBuilder $query
     * @param int                               $limit
     * @param int                               $offset
     * @param string                            $column
     * @param string                            $as
     *
     * @return array
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getMostVisited($query, $limit = 10, $offset = 0, $column = 'p.hits', $as = '')
    {
        if ($as) {
            $as = ' as "'.$as.'"';
        }

        $query->select('p.title, p.id, '.$column.$as)
            ->groupBy('p.id, p.title, '.$column)
            ->orderBy($column, 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        return $query->execute()->fetchAll();
    }

    /**
     * @param $leadId
     * @param $newTrackingId
     * @param $oldTrackingId
     */
    public function updateLeadByTrackingId($leadId, $newTrackingId, $oldTrackingId)
    {
        $q = $this->_em->getConnection()->createQueryBuilder();
        $q->update(MAUTIC_TABLE_PREFIX.'page_hits')
            ->set('lead_id', (int) $leadId)
            ->set('tracking_id', ':newTrackingId')
            ->where(
                $q->expr()->eq('tracking_id', ':oldTrackingId')
            )
            ->setParameters([
                'newTrackingId' => $newTrackingId,
                'oldTrackingId' => $oldTrackingId,
            ])
            ->execute();
    }

    /**
     * Updates lead ID (e.g. after a lead merge).
     *
     * @param $fromLeadId
     * @param $toLeadId
     */
    public function updateLead($fromLeadId, $toLeadId)
    {
        $q = $this->_em->getConnection()->createQueryBuilder();
        $q->update(MAUTIC_TABLE_PREFIX.'page_hits')
            ->set('lead_id', (int) $toLeadId)
            ->where('lead_id = '.(int) $fromLeadId)
            ->execute();
    }
}
