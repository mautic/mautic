<?php

namespace Mautic\DynamicContentBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\LeadBundle\Entity\TimelineTrait;

/**
 * @extends CommonRepository<Stat>
 */
class StatRepository extends CommonRepository
{
    use TimelineTrait;

    public function getSentStats($dynamicContentId): array
    {
        $q = $this->_em->getConnection()->createQueryBuilder();
        $q->select('s.lead_id')
            ->from(MAUTIC_TABLE_PREFIX.'dynamic_content_stats', 's')
            ->where('s.dynamic_content_id = :dynamic_content')
            ->setParameter('dynamic_content', $dynamicContentId);

        $result = $q->executeQuery()->fetchAllAssociative();

        // index by lead
        $stats = [];

        foreach ($result as $r) {
            $stats[$r['lead_id']] = $r['lead_id'];
        }

        unset($result);

        return $stats;
    }

    /**
     * @param int|array $dynamicContentIds
     *
     * @return int
     */
    public function getSentCount($dynamicContentIds = null)
    {
        $q = $this->_em->getConnection()->createQueryBuilder();

        $q->select('count(s.id) as sent_count')
            ->from(MAUTIC_TABLE_PREFIX.'dynamic_content_stats', 's');

        if ($dynamicContentIds) {
            if (!is_array($dynamicContentIds)) {
                $dynamicContentIds = [(int) $dynamicContentIds];
            }
            $q->where(
                $q->expr()->in('s.dynamic_content_id', $dynamicContentIds)
            );
        }

        $results = $q->executeQuery()->fetchAllAssociative();

        return (isset($results[0])) ? $results[0]['sent_count'] : 0;
    }

    /**
     * Get sent counts based grouped by dynamic content Id.
     *
     * @param array $dynamicContentIds
     */
    public function getSentCounts($dynamicContentIds = [], \DateTime $fromDate = null): array
    {
        $q = $this->_em->getConnection()->createQueryBuilder();
        $q->select('s.dynamic_content_id, count(s.id) as sent_count')
            ->from(MAUTIC_TABLE_PREFIX.'dynamic_content_stats', 's')
            ->andWhere(
                $q->expr()->in('e.dynamic_content_id', $dynamicContentIds)
            );

        if (null !== $fromDate) {
            // make sure the date is UTC
            $dt = new DateTimeHelper($fromDate);
            $q->andWhere(
                $q->expr()->gte('e.date_sent', $q->expr()->literal($dt->toUtcString()))
            );
        }
        $q->groupBy('e.dynamic_content_id');

        // get a total number of sent DC stats first
        $results = $q->executeQuery()->fetchAllAssociative();

        $counts = [];

        foreach ($results as $r) {
            $counts[$r['dynamic_content_id']] = $r['sent_count'];
        }

        return $counts;
    }

    /**
     * Get a lead's dynamic content stat.
     *
     * @param int|null $leadId
     *
     * @return array
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getLeadStats($leadId = null, array $options = [])
    {
        $query = $this->getEntityManager()->getConnection()->createQueryBuilder();

        $query->select('dc.id AS dynamic_content_id, s.id, s.date_sent as dateSent, dc.name, s.sent_details as sentDetails, s.lead_id')
            ->from(MAUTIC_TABLE_PREFIX.'dynamic_content_stats', 's')
            ->leftJoin('s', MAUTIC_TABLE_PREFIX.'dynamic_content', 'dc', 'dc.id = s.dynamic_content_id');

        if ($leadId) {
            $query->where($query->expr()->eq('s.lead_id', (int) $leadId));
        }

        if (isset($options['search']) && $options['search']) {
            $query->andWhere(
                $query->expr()->like('dc.name', $query->expr()->literal('%'.$options['search'].'%'))
            );
        }

        return $this->getTimelineResults($query, $options, 'dc.name', 's.date_sent', ['sentDetails'], ['dateSent']);
    }

    /**
     * Updates lead ID (e.g. after a lead merge).
     */
    public function updateLead($fromLeadId, $toLeadId): void
    {
        $q = $this->_em->getConnection()->createQueryBuilder();
        $q->update(MAUTIC_TABLE_PREFIX.'dynamic_content_stats')
            ->set('lead_id', (int) $toLeadId)
            ->where('lead_id = '.(int) $fromLeadId)
            ->executeStatement();
    }

    /**
     * Delete a stat.
     */
    public function deleteStat($id): void
    {
        $this->_em->getConnection()->delete(MAUTIC_TABLE_PREFIX.'dynamic_content_stats', ['id' => (int) $id]);
    }

    public function getTableAlias(): string
    {
        return 's';
    }
}
