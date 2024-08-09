<?php

namespace Mautic\PageBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\TimelineTrait;

/**
 * @extends CommonRepository<VideoHit>
 */
class VideoHitRepository extends CommonRepository
{
    use TimelineTrait;

    /**
     * Get video hit info for lead timeline.
     *
     * @param int|null $leadId
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
     * @param int $leadId
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
            ->where('h.lead = :leadId')
            ->setParameter('leadId', (int) $leadId);

        if (isset($options['url']) && $options['url']) {
            $query->andWhere($query->expr()->eq('h.url', $query->expr()->literal($options['url'])));
        }

        return $query->getQuery()->getArrayResult();
    }

    /**
     * Count stats from hit times.
     *
     * @param array $times
     */
    public function countStats($times): array
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
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getReferers($query, $limit = 10, $offset = 0): array
    {
        $query->select('h.referer, count(h.referer) as sessions')
            ->groupBy('h.referer')
            ->orderBy('sessions', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        return $query->executeQuery()->fetchAllAssociative();
    }

    /**
     * Updates lead ID (e.g. after a lead merge).
     */
    public function updateLead($fromLeadId, $toLeadId): void
    {
        $q = $this->_em->getConnection()->createQueryBuilder();
        $q->update(MAUTIC_TABLE_PREFIX.'video_hits')
            ->set('lead_id', (int) $toLeadId)
            ->where('lead_id = '.(int) $fromLeadId)
            ->executeStatement();
    }
}
