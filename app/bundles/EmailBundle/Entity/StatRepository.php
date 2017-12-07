<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\CoreBundle\Helper\Chart\ChartQuery;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\LeadBundle\Entity\TimelineTrait;

/**
 * Class StatRepository.
 */
class StatRepository extends CommonRepository
{
    use TimelineTrait;

    /**
     * @param $trackingHash
     *
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
     * @param      $emailId
     * @param null $listId
     *
     * @return array
     */
    public function getSentStats($emailIds, $listId = null)
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

        $result = $q->execute()->fetchAll();

        //index by lead
        $stats = [];
        foreach ($result as $r) {
            $stats[$r['lead_id']] = $r['lead_id'];
        }

        unset($result);

        return $stats;
    }

    /**
     * @param null            $emailIds
     * @param null            $listId
     * @param ChartQuery|null $chartQuery
     *
     * @return array|int
     */
    public function getSentCount($emailIds = null, $listId = null, ChartQuery $chartQuery = null)
    {
        $q = $this->_em->getConnection()->createQueryBuilder();

        $q->select('count(s.id) as sent_count')
            ->from(MAUTIC_TABLE_PREFIX.'email_stats', 's');

        if ($emailIds) {
            if (!is_array($emailIds)) {
                $emailIds = [(int) $emailIds];
            }
            $q->where(
                $q->expr()->in('s.email_id', $emailIds)
            );
        }

        if (true === $listId) {
            $q->addSelect('s.list_id')
                ->groupBy('s.list_id');
        } elseif ($listId) {
            $q->andWhere('s.list_id = '.(int) $listId);
        }

        $q->andWhere('s.is_failed = :false')
            ->setParameter('false', false, 'boolean');

        if ($chartQuery) {
            $chartQuery->applyDateFilters($q, 'date_sent', 's');
        }

        $results = $q->execute()->fetchAll();

        if (true === $listId) {
            // Return list group of counts
            $byList = [];
            foreach ($results as $result) {
                $byList[$result['list_id']] = $result['sent_count'];
            }

            return $byList;
        }

        return (isset($results[0])) ? $results[0]['sent_count'] : 0;
    }

    /**
     * @param null            $emailIds
     * @param null            $listId
     * @param ChartQuery|null $chartQuery
     *
     * @return array|int
     */
    public function getReadCount($emailIds = null, $listId = null, ChartQuery $chartQuery = null)
    {
        $q = $this->_em->getConnection()->createQueryBuilder();

        $q->select('count(s.id) as read_count')
            ->from(MAUTIC_TABLE_PREFIX.'email_stats', 's');

        if ($emailIds) {
            if (!is_array($emailIds)) {
                $emailIds = [(int) $emailIds];
            }
            $q->where(
                $q->expr()->in('s.email_id', $emailIds)
            );
        }

        if (true === $listId) {
            $q->addSelect('s.list_id')
                ->groupBy('s.list_id');
        } elseif ($listId) {
            $q->andWhere('s.list_id = '.(int) $listId);
        }

        $q->andWhere('is_read = :true')
            ->setParameter('true', true, 'boolean');

        if ($chartQuery) {
            $chartQuery->applyDateFilters($q, 'date_sent', 's');
        }

        $results = $q->execute()->fetchAll();

        if (true === $listId) {
            // Return list group of counts
            $byList = [];
            foreach ($results as $result) {
                $byList[$result['list_id']] = $result['read_count'];
            }

            return $byList;
        }

        return (isset($results[0])) ? $results[0]['read_count'] : 0;
    }

    /**
     * @param           $emailIds
     * @param \DateTime $fromDate
     *
     * @return array
     */
    public function getOpenedRates($emailIds, \DateTime $fromDate = null)
    {
        $inIds = (!is_array($emailIds)) ? [$emailIds] : $emailIds;

        $sq = $this->_em->getConnection()->createQueryBuilder();
        $sq->select('e.email_id, count(e.id) as the_count')
            ->from(MAUTIC_TABLE_PREFIX.'email_stats', 'e')
            ->where(
                $sq->expr()->andX(
                    $sq->expr()->eq('e.is_failed', ':false'),
                    $sq->expr()->in('e.email_id', $inIds)
                )
            )->setParameter('false', false, 'boolean');

        if ($fromDate !== null) {
            //make sure the date is UTC
            $dt = new DateTimeHelper($fromDate);
            $sq->andWhere(
                $sq->expr()->gte('e.date_sent', $sq->expr()->literal($dt->toUtcString()))
            );
        }
        $sq->groupBy('e.email_id');

        //get a total number of sent emails first
        $totalCounts = $sq->execute()->fetchAll();

        $return = [];
        foreach ($inIds as $id) {
            $return[$id] = [
                'totalCount' => 0,
                'readCount'  => 0,
                'readRate'   => 0,
            ];
        }

        foreach ($totalCounts as $t) {
            if ($t['email_id'] != null) {
                $return[$t['email_id']]['totalCount'] = (int) $t['the_count'];
            }
        }

        //now get a read count
        $sq->andWhere('e.is_read = :true')
            ->setParameter('true', true, 'boolean');
        $readCounts = $sq->execute()->fetchAll();

        foreach ($readCounts as $r) {
            $return[$r['email_id']]['readCount'] = (int) $r['the_count'];
            $return[$r['email_id']]['readRate']  = ($return[$r['email_id']]['totalCount']) ?
                round(($r['the_count'] / $return[$r['email_id']]['totalCount']) * 100, 2) :
                0;
        }

        return (!is_array($emailIds)) ? $return[$emailIds] : $return;
    }

    /**
     * @param null            $emailIds
     * @param null            $listId
     * @param ChartQuery|null $chartQuery
     *
     * @return array|int
     */
    public function getFailedCount($emailIds = null, $listId = null, ChartQuery $chartQuery = null)
    {
        $q = $this->_em->getConnection()->createQueryBuilder();

        $q->select('count(s.id) as failed_count')
            ->from(MAUTIC_TABLE_PREFIX.'email_stats', 's');

        if ($emailIds) {
            if (!is_array($emailIds)) {
                $emailIds = [(int) $emailIds];
            }
            $q->where(
                $q->expr()->in('s.email_id', $emailIds)
            );
        }

        if (true === $listId) {
            $q->addSelect('s.list_id')
                ->groupBy('s.list_id');
        } elseif ($listId) {
            $q->andWhere('s.list_id = '.(int) $listId);
        }

        $q->andWhere('is_failed = :true')
            ->setParameter('true', true, 'boolean');

        if ($chartQuery) {
            $chartQuery->applyDateFilters($q, 'date_sent', 's');
        }

        $results = $q->execute()->fetchAll();

        if (true === $listId) {
            // Return list group of counts
            $byList = [];
            foreach ($results as $result) {
                $byList[$result['list_id']] = $result['failed_count'];
            }

            return $byList;
        }

        return (isset($results[0])) ? $results[0]['failed_count'] : 0;
    }

    /**
     * @param array|int $emailIds
     *
     * @return int
     */
    public function getOpenedStatIds($emailIds = null, $listId = null)
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

        $results = $q->execute()->fetchAll();

        return $results;
    }

    /**
     * Get a lead's email stat.
     *
     * @param int   $leadId
     * @param array $options
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
            } elseif ('sent' == $state) {
                // Get only those that have not been read yet
                $query->andWhere(
                    $query->expr()->eq('s.is_read', 0)
                );
                $query->andWhere(
                    $query->expr()->eq('s.is_failed', 0)
                );
            } elseif ('failed' == $state) {
                $query->andWhere(
                    $query->expr()->eq('s.is_failed', 1)
                );
                $state = 'sent';
            }
        } else {
            $state = 'sent';
        }

        if (isset($options['search']) && $options['search']) {
            $query->andWhere(
                $query->expr()->orX(
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

        $timeToReadParser = function (&$stat) {
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

        $results = $query->execute()->fetch();

        $results['ignored'] = $results['sent'] - $results['read'] - $results['failed'];
        unset($results['sent']);

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

        $results = $query->execute()->fetchAll();

        return $results;
    }

    /**
     * Get sent counts based grouped by email Id.
     *
     * @param array $emailIds
     *
     * @return array
     */
    public function getSentCounts($emailIds = [], \DateTime $fromDate = null)
    {
        $q = $this->_em->getConnection()->createQueryBuilder();
        $q->select('e.email_id, count(e.id) as sentcount')
            ->from(MAUTIC_TABLE_PREFIX.'email_stats', 'e')
            ->where(
                $q->expr()->andX(
                    $q->expr()->in('e.email_id', $emailIds),
                    $q->expr()->eq('e.is_failed', ':false')
                )
            )->setParameter('false', false, 'boolean');

        if ($fromDate !== null) {
            //make sure the date is UTC
            $dt = new DateTimeHelper($fromDate);
            $q->andWhere(
                $q->expr()->gte('e.date_read', $q->expr()->literal($dt->toUtcString()))
            );
        }
        $q->groupBy('e.email_id');

        //get a total number of sent emails first
        $results = $q->execute()->fetchAll();

        $counts = [];

        foreach ($results as $r) {
            $counts[$r['email_id']] = $r['sentcount'];
        }

        return $counts;
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
        $q->update(MAUTIC_TABLE_PREFIX.'email_stats')
            ->set('lead_id', (int) $toLeadId)
            ->where('lead_id = '.(int) $fromLeadId)
            ->execute();
    }

    /**
     * Delete a stat.
     *
     * @param $id
     */
    public function deleteStat($id)
    {
        $this->_em->getConnection()->delete(MAUTIC_TABLE_PREFIX.'email_stats', ['id' => (int) $id]);
        $this->_em->getConnection()->delete(MAUTIC_TABLE_PREFIX.'email_stats_devices', ['stat_id' => (int) $id]);
    }

    /**
     * @return string
     */
    public function getTableAlias()
    {
        return 's';
    }

    /**
     * @param $leadId
     * @param $emailId
     *
     * @return array
     */
    public function findContactEmailStats($leadId, $emailId)
    {
        return $this->createQueryBuilder('s')
            ->where('IDENTITY(s.lead) = '.(int) $leadId.' AND IDENTITY(s.email) = '.(int) $emailId)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param $contacts
     * @param $emailId
     *
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
        ->setParameter(':email', $emailId)
        ->setParameter(':contacts', $contacts);

        $results = $query->execute()->fetch();

        return $results;
    }
}
