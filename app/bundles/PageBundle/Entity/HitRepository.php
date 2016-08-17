<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\Entity;

use Doctrine\ORM\Query;
use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\LeadBundle\Entity\TimelineTrait;

/**
 * Class HitRepository
 */
class HitRepository extends CommonRepository
{
    use TimelineTrait;

    /**
     * Determine if the page hit is a unique
     *
     * @param Page|Redirect $page
     * @param string        $trackingId
     *
     * @return bool
     */
    public function isUniquePageHit($page, $trackingId)
    {
        $q  = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $q2 = $this->getEntityManager()->getConnection()->createQueryBuilder();

        $q2->select('null')
            ->from(MAUTIC_TABLE_PREFIX.'page_hits', 'h');

        $expr = $q2->expr()->andX(
            $q2->expr()->eq('h.tracking_id', ':id')
        );

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
            ->from(sprintf('(SELECT (NOT EXISTS (%s)) is_unique)', $q2->getSQL()), 'u'
        )
            ->setParameter('id', $trackingId);

        return (bool) $q->execute()->fetchColumn();
    }

    /**
     * Get a lead's page hits
     *
     * @param integer $leadId
     * @param array   $options
     *
     * @return array
     */
    public function getLeadHits($leadId, array $options = array())
    {
        $query = $this->getEntityManager()->getConnection()->createQueryBuilder();

        $query->select('h.page_id, h.user_agent as userAgent, h.date_hit as dateHit, h.date_left as dateLeft, h.referer, h.source, h.source_id as sourceId, h.url, h.url_title as urlTitle, h.query, ds.client_info as clientInfo, ds.device, ds.device_os_name as deviceOsName, ds.device_brand as deviceBrand, ds.device_model as deviceModel')
            ->from(MAUTIC_TABLE_PREFIX.'page_hits', 'h')
            ->leftJoin('h', MAUTIC_TABLE_PREFIX.'pages', 'p', 'h.page_id = p.id')
            ->where('h.lead_id = ' . (int) $leadId);

        if (isset($options['search']) && $options['search']) {
            $query->andWhere($query->expr()->like('p.title', $query->expr()->literal('%' . $options['search'] . '%')));
        }

        $query->leftjoin('h',MAUTIC_TABLE_PREFIX.'lead_devices', 'ds', 'ds.id = h.device_id');

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
        $query->select("count(distinct(h.trackingId)) as \"hitCount\"");
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
     * Get an array of hits via an email clickthrough
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
            $emailIds = array($emailIds);
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

        $results = $q->execute()->fetchAll();;

        $hits = array();
        foreach ($results as $r) {
            $hits[$r['email_id']] = $r['hit_count'];
        }

        return $hits;
    }

    /**
     * Count returning IP addresses
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
     * Count email clickthrough
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
     * Count how many visitors hit some page in last X $seconds
     *
     * @param int  $seconds
     * @param bool $notLeft
     *
     * @return int
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
     * Get the latest hit
     *
     * @param array $options
     *
     * @return \DateTime
     */
    public function getLatestHit($options)
    {
        $sq = $this->_em->getConnection()->createQueryBuilder();
        $sq->select('MAX(h.date_hit) latest_hit')
            ->from(MAUTIC_TABLE_PREFIX.'page_hits', 'h');

        if (isset($options['leadId'])) {
            $sq->andWhere(
                $sq->expr()->eq('h.lead_id', $options['leadId'])
            );
        }

        if (isset($options['urls']) && $options['urls']) {
            $inUrls = (!is_array($options['urls'])) ? array($options['urls']) : $options['urls'];
            foreach ($inUrls as $k => $u) {
                $sq->andWhere($sq->expr()->like('h.url', ':url_'.$k))
                    ->setParameter('url_'.$k, $u);
            }
        }

        $result = $sq->execute()->fetch();

        return new \DateTime($result['latest_hit']);
    }

