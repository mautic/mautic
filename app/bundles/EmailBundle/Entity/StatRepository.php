<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Entity;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\CoreBundle\Helper\GraphHelper;

/**
 * Class StatRepository
 *
 * @package Mautic\EmailBundle\Entity
 */
class StatRepository extends CommonRepository
{

    /**
     * @param $trackingHash
     *
     * @return mixed
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
     * @param        $emailId
     * @param string $listId
     */
    public function getSentStats($emailId, $listId = null)
    {
        $q = $this->_em->getConnection()->createQueryBuilder();
        $q->select('s.lead_id')
            ->from(MAUTIC_TABLE_PREFIX.'email_stats', 's')
            ->where('s.email_id = :email')
            ->setParameter('email', $emailId);

        if ($listId) {
            $q->andWhere('s.list_id = :list')
                ->setParameter('list', $listId);
        }

        $result = $q->execute()->fetchAll();

        //index by lead
        $stats = array();
        foreach ($result as $r) {
            $stats[$r['lead_id']] = $r['lead_id'];
        }

        unset($result);

        return $stats;
    }

    /**
     * @param $emailId
     * @param $listId
     */
    public function getSentCount($emailId = 0, $listId = 0)
    {
        $q = $this->_em->getConnection()->createQueryBuilder();

        $q->select('count(s.id) as sent_count')
            ->from(MAUTIC_TABLE_PREFIX.'email_stats', 's');

        if ($emailId) {
            $q->where('s.email_id = ' . (int) $emailId);
        }

        if ($listId) {
            $q->andWhere('s.list_id = ' . (int) $listId);
        }

        $q->andWhere('s.is_failed = :false')
            ->setParameter('false', false, 'boolean');

        $results = $q->execute()->fetchAll();

        return (isset($results[0])) ? $results[0]['sent_count'] : 0;
    }

    /**
     * @param $emailId
     * @param $listId
     */
    public function getReadCount($emailId, $listId)
    {
        $q = $this->_em->getConnection()->createQueryBuilder();

        $q->select('count(s.id) as read_count')
            ->from(MAUTIC_TABLE_PREFIX.'email_stats', 's')
            ->where('email_id = ' . $emailId)
            ->andWhere('list_id = ' . $listId)
            ->andWhere('is_read = :true')
            ->setParameter('true', true, 'boolean');
        $results = $q->execute()->fetchAll();

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
        $inIds = (!is_array($emailIds)) ? array($emailIds) : $emailIds;

        $sq = $this->_em->getConnection()->createQueryBuilder();
        $sq->select('e.email_id, count(e.id) as the_count')
            ->from(MAUTIC_TABLE_PREFIX.'email_stats', 'e')
            ->where('e.is_failed = 0')
            ->andWhere($sq->expr()->in('e.email_id', $inIds));

        if ($fromDate !== null) {
            //make sure the date is UTC
            $dt = new DateTimeHelper($fromDate);
            $sq->andWhere(
                $sq->expr()->gte('e.date_read', $sq->expr()->literal($dt->toUtcString()))
            );
        }
        $sq->groupBy('e.email_id');

        //get a total number of sent emails first
        $totalCounts = $sq->execute()->fetchAll();

        $return  = array();
        foreach ($inIds as $id) {
            $return[$id] = array(
                'totalCount' => 0,
                'readCount'  => 0,
                'readRate'   => 0
            );
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
            $return[$r['email_id']]['readCount'] = (int) $r['thecount'];
            $return[$r['email_id']]['readRate']  = ($return[$r['email_id']]['totalCount']) ?
                round(($r['theCount'] / $return[$r['email_id']]['totalCount']) * 100, 2) :
                0;
        }

        return (!is_array($emailIds)) ? $return[$emailIds] : $return;
    }

    /**
     * @param $emailId
     * @param $listId
     */
    public function getFailedCount($emailId, $listId)
    {
        $q = $this->_em->getConnection()->createQueryBuilder();

        $q->select('count(s.id) as failed_count')
            ->from(MAUTIC_TABLE_PREFIX.'email_stats', 's')
            ->where('email_id = ' . $emailId)
            ->andWhere('list_id = ' . $listId)
            ->andWhere('is_failed = :true')
            ->setParameter('true', true, 'boolean');
        $results = $q->execute()->fetchAll();

        return (isset($results[0])) ? $results[0]['failed_count'] : 0;
    }

