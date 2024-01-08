<?php

namespace Mautic\EmailBundle\Entity;

use Doctrine\DBAL\Connection;
use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\CoreBundle\Helper\Chart\ChartQuery;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\LeadBundle\Entity\TimelineTrait;

/**
 * @extends CommonRepository<Stat>
 */
class StatRepository extends CommonRepository
{
    use TimelineTrait;

    /**
     * @return mixed
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getEmailStatus($trackingHash)
    {
        $q = $this->createQueryBuilder('s');
        $q->select('s')
            ->leftJoin('s.lead', 'l')
            ->leftJoin('s.email', 'e')
            ->where(
                $q->expr()->eq('s.trackingHash', ':hash')
            )
            ->setParameter('hash', $trackingHash);
        $result = $q->getQuery()->getResult();

        return (!empty($result)) ? $result[0] : null;
    }

    /**
     * @param int $contactId
     * @param int $emailId
     *
     * @return array
     */
    public function getUniqueClickedLinksPerContactAndEmail($contactId, $emailId)
    {
        $q = $this->_em->getConnection()->createQueryBuilder();
        $q->select('distinct ph.url, ph.date_hit')
            ->from(MAUTIC_TABLE_PREFIX.'page_hits', 'ph')
            ->where('ph.email_id = :emailId')
            ->andWhere('ph.lead_id = :leadId')
            ->setParameter('leadId', $contactId)
            ->setParameter('emailId', $emailId);

        $result = $q->executeQuery()->fetchAllAssociative();

        foreach ($result as $row) {
            $data[$row['date_hit']] = $row['url'];
        }

        return $data;
    }

    /**
     * @param int      $limit
     * @param int|null $createdByUserId
     * @param int|null $companyId
     * @param int|null $campaignId
     * @param int|null $segmentId
     */
    public function getSentEmailToContactData(
        $limit,
        \DateTime $dateFrom,
        \DateTime $dateTo,
        $createdByUserId = null,
        $companyId = null,
        $campaignId = null,
        $segmentId = null
    ): array {
        $q = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $q->select('s.id, s.lead_id, s.email_address, s.is_read, s.email_id, s.date_sent, s.date_read')
            ->from(MAUTIC_TABLE_PREFIX.'email_stats', 's')
            ->leftJoin('s', MAUTIC_TABLE_PREFIX.'emails', 'e', 's.email_id = e.id')
            ->addSelect('e.name AS email_name')
            ->leftJoin('s', MAUTIC_TABLE_PREFIX.'page_hits', 'ph', 'ph.source = "email" and ph.source_id = s.email_id and ph.lead_id = s.lead_id')
            ->addSelect('COUNT(ph.id) AS link_hits');

        if (null !== $createdByUserId) {
            $q->andWhere('e.created_by = :userId')
                ->setParameter('userId', $createdByUserId);
        }

        $q->andWhere('s.date_sent BETWEEN :dateFrom AND :dateTo')
            ->setParameter('dateFrom', $dateFrom->format('Y-m-d H:i:s'))
            ->setParameter('dateTo', $dateTo->format('Y-m-d H:i:s'));

        $companyJoinOnExpr = $q->expr()->and(
            $q->expr()->eq('s.lead_id', 'cl.lead_id')
        );
        if (!empty($companyId)) {
            // Must force a one to one relationship
            $companyJoinOnExpr->with(
                $q->expr()->eq('cl.is_primary', 1)
            );
        }

        $q->leftJoin('s', MAUTIC_TABLE_PREFIX.'companies_leads', 'cl', $companyJoinOnExpr)
            ->leftJoin('s', MAUTIC_TABLE_PREFIX.'companies', 'c', 'cl.company_id = c.id')
            ->addSelect('c.id AS company_id')
            ->addSelect('c.companyname AS company_name');

        if (!empty($companyId)) {
            $q->andWhere('cl.company_id = :companyId')
                ->setParameter('companyId', $companyId);
        }

        $q->leftJoin('s', MAUTIC_TABLE_PREFIX.'campaign_events', 'ce', 's.source = "campaign.event" and s.source_id = ce.id')
            ->leftJoin('ce', MAUTIC_TABLE_PREFIX.'campaigns', 'campaign', 'ce.campaign_id = campaign.id')
            ->addSelect('campaign.id AS campaign_id')
            ->addSelect('campaign.name AS campaign_name');

        if (null !== $campaignId) {
            $q->andWhere('ce.campaign_id = :campaignId')
                ->setParameter('campaignId', $campaignId);
        }

        $q->leftJoin('s', MAUTIC_TABLE_PREFIX.'lead_lists', 'll', 's.list_id = ll.id')
            ->addSelect('ll.id AS segment_id')
            ->addSelect('ll.name AS segment_name');

        if (null !== $segmentId) {
            $sb = $this->getEntityManager()->getConnection()->createQueryBuilder();
            $sb->select('null')
                ->from(MAUTIC_TABLE_PREFIX.'lead_lists_leads', 'lll')
                ->where(
                    $sb->expr()->and(
                        $sb->expr()->eq('lll.leadlist_id', ':segmentId'),
                        $sb->expr()->eq('lll.lead_id', 'ph.lead_id'),
                        $sb->expr()->eq('lll.manually_removed', 0)
                    )
                );

            // Filter for both broadcasts and campaign related segments
            $q->andWhere(
                $q->expr()->or(
                    $q->expr()->eq('s.list_id', ':segmentId'),
                    $q->expr()->and(
                        $q->expr()->isNull('s.list_id'),
                        sprintf('EXISTS (%s)', $sb->getSQL())
                    )
                )
            )
                ->setParameter('segmentId', $segmentId);
        }

        $q->setMaxResults($limit);
        $q->groupBy('s.id');
        $q->orderBy('s.id', 'DESC');

        return $q->executeQuery()->fetchAllAssociative();
    }