    /**
     * Get the number of bounces
     *
     * @param array|string $pageIds
     * @param \DateTime    $fromDate
     *
     * @return array
     */
    public function getBounces($pageIds, \DateTime $fromDate = null)
    {
        $inIds = (!is_array($pageIds)) ? array($pageIds) : $pageIds;

        // Get the total number of hits
        $q = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $q->select('p.id, p.unique_hits')
            ->from(MAUTIC_TABLE_PREFIX.'pages', 'p')
            ->where($q->expr()->in('p.id', $inIds));
        $results = $q->execute()->fetchAll();

        $return  = array();
        foreach ($results as $p) {
            $return[$p['id']] = array(
                'totalHits' => $p['unique_hits'],
                'bounces'   => 0,
                'rate'      => 0
            );
        }

        // Find what sessions were bounces
        $sq = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $sq->select('b.tracking_id')
            ->from(MAUTIC_TABLE_PREFIX.'page_hits', 'b')
            ->leftJoin('b', MAUTIC_TABLE_PREFIX.'pages', 'p', 'b.page_id = p.id')
            ->andWhere($sq->expr()->eq('b.code', '200'));

        if ($fromDate !== null) {
            //make sure the date is UTC
            $dt = new DateTimeHelper($fromDate);
            $sq->andWhere(
                $sq->expr()->gte('b.date_hit', $sq->expr()->literal($dt->toUtcString()))
            );
        }

        // Group by tracking ID to determine if the same session visited multiple pages
        $sq->groupBy('b.tracking_id');

        // Include if a single hit to page or multiple hits to the same page
        $sq->having('count(distinct(b.page_id)) = 1');

        // Load this data into a temporary table
        $platform = $this->getEntityManager()->getConnection()->getDatabasePlatform();
        $tempTableName = $platform->getTemporaryTableName('tmp_0');
        $sql = $platform->getCreateTemporaryTableSnippetSQL().' '.$tempTableName.' AS ('.$sq->getSQL().');';
        $stmt = $this->getEntityManager()->getConnection()->prepare($sql);
        $stmt->execute();

        // Now group bounced sessions by page_id to get the number of bounces per page
        $q = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $q->select('h.page_id, count(distinct(h.tracking_id)) as bounces')
            ->from(MAUTIC_TABLE_PREFIX.'page_hits', 'h')
            ->innerJoin(
                'h',
                $tempTableName,
                't',
                $q->expr()->andx(
                    $q->expr()->eq('h.tracking_id', 't.tracking_id'),
                    $q->expr()->in('h.page_id', $inIds)
                )
            )
            ->groupBy('h.page_id');

        $results = $q->execute()->fetchAll();

        // Drop the temporary table now
        $stmt = $this->getEntityManager()->getConnection()->prepare($platform->getDropTemporaryTableSQL($tempTableName));
        $stmt->execute();

        foreach ($results as $r) {
            $return[$r['page_id']]['bounces'] = (int) $r['bounces'];
            $return[$r['page_id']]['rate']    = ($return[$r['page_id']]['totalHits']) ?
                round(($r['bounces'] / $return[$r['page_id']]['totalHits']) * 100, 2) :
                0;
        }

        return (!is_array($pageIds)) ? $return[$pageIds] : $return;
    }

    /**
     * Get array of dwell time labels with ranges
     *
     * @return array
     */
    public function getDwellTimeLabels()
    {
        return array(
            array(
                'label' => '< 1m',
                'from' => 0,
                'till' => 60
            ),
            array(
                'label' => '1 - 5m',
                'from' => 60,
                'till' => 300
            ),
            array(
                'label' => '5 - 10m',
                'value' => 0,
                'from' => 300,
                'till' => 600
            ),
            array(
                'label' => '> 10m',
                'from' => 600,
                'till' => 999999
            )
        );
    }