    /**
     * Get a lead's email stat
     *
     * @param integer $leadId
     * @param array   $options
     *
     * @return array
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getLeadStats($leadId, array $options = array())
    {
        $query = $this->createQueryBuilder('s')
            ->select('IDENTITY(s.email) AS email_id, s.id, s.dateRead, s.dateSent, e.subject, s.isRead, s.isFailed, s.viewedInBrowser, s.retryCount, IDENTITY(s.list) AS list_id, l.name as list_name')
            ->leftJoin('MauticEmailBundle:Email', 'e', 'WITH', 'e.id = s.email')
            ->leftJoin('MauticLeadBundle:LeadList', 'l', 'WITH', 'l.id = s.list')
            ->where('s.lead = ' . $leadId);

        if (!empty($options['ipIds'])) {
            $query->orWhere('s.ipAddress IN (' . implode(',', $options['ipIds']) . ')');
        }

        if (isset($options['filters']['search']) && $options['filters']['search']) {
            $query->andWhere($query->expr()->orX(
                $query->expr()->like('e.subject', $query->expr()->literal('%' . $options['filters']['search'] . '%')),
                $query->expr()->like('e.plainText', $query->expr()->literal('%' . $options['filters']['search'] . '%'))
            ));
        }

        $stats = $query->getQuery()->getArrayResult();

        foreach ($stats as &$stat) {
            $dateSent = new DateTimeHelper($stat['dateSent']);
            if (!empty($stat['dateSent']) && !empty($stat['dateRead'])) {
                $stat['timeToRead'] = $dateSent->getDiff($stat['dateRead']);
            } else {
                $stat['timeToRead'] = false;
            }
        }

        return $stats;
    }

    /**
     * Get pie graph data for Sent, Read and Failed email count
     *
     * @param QueryBuilder $query
     * @param array $args
     *
     * @return array
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getIgnoredReadFailed($query = null, $args = array())
    {
        if (!$query) {
            $query = $this->_em->getConnection()->createQueryBuilder()
                ->from(MAUTIC_TABLE_PREFIX.'email_stats', 'es')
                ->leftJoin('es', MAUTIC_TABLE_PREFIX.'emails', 'e', 'es.email_id = e.id');
        }

        $query->select('count(es.id) as sent, count(CASE WHEN es.is_read THEN 1 ELSE null END) as "read", count(CASE WHEN es.is_failed THEN 1 ELSE null END) as failed');

        if (isset($args['source'])) {
            $query->andWhere($query->expr()->eq('es.source', $query->expr()->literal($args['source'])));
        }

        if (isset($args['source_id'])) {
            $query->andWhere($query->expr()->eq('es.source_id', (int) $args['source_id']));
        }

        $results = $query->execute()->fetch();

        $results['ignored'] = $results['sent'] - $results['read'] - $results['failed'];
        unset($results['sent']);

        return GraphHelper::preparePieGraphData($results);
    }

    /**
     * Get pie graph data for Sent, Read and Failed email count
     *
     * @param QueryBuilder $query
     *
     * @return array
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
     * Get sent counts based grouped by email Id
     *
     * @param array $emailIds
     *
     * @return array
     */
    public function getSentCounts($emailIds = array(), \DateTime $fromDate = null)
    {
        $q = $this->_em->getConnection()->createQueryBuilder();
        $q->select('e.email_id, count(e.id) as sentcount')
            ->from(MAUTIC_TABLE_PREFIX.'email_stats', 'e')
            ->where('e.is_failed = 0')
            ->andWhere($q->expr()->in('e.email_id', $emailIds));

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

        $counts = array();

        foreach ($results as $r) {
            $counts[$r['email_id']] = $r['sentcount'];
        }

        return $counts;
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
        $q->update(MAUTIC_TABLE_PREFIX . 'email_stats')
            ->set('lead_id', (int) $toLeadId)
            ->where('lead_id = ' . (int) $fromLeadId)
            ->execute();
    }
}
