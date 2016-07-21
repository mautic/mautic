<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\Entity;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query;
use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\LeadBundle\Entity\Lead;

/**
 * Class VideoHitRepository
 */
class VideoHitRepository extends CommonRepository
{
    /**
     * @param Lead   $lead
     * @param string $guid
     *
     * @return VideoHit
     */
    public function getHitForLeadByGuid(Lead $lead, $guid)
    {
        $result = $this->findOneBy(['guid' => $guid, 'lead' => $lead]);

        return $result ?: new VideoHit;
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
        $query->select('h.userAgent, h.dateHit, h.dateLeft, h.referer, h.source, h.sourceId, h.url, h.duration, h.query, h.timeWatched')
            ->where('h.lead = ' . (int) $leadId);

        if (!empty($options['ipIds'])) {
            $query->orWhere('h.ipAddress IN (' . implode(',', $options['ipIds']) . ')');
        }

        if (isset($options['url']) && $options['url']) {
            $query->andWhere($query->expr()->eq('h.url', $query->expr()->literal($options['url'])));
        }

        return $query->getQuery()->getArrayResult();
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
     * Updates lead ID (e.g. after a lead merge)
     *
     * @param $fromLeadId
     * @param $toLeadId
     */
    public function updateLead($fromLeadId, $toLeadId)
    {
        $q = $this->_em->getConnection()->createQueryBuilder();
        $q->update(MAUTIC_TABLE_PREFIX . 'video_hits')
            ->set('lead_id', (int) $toLeadId)
            ->where('lead_id = ' . (int) $fromLeadId)
            ->execute();
    }
}