    /**
     * Get the dwell times for bunch of pages
     *
     * @param array $pageIds
     * @param array $options
     *
     * @return array
     */
    public function getDwellTimesForPages(array $pageIds, array $options)
    {
        $q = $this->_em->getConnection()->createQueryBuilder();
        $q->from(MAUTIC_TABLE_PREFIX . 'page_hits', 'ph')
            ->leftJoin('ph', MAUTIC_TABLE_PREFIX . 'pages', 'p', 'ph.page_id = p.id')
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
        $times = array();
        $titles = array();

        foreach ($results as $r) {
            $dateHit  = $r['date_hit'] ? new \DateTime($r['date_hit']) : 0;
            $dateLeft = $r['date_left'] ? new \DateTime($r['date_left']) : 0;

            $titles[$r['page_id']] = $r['title'];
            $times[$r['page_id']][] = $dateLeft ? ($dateLeft->getTimestamp() - $dateHit->getTimestamp()) : 0;
        }

        //now loop to create stats
        $stats = array();

        foreach ($times as $pid => $time) {
            $stats[$pid] = $this->countStats($time);
            $stats[$pid]['title'] = $titles[$pid];
        }

        return $stats;
    }

    /**
     * Get the dwell times for bunch of URLs
     *
     * @param string $url
     * @param array  $options
     *
     * @return array
     */
    public function getDwellTimesForUrl($url, array $options)
    {
        $q = $this->_em->getConnection()->createQueryBuilder();
        $q->from(MAUTIC_TABLE_PREFIX . 'page_hits', 'ph')
            ->leftJoin('ph', MAUTIC_TABLE_PREFIX . 'pages', 'p', 'ph.page_id = p.id')
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

        $times = array();

        foreach ($results as $r) {
            $dateHit  = $r['date_hit'] ? new \DateTime($r['date_hit']) : 0;
            $dateLeft = $r['date_left'] ? new \DateTime($r['date_left']) : 0;
            $times[]  = $dateLeft ? ($dateLeft->getTimestamp() - $dateHit->getTimestamp()) : 0;
        }

        return $this->countStats($times);
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
        return array(
            'sum'     => array_sum($times),
            'min'     => count($times) ? min($times) : 0,
            'max'     => count($times) ? max($times) : 0,
            'average' => count($times) ? round(array_sum($times) / count($times)) : 0,
            'count'   => count($times)
        );
    }

    /**
     * Update a hit with the the time the user left
     *
     * @param int $lastHitId
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
     * @param \Doctrine\DBAL\Query\QueryBuilder $query
     * @param int                               $limit
     * @param int                               $offset
     *
     * @return array
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
     * Get list of referers ordered by it's count
     *
     * @param \Doctrine\DBAL\Query\QueryBuilder $query
     * @param int                               $limit
     * @param int                               $offset
     * @param string                            $column
     * @param string                            $as
     *
     * @return array
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getMostVisited($query, $limit = 10, $offset = 0, $column = 'p.hits', $as = '')
    {
        if ($as) {
            $as = ' as "' . $as . '"';
        }

        $query->select('p.title, p.id, ' . $column . $as)
            ->groupBy('p.id, p.title, ' . $column)
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
        $q->update(MAUTIC_TABLE_PREFIX . 'page_hits')
            ->set('lead_id', (int) $leadId)
            ->set('tracking_id', ':newTrackingId')
            ->where(
                $q->expr()->eq('tracking_id', ':oldTrackingId')
            )
            ->setParameters(array(
                'newTrackingId' => $newTrackingId,
                'oldTrackingId' => $oldTrackingId
            ))
            ->execute();
    }

    /**
     * Updates lead ID (e.g. after a lead merge)
     *
     * @param $fromLeadId
     * @param $toLeadId
     */
    public function updateLead($fromLeadId, $toLeadId)
    {
        $q = $this->_em->getConnection()->createQueryBuilder();
        $q->update(MAUTIC_TABLE_PREFIX . 'page_hits')
            ->set('lead_id', (int) $toLeadId)
            ->where('lead_id = ' . (int) $fromLeadId)
            ->execute();
    }
}
