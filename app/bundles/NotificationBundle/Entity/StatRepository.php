<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\NotificationBundle\Entity;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\CoreBundle\Helper\GraphHelper;

/**
 * Class StatRepository
 *
 * @package Mautic\NotificationBundle\Entity
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
    public function getNotificationStatus($trackingHash)
    {
        $q = $this->createQueryBuilder('s');
        $q->select('s')
            ->leftJoin('s.lead', 'l')
            ->leftJoin('s.notification', 'e')
            ->where(
                $q->expr()->eq('s.trackingHash', ':hash')
            )
            ->setParameter('hash', $trackingHash);

        $result = $q->getQuery()->getResult();

        return (!empty($result)) ? $result[0] : null;
    }

    /**
     * @param      $notificationId
     * @param null $listId
     *
     * @return array
     */
    public function getSentStats($notificationId, $listId = null)
    {
        $q = $this->_em->getConnection()->createQueryBuilder();
        $q->select('s.lead_id')
            ->from(MAUTIC_TABLE_PREFIX . 'push_notification_stats', 's')
            ->where('s.notification_id = :notification')
            ->setParameter('notification', $notificationId);

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
     * @param int|array $notificationIds
     * @param int       $listId
     *
     * @return int
     */
    public function getSentCount($notificationIds = null, $listId = null)
    {
        $q = $this->_em->getConnection()->createQueryBuilder();

        $q->select('count(s.id) as sent_count')
            ->from(MAUTIC_TABLE_PREFIX . 'push_notification_stats', 's');

        if ($notificationIds) {
            if (!is_array($notificationIds)) {
                $notificationIds = array((int) $notificationIds);
            }
            $q->where(
                $q->expr()->in('s.notification_id', $notificationIds)
            );
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
     * @param array|int $emailIds
     * @param int       $listId
     *
     * @return int
     */
    public function getReadCount($emailIds = null, $listId = null)
    {
        $q = $this->_em->getConnection()->createQueryBuilder();

        $q->select('count(s.id) as read_count')
            ->from(MAUTIC_TABLE_PREFIX . 'push_notification_stats', 's');

        if ($emailIds) {
            if (!is_array($emailIds)) {
                $emailIds = array((int) $emailIds);
            }
            $q->where(
                $q->expr()->in('s.notification_id', $emailIds)
            );
        }

        if ($listId) {
            $q->andWhere('s.list_id = ' . (int) $listId);
        }

        $q->andWhere('is_read = :true')
            ->setParameter('true', true, 'boolean');
        $results = $q->execute()->fetchAll();

        return (isset($results[0])) ? $results[0]['read_count'] : 0;
    }

    /**
     * @param           $notificationIds
     * @param \DateTime $fromDate
     *
     * @return array
     */
    public function getClickedRates($notificationIds, \DateTime $fromDate = null)
    {
        $inIds = (!is_array($notificationIds)) ? array($notificationIds) : $notificationIds;

        $sq = $this->_em->getConnection()->createQueryBuilder();
        $sq->select('e.email_id, count(e.id) as the_count')
            ->from(MAUTIC_TABLE_PREFIX . 'push_notification_stats', 'e')
            ->where(
                $sq->expr()->in('e.notification_id', $inIds)
            );

        if ($fromDate !== null) {
            //make sure the date is UTC
            $dt = new DateTimeHelper($fromDate);
            $sq->andWhere(
                $sq->expr()->gte('e.date_sent', $sq->expr()->literal($dt->toUtcString()))
            );
        }
        $sq->groupBy('e.notification_id');

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
            if ($t['notification_id'] != null) {
                $return[$t['notification_id']]['totalCount'] = (int) $t['the_count'];
            }
        }

        //now get a read count
        $sq->andWhere('e.is_read = :true')
            ->setParameter('true', true, 'boolean');
        $readCounts = $sq->execute()->fetchAll();

        foreach ($readCounts as $r) {
            $return[$r['notification_id']]['readCount'] = (int) $r['the_count'];
            $return[$r['notification_id']]['readRate']  = ($return[$r['notification_id']]['totalCount']) ?
                round(($r['the_count'] / $return[$r['notification_id']]['totalCount']) * 100, 2) :
                0;
        }

        return (!is_array($notificationIds)) ? $return[$notificationIds] : $return;
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
        $query = $this->createQueryBuilder('s');

        $query->select('IDENTITY(s.notification) AS notification_id, s.id, s.dateRead, s.dateSent, e.title, s.isRead, s.retryCount, IDENTITY(s.list) AS list_id, l.name as list_name, s.trackingHash as idHash, s.clickDetails')
            ->leftJoin('MauticNotificationBundle:Notification', 'e', 'WITH', 'e.id = s.notification')
            ->leftJoin('MauticLeadBundle:LeadList', 'l', 'WITH', 'l.id = s.list')
            ->where(
                $query->expr()->andX(
                    $query->expr()->eq('IDENTITY(s.lead)', $leadId),
                    $query->expr()->eq('s.isFailed', ':false'))
            )->setParameter('false', false, 'boolean');

        if (!empty($options['ipIds'])) {
            $query->orWhere('s.ipAddress IN (' . implode(',', $options['ipIds']) . ')');
        }

        if (isset($options['filters']['search']) && $options['filters']['search']) {
            $query->andWhere(
                $query->expr()->like('e.title', $query->expr()->literal('%' . $options['filters']['search'] . '%'))
            );
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
                ->from(MAUTIC_TABLE_PREFIX . 'push_notification_stats', 'es')
                ->leftJoin('es', MAUTIC_TABLE_PREFIX . 'push_notification', 'e', 'es.notification_id = e.id');
        }

        $query->select('count(es.id) as sent, count(CASE WHEN es.is_read THEN 1 ELSE null END) as "clicked"');

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
    public function getMostNotifications($query, $limit = 10, $offset = 0)
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
            ->from(MAUTIC_TABLE_PREFIX . 'push_notification_stats', 'e')
            ->where(
                $q->expr()->in('e.notification_id', $emailIds)
            );

        if ($fromDate !== null) {
            //make sure the date is UTC
            $dt = new DateTimeHelper($fromDate);
            $q->andWhere(
                $q->expr()->gte('e.date_read', $q->expr()->literal($dt->toUtcString()))
            );
        }
        $q->groupBy('e.notification_id');

        //get a total number of sent emails first
        $results = $q->execute()->fetchAll();

        $counts = array();

        foreach ($results as $r) {
            $counts[$r['notification_id']] = $r['sentcount'];
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
        $q->update(MAUTIC_TABLE_PREFIX . 'push_notification_stats')
            ->set('notification_id', (int) $toLeadId)
            ->where('notification_id = ' . (int) $fromLeadId)
            ->execute();
    }

    /**
     * Delete a stat
     *
     * @param $id
     */
    public function deleteStat($id)
    {
        $this->_em->getConnection()->delete(MAUTIC_TABLE_PREFIX . 'push_notification_stats', array('id' => (int) $id));
    }

    /**
     * Fetch stats for some period of time.
     *
     * @param $notificationIds
     * @param $fromDate
     * @param $state
     *
     * @return mixed
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getNotificationStats($notificationIds, $fromDate, $state)
    {
        if (!is_array($notificationIds)) {
            $notificationIds = array((int) $notificationIds);
        }

        // Load points for selected period
        $q = $this->createQueryBuilder('s');

        $dateColumn = ($state == 'sent') ? 'dateSent' : 'dateRead';

        $q->select('s.id, 1 as data, s.'.$dateColumn.' as date');

        $q->where(
            $q->expr()->in('IDENTITY(s.notification)', ':notifications')
        )
            ->setParameter('notifications', $notificationIds);

        if ($state != 'sent') {
            $q->andWhere(
                $q->expr()->eq('s.is'.ucfirst($state), ':true')
            )
                ->setParameter('true', true, 'boolean');
        }

        $q->andwhere(
            $q->expr()->gte('s.'.$dateColumn, ':date')
        )
            ->setParameter('date', $fromDate)
            ->orderBy('s.'.$dateColumn, 'ASC');

        $stats = $q->getQuery()->getArrayResult();

        return $stats;
    }

    /**
     * @return string
     */
    public function getTableAlias()
    {
        return 's';
    }
}
