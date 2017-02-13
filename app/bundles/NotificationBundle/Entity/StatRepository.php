<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\NotificationBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\CoreBundle\Helper\DateTimeHelper;

/**
 * Class StatRepository.
 */
class StatRepository extends CommonRepository
{
    /**
     * @param $trackingHash
     *
     * @return mixed
     *
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
     * Updates lead ID (e.g. after a lead merge).
     *
     * @param      $notificationId
     * @param null $listId
     *
     * @return array
     */
    public function getSentStats($notificationId, $listId = null)
    {
        $q = $this->_em->getConnection()->createQueryBuilder();
        $q->select('s.lead_id')
            ->from(MAUTIC_TABLE_PREFIX.'push_notification_stats', 's')
            ->where('s.notification_id = :notification')
            ->setParameter('notification', $notificationId);

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
     * @param int|array $notificationIds
     * @param int       $listId
     *
     * @return int
     */
    public function getSentCount($notificationIds = null, $listId = null)
    {
        $q = $this->_em->getConnection()->createQueryBuilder();

        $q->select('count(s.id) as sent_count')
            ->from(MAUTIC_TABLE_PREFIX.'push_notification_stats', 's');

        if ($notificationIds) {
            if (!is_array($notificationIds)) {
                $notificationIds = [(int) $notificationIds];
            }
            $q->where(
                $q->expr()->in('s.notification_id', $notificationIds)
            );
        }

        if ($listId) {
            $q->andWhere('s.list_id = '.(int) $listId);
        }

        $q->andWhere('s.is_failed = :false')
            ->setParameter('false', false, 'boolean');

        $results = $q->execute()->fetchAll();

        return (isset($results[0])) ? $results[0]['sent_count'] : 0;
    }

    /**
     * @param array|int $notificationIds
     * @param int       $listId
     *
     * @return int
     */
    public function getReadCount($notificationIds = null, $listId = null)
    {
        $q = $this->_em->getConnection()->createQueryBuilder();

        $q->select('count(s.id) as read_count')
            ->from(MAUTIC_TABLE_PREFIX.'push_notification_stats', 's');

        if ($notificationIds) {
            if (!is_array($notificationIds)) {
                $notificationIds = [(int) $notificationIds];
            }
            $q->where(
                $q->expr()->in('s.notification_id', $notificationIds)
            );
        }

        if ($listId) {
            $q->andWhere('s.list_id = '.(int) $listId);
        }

        $q->andWhere('is_read = :true')
            ->setParameter('true', true, 'boolean');
        $results = $q->execute()->fetchAll();

        return (isset($results[0])) ? $results[0]['read_count'] : 0;
    }

    /**
     * Get a contact's notifications stat.
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
        $query = $this->createQueryBuilder('s');

        $query->select('IDENTITY(s.notification) AS notification_id, s.id, s.dateRead, s.dateSent, e.title, s.isRead, s.retryCount, IDENTITY(s.list) AS list_id, l.name as list_name, s.trackingHash as idHash, s.clickDetails')
            ->leftJoin('MauticNotificationBundle:Notification', 'e', 'WITH', 'e.id = s.notification')
            ->leftJoin('MauticLeadBundle:LeadList', 'l', 'WITH', 'l.id = s.list')
            ->where(
                $query->expr()->andX(
                    $query->expr()->eq('IDENTITY(s.lead)', $leadId),
                    $query->expr()->eq('s.isFailed', ':false'))
            )->setParameter('false', false, 'boolean');

        if (isset($options['search']) && $options['search']) {
            $query->andWhere(
                $query->expr()->like('e.title', $query->expr()->literal('%'.$options['search'].'%'))
            );
        }

        if (isset($options['order'])) {
            list($orderBy, $orderByDir) = $options['order'];

            switch ($orderBy) {
                case 'eventLabel':
                    $orderBy = 'e.title';
                    break;
                case 'timestamp':
                default:
                    $orderBy = 'e.dateRead, e.dateSent';
                    break;
            }

            $query->orderBy($orderBy, $orderByDir);
        }

        if (!empty($options['limit'])) {
            $query->setMaxResults($options['limit']);

            if (!empty($options['start'])) {
                $query->setFirstResult($options['start']);
            }
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
     * Get pie graph data for Sent, Read and Failed notifications count.
     *
     * @param QueryBuilder $query
     *
     * @return array
     *
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
     * Get sent counts based grouped by notification Id.
     *
     * @param array $notificationIds
     *
     * @return array
     */
    public function getSentCounts($notificationIds = [], \DateTime $fromDate = null)
    {
        $q = $this->_em->getConnection()->createQueryBuilder();
        $q->select('e.email_id, count(e.id) as sentcount')
            ->from(MAUTIC_TABLE_PREFIX.'push_notification_stats', 'e')
            ->where(
                $q->expr()->in('e.notification_id', $notificationIds)
            );

        if ($fromDate !== null) {
            //make sure the date is UTC
            $dt = new DateTimeHelper($fromDate);
            $q->andWhere(
                $q->expr()->gte('e.date_read', $q->expr()->literal($dt->toUtcString()))
            );
        }
        $q->groupBy('e.notification_id');

        //get a total number of sent notifications first
        $results = $q->execute()->fetchAll();

        $counts = [];

        foreach ($results as $r) {
            $counts[$r['notification_id']] = $r['sentcount'];
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
        $q->update(MAUTIC_TABLE_PREFIX.'push_notification_stats')
            ->set('notification_id', (int) $toLeadId)
            ->where('notification_id = '.(int) $fromLeadId)
            ->execute();
    }

    /**
     * Delete a stat.
     *
     * @param $id
     */
    public function deleteStat($id)
    {
        $this->_em->getConnection()->delete(MAUTIC_TABLE_PREFIX.'push_notification_stats', ['id' => (int) $id]);
    }

    /**
     * @return string
     */
    public function getTableAlias()
    {
        return 's';
    }
}
