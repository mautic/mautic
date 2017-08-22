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
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\TimelineTrait;

/**
 * Class VideoHitRepository.
 */
class VideoHitRepository extends CommonRepository
{
    use TimelineTrait;

    /**
     * Get video hit info for lead timeline.
     *
     * @param int|null $leadId
     * @param array    $options
     *
     * @return array
     */
    public function getTimelineStats($leadId = null, array $options = [])
    {
        $query = $this->getEntityManager()->getConnection()->createQueryBuilder();

        $query->select('h.id, h.url, h.date_hit, h.time_watched, h.duration, h.referer, h.user_agent')
            ->from(MAUTIC_TABLE_PREFIX.'video_hits', 'h');

        if ($leadId) {
            $query->where($query->expr()->eq('h.lead_id', (int) $leadId));
        }

        if (isset($options['search']) && $options['search']) {
            $query->andWhere(
                $query->expr()->like('h.url', $query->expr()->literal('%'.$options['search'].'%'))
            );
        }

        return $this->getTimelineResults($query, $options, 'h.url', 'h.date_hit', [], ['date_hit']);
    }

    /**
     * @param Lead   $lead
     * @param string $guid
     *
     * @return VideoHit
     */
    public function getHitForLeadByGuid(Lead $lead, $guid)
    {
        $result = $this->findOneBy(['guid' => $guid, 'lead' => $lead]);

        return $result ?: new VideoHit();
    }

    /**
     * Get a lead's page hits.
     *
     * @param int   $leadId
     * @param array $options
     *
     * @return array
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getLeadHits($leadId, array $options = [])
    {
        $query = $this->createQueryBuilder('h');
        $query->select('h.userAgent, h.dateHit, h.dateLeft, h.referer, h.channel, h.channelId, h.url, h.duration, h.query, h.timeWatched')
            ->where('h.lead = '.(int) $leadId);

        if (isset($options['url']) && $options['url']) {
            $query->andWhere($query->expr()->eq('h.url', $query->expr()->literal($options['url'])));
        }

        return $query->getQuery()->getArrayResult();
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
        $query->select('h.referer, count(h.referer) as sessions')
            ->groupBy('h.referer')
            ->orderBy('sessions', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        return $query->execute()->fetchAll();
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
        $q->update(MAUTIC_TABLE_PREFIX.'video_hits')
            ->set('lead_id', (int) $toLeadId)
            ->where('lead_id = '.(int) $fromLeadId)
            ->execute();
    }
}