    /**
     * @param array<int,int|string>|int|null      $emailIds
     * @param array<int,int|string>|int|true|null $listId
     */
    public function getSentStats($emailIds, $listId = null): array
    {
        if (!is_array($emailIds)) {
            $emailIds = [(int) $emailIds];
        }

        $q = $this->_em->getConnection()->createQueryBuilder();
        $q->select('s.lead_id')
            ->from(MAUTIC_TABLE_PREFIX.'email_stats', 's')
            ->where(
                $q->expr()->in('s.email_id', $emailIds)
            );

        if ($listId) {
            $q->andWhere('s.list_id = :list')
                ->setParameter('list', $listId);
        }

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
     * @param array<int,int|string>|int|null      $emailIds
     * @param array<int,int|string>|int|true|null $listId
     * @param bool                                $combined
     *
     * @return array|int
     */
    public function getSentCount($emailIds = null, $listId = null, ChartQuery $chartQuery = null, $combined = false)
    {
        return $this->getStatusCount('is_sent', $emailIds, $listId, $chartQuery, $combined);
    }

    /**
     * @param array<int,int|string>|int|null $emailIds
     * @param array<int,int|string>|int|null $listId
     * @param bool                           $combined
     *
     * @return array|int
     */
    public function getReadCount($emailIds = null, $listId = null, ChartQuery $chartQuery = null, $combined = false)
    {
        return $this->getStatusCount('is_read', $emailIds, $listId, $chartQuery, $combined);
    }

    /**
     * @param array<int,int|string>|int|null      $emailIds
     * @param array<int,int|string>|int|true|null $listId
     * @param bool                                $combined
     *
     * @return array|int
     */
    public function getFailedCount($emailIds = null, $listId = null, ChartQuery $chartQuery = null, $combined = false)
    {
        return $this->getStatusCount('is_failed', $emailIds, $listId, $chartQuery, $combined);
    }

    /**
     * @param string                              $column
     * @param array<int,int|string>|int|null      $emailIds
     * @param array<int,int|string>|int|true|null $listId
     * @param bool                                $combined
     *
     * @return array|int
     */
    public function getStatusCount($column, $emailIds = null, $listId = null, ChartQuery $chartQuery = null, $combined = false)
    {
        $q = $this->_em->getConnection()->createQueryBuilder();

        $q->select('count(s.id) as count')
            ->from(MAUTIC_TABLE_PREFIX.'email_stats', 's');

        if ($emailIds) {
            if (!is_array($emailIds)) {
                $emailIds = [(int) $emailIds];
            }
            $q->where(
                $q->expr()->in('s.email_id', $emailIds)
            );
        }

        if ($listId) {
            if (!$combined) {
                if (true === $listId) {
                    $q->addSelect('s.list_id')
                        ->groupBy('s.list_id');
                } elseif (is_array($listId)) {
                    $q->andWhere(
                        $q->expr()->in('s.list_id', ':segmentIds')
                    );
                    $q->setParameter('segmentIds', $listId, Connection::PARAM_INT_ARRAY);

                    $q->addSelect('s.list_id')
                        ->groupBy('s.list_id');
                } else {
                    $q->andWhere('s.list_id = :list_id')
                        ->setParameter('list_id', $listId);
                }
            } else {
                $subQ = $this->getEntityManager()->getConnection()->createQueryBuilder();
                $subQ->select('null')
                    ->from(MAUTIC_TABLE_PREFIX.'lead_lists_leads', 'list')
                    ->andWhere(
                        $q->expr()->and(
                            $q->expr()->in('list.leadlist_id', array_map('intval', $listId)),
                            $q->expr()->eq('list.lead_id', 's.lead_id')
                        )
                    );

                $q->andWhere(sprintf('EXISTS (%s)', $subQ->getSQL()));
            }
        }

        if ('is_sent' === $column) {
            $q->andWhere('s.is_failed = :false')
                ->setParameter('false', false, 'boolean');
        } else {
            $q->andWhere($column.' = :true')
                ->setParameter('true', true, 'boolean');
        }

        if ($chartQuery) {
            if ('is_read' === $column) {
                $chartQuery->applyDateFilters($q, 'date_read', 's');
            } else {
                $chartQuery->applyDateFilters($q, 'date_sent', 's');
            }
        }

        $results = $q->executeQuery()->fetchAllAssociative();

        if ((true === $listId || is_array($listId)) && !$combined) {
            // Return list group of counts
            $byList = [];
            foreach ($results as $result) {
                $byList[$result['list_id']] = $result['count'];
            }

            return $byList;
        }

        return (isset($results[0])) ? $results[0]['count'] : 0;
    }

    /**
     * @param array<int,int|string>|int $emailIds
     */
    public function getOpenedRates($emailIds, \DateTime $fromDate = null): array
    {
        $inIds = (!is_array($emailIds)) ? [$emailIds] : $emailIds;

        $sq = $this->_em->getConnection()->createQueryBuilder();
        $sq->select('e.email_id, count(e.id) as the_count')
            ->from(MAUTIC_TABLE_PREFIX.'email_stats', 'e')
            ->where(
                $sq->expr()->and(
                    $sq->expr()->eq('e.is_failed', ':false'),
                    $sq->expr()->in('e.email_id', $inIds)
                )
            )->setParameter('false', false, 'boolean');

        if (null !== $fromDate) {
            // make sure the date is UTC
            $dt = new DateTimeHelper($fromDate);
            $sq->andWhere(
                $sq->expr()->gte('e.date_sent', $sq->expr()->literal($dt->toUtcString()))
            );
        }
        $sq->groupBy('e.email_id');

        // get a total number of sent emails first
        $totalCounts = $sq->executeQuery()->fetchAllAssociative();

        $return = [];
        foreach ($inIds as $id) {
            $return[$id] = [
                'totalCount' => 0,
                'readCount'  => 0,
                'readRate'   => 0,
            ];
        }

        foreach ($totalCounts as $t) {
            if (null != $t['email_id']) {
                $return[$t['email_id']]['totalCount'] = (int) $t['the_count'];
            }
        }

        // now get a read count
        $sq->andWhere('e.is_read = :true')
            ->setParameter('true', true, 'boolean');
        $readCounts = $sq->executeQuery()->fetchAllAssociative();

        foreach ($readCounts as $r) {
            $return[$r['email_id']]['readCount'] = (int) $r['the_count'];
            $return[$r['email_id']]['readRate']  = ($return[$r['email_id']]['totalCount']) ?
                round(($r['the_count'] / $return[$r['email_id']]['totalCount']) * 100, 2) :
                0;
        }

        return (!is_array($emailIds)) ? $return[$emailIds] : $return;
    }

    /**
     * @param array<int,int|string>|int $emailIds
     *
     * @return array<int, array<string, mixed>>
     */
    public function getOpenedStatIds($emailIds = null, $listId = null): array
    {
        $q = $this->_em->getConnection()->createQueryBuilder();

        $q->select('s.id')
            ->from(MAUTIC_TABLE_PREFIX.'email_stats', 's');

        if ($emailIds) {
            if (!is_array($emailIds)) {
                $emailIds = [(int) $emailIds];
            }
            $q->where(
                $q->expr()->in('s.email_id', $emailIds)
            );
        }

        $q->andWhere('open_count > 0');

        if ($listId) {
            $q->andWhere('s.list_id = '.(int) $listId);
        }

        return $q->executeQuery()->fetchAllAssociative();
    }

    /**
     * Get a lead's email stat.
     *
     * @param int $leadId
     *
     * @return array
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getLeadStats($leadId, array $options = [])
    {
        $query = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $query->from(MAUTIC_TABLE_PREFIX.'email_stats', 's')
            ->leftJoin('s', MAUTIC_TABLE_PREFIX.'emails', 'e', 's.email_id = e.id')
            ->leftJoin('s', MAUTIC_TABLE_PREFIX.'email_copies', 'ec', 's.copy_id = ec.id');

        if ($leadId) {
            $query->andWhere(
                $query->expr()->eq('s.lead_id', (int) $leadId)
            );
        }

        if (!empty($options['basic_select'])) {
            $query->select(
                's.email_id, s.id, s.date_read as dateRead, s.date_sent as dateSent, e.subject, e.name as email_name, s.is_read as isRead, s.is_failed as isFailed, ec.subject as storedSubject'
            );
        } else {
            $query->select(
                's.email_id, s.id, s.date_read as dateRead, s.date_sent as dateSent,e.subject, e.name as email_name, s.is_read as isRead, s.is_failed as isFailed, s.viewed_in_browser as viewedInBrowser, s.retry_count as retryCount, s.list_id, l.name as list_name, s.tracking_hash as idHash, s.open_details as openDetails, ec.subject as storedSubject, s.lead_id'
            )
                ->leftJoin('s', MAUTIC_TABLE_PREFIX.'lead_lists', 'l', 's.list_id = l.id');
        }

        if (isset($options['state'])) {
            $state = $options['state'];
            if ('read' == $state) {
                $query->andWhere(
                    $query->expr()->eq('s.is_read', 1)
                );
            } elseif ('failed' == $state) {
                $query->andWhere(
                    $query->expr()->eq('s.is_failed', 1)
                );
            }
        }
        $state = 'sent';

        if (isset($options['search']) && $options['search']) {
            $query->andWhere(
                $query->expr()->or(
                    $query->expr()->like('ec.subject', $query->expr()->literal('%'.$options['search'].'%')),
                    $query->expr()->like('e.subject', $query->expr()->literal('%'.$options['search'].'%')),
                    $query->expr()->like('e.name', $query->expr()->literal('%'.$options['search'].'%'))
                )
            );
        }

        if (isset($options['fromDate']) && $options['fromDate']) {
            $dt = new DateTimeHelper($options['fromDate']);
            $query->andWhere(
                $query->expr()->gte('s.date_sent', $query->expr()->literal($dt->toUtcString()))
            );
        }

        $timeToReadParser = function (&$stat): void {
            $dateSent = new DateTimeHelper($stat['dateSent']);
            if (!empty($stat['dateSent']) && !empty($stat['dateRead'])) {
                $stat['timeToRead'] = $dateSent->getDiff($stat['dateRead']);
            } else {
                $stat['timeToRead'] = false;
            }
        };

        return $this->getTimelineResults(
            $query,
            $options,
            'storedSubject, e.subject',
            's.date_'.$state,
            ['openDetails'],
            ['dateRead', 'dateSent'],
            $timeToReadParser
        );
    }

    /**
     * Get counts for Sent, Read and Failed emails.
     *
     * @param QueryBuilder $query
     *
     * @return array
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getIgnoredReadFailed($query = null)
    {
        $query->select('count(es.id) as sent, count(CASE WHEN es.is_read THEN 1 ELSE null END) as "read", count(CASE WHEN es.is_failed THEN 1 ELSE null END) as failed');

        $results = $query->execute()->fetchAssociative();

        if ($results) {
            $results['ignored'] = $results['sent'] - $results['read'] - $results['failed'];
            unset($results['sent']);
        } else {
            $results['ignored'] = $results['sent'] = $results['read'] = $results['failed']  = 0;
        }

        return $results;
    }

    /**
     * Get pie graph data for Sent, Read and Failed email count.
     *
     * @param QueryBuilder $query
     *
     * @return array
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getMostEmails($query, $limit = 10, $offset = 0)
    {
        $query
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        return $query->execute()->fetchAllAssociative();
    }

    /**
     * Get sent counts based grouped by email Id.
     *
     * @param array $emailIds
     */
    public function getSentCounts($emailIds = [], \DateTime $fromDate = null): array
    {
        $q = $this->_em->getConnection()->createQueryBuilder();
        $q->select('e.email_id, count(e.id) as sentcount')
            ->from(MAUTIC_TABLE_PREFIX.'email_stats', 'e')
            ->where(
                $q->expr()->and(
                    $q->expr()->in('e.email_id', $emailIds),
                    $q->expr()->eq('e.is_failed', ':false')
                )
            )->setParameter('false', false, 'boolean');

        if (null !== $fromDate) {
            // make sure the date is UTC
            $dt = new DateTimeHelper($fromDate);
            $q->andWhere(
                $q->expr()->gte('e.date_read', $q->expr()->literal($dt->toUtcString()))
            );
        }
        $q->groupBy('e.email_id');

        // get a total number of sent emails first
        $results = $q->executeQuery()->fetchAllAssociative();

        $counts = [];

        foreach ($results as $r) {
            $counts[$r['email_id']] = $r['sentcount'];
        }

        return $counts;
    }

    /**
     * Updates lead ID (e.g. after a lead merge).
     */
    public function updateLead($fromLeadId, $toLeadId): void
    {
        $q = $this->_em->getConnection()->createQueryBuilder();
        $q->update(MAUTIC_TABLE_PREFIX.'email_stats')
            ->set('lead_id', (int) $toLeadId)
            ->where('lead_id = '.(int) $fromLeadId)
            ->executeStatement();
    }

    /**
     * Delete a stat.
     */
    public function deleteStat($id): void
    {
        $this->getEntityManager()->getConnection()->delete(MAUTIC_TABLE_PREFIX.'email_stats', ['id' => (int) $id]);
    }

    public function deleteStats(array $ids): void
    {
        $qb = $this->getEntityManager()->getConnection()->createQueryBuilder();

        $qb->delete(MAUTIC_TABLE_PREFIX.'email_stats')
            ->where(
                $qb->expr()->in('id', $ids)
            )
            ->executeStatement();
    }

    public function getTableAlias(): string
    {
        return 's';
    }

    /**
     * @return array
     */
    public function findContactEmailStats($leadId, $emailId)
    {
        return $this->createQueryBuilder('s')
            ->where('IDENTITY(s.lead) = :leadId AND IDENTITY(s.email) =  :emailId')
            ->setParameter('leadId', (int) $leadId)
            ->setParameter('emailId', (int) $emailId)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return mixed
     */
    public function checkContactsSentEmail($contacts, $emailId)
    {
        $query = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $query->from(MAUTIC_TABLE_PREFIX.'email_stats', 's');
        $query->select('id, lead_id')
            ->where('s.email_id = :email')
            ->andWhere('s.lead_id in (:contacts)')
            ->andWhere('is_failed = 0')
            ->setParameter('email', $emailId)
            ->setParameter('contacts', $contacts);

        return $query->executeQuery()->fetchAssociative();
    }

    /**
     * @return array Formatted as [contactId => sentCount]
     */
    public function getSentCountForContacts(array $contacts, $emailId): array
    {
        $query = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $query->from(MAUTIC_TABLE_PREFIX.'email_stats', 's');
        $query->select('count(s.id) as sent_count, s.lead_id')
            ->where('s.email_id = :email')
            ->andWhere('s.lead_id in (:contacts)')
            ->andWhere('s.is_failed = 0')
            ->setParameter('email', $emailId)
            ->setParameter('contacts', $contacts, Connection::PARAM_INT_ARRAY)
            ->groupBy('s.lead_id');

        $results = $query->executeQuery()->fetchAllAssociative();

        $contacts = [];
        foreach ($results as $result) {
            $contacts[$result['lead_id']] = $result['sent_count'];
        }

        return $contacts;
    }

    /**
     * @param array<int> $contacts
     *
     * @return array<int, array<string, int|float>>
     */
    public function getStatsSummaryForContacts(array $contacts): array
    {
        $queryBuilder               = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $subQueryBuilder            = $this->getEntityManager()->getConnection()->createQueryBuilder();

        $leadAlias     = 'l'; // leads
        $statsAlias    = 'es'; // email_stats
        $subQueryAlias = 'sq'; // sub query
        $cutAlias      = 'cut'; // channel_url_trackables
        $pageHitsAlias = 'ph'; // page_hits

        // use sub query to get page hits for and unique page hits selected contacts
        $subQueryBuilder->select(
            "COUNT({$pageHitsAlias}.id) AS hits",
            "COUNT(DISTINCT({$pageHitsAlias}.redirect_id)) AS unique_hits",
            "{$cutAlias}.channel_id",
            "{$pageHitsAlias}.lead_id"
        )
            ->from(MAUTIC_TABLE_PREFIX.'channel_url_trackables', $cutAlias)
            ->join(
                $cutAlias,
                MAUTIC_TABLE_PREFIX.'page_hits',
                $pageHitsAlias,
                "{$cutAlias}.redirect_id = {$pageHitsAlias}.redirect_id AND {$cutAlias}.channel_id = {$pageHitsAlias}.source_id"
            )
            ->where("{$cutAlias}.channel = 'email' AND {$pageHitsAlias}.source = 'email'")
            ->andWhere("{$pageHitsAlias}.lead_id in (:contacts)")
            ->setParameter('contacts', $contacts, Connection::PARAM_INT_ARRAY)
            ->groupBy("{$cutAlias}.channel_id, {$pageHitsAlias}.lead_id");

        // main query
        $queryBuilder->select(
            "{$leadAlias}.id AS `lead_id`",
            "COUNT({$statsAlias}.id) AS `sent_count`",
            "SUM(IF({$statsAlias}.is_read IS NULL, 0, {$statsAlias}.is_read)) AS `read_count`",
            "SUM(IF({$subQueryAlias}.hits is NULL, 0, 1)) AS `clicked_through_count`",
        )->from(MAUTIC_TABLE_PREFIX.'email_stats', $statsAlias)
            ->rightJoin(
                $statsAlias,
                MAUTIC_TABLE_PREFIX.'leads',
                $leadAlias,
                "{$statsAlias}.lead_id=l.id"
            )->leftJoin(
                $statsAlias,
                "({$subQueryBuilder->getSQL()})",
                $subQueryAlias,
                "{$statsAlias}.email_id = {$subQueryAlias}.channel_id AND {$statsAlias}.lead_id = {$subQueryAlias}.lead_id"
            )->andWhere("{$leadAlias}.id in (:contacts)")
            ->setParameter('contacts', $contacts, Connection::PARAM_INT_ARRAY)
            ->groupBy("{$leadAlias}.id");

        $results = $queryBuilder->executeQuery()->fetchAllAssociative();

        $contacts = [];
        foreach ($results as $result) {
            $sentCount    = (int) $result['sent_count'];
            $readCount    = (int) $result['read_count'];
            $clickedCount = (int) $result['clicked_through_count'];

            $contacts[(int) $result['lead_id']] = [
                'sent_count'              => $sentCount,
                'read_count'              => $readCount,
                'clicked_count'           => $clickedCount,
                'open_rate'               => round($sentCount > 0 ? ($readCount / $sentCount) : 0, 4),
                'click_through_rate'      => round($sentCount > 0 ? ($clickedCount / $sentCount) : 0, 4),
                'click_through_open_rate' => round($readCount > 0 ? ($clickedCount / $readCount) : 0, 4),
            ];
        }

        return $contacts;
    }
}
