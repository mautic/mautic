<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Entity;

use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\TimelineTrait;

/**
 * AuditLogRepository.
 */
class AuditLogRepository extends CommonRepository
{
    use TimelineTrait;

    /**
     * @param Lead  $lead
     * @param array $filters
     *
     * @return int
     */
    public function getAuditLogsCount(Lead $lead, array $filters = null)
    {
        $query = $this->_em->getConnection()->createQueryBuilder()
            ->from(MAUTIC_TABLE_PREFIX.'audit_log', 'al')
            ->select('count(*)')
            ->where('al.object = \'lead\'')
            ->andWhere('al.object_id = :id')
            ->setParameter('id', $lead->getId());

        if (is_array($filters) && !empty($filters['search'])) {
            $query->andWhere('al.details like \'%'.$filters['search'].'%\'');
        }

        if (is_array($filters) && !empty($filters['includeEvents'])) {
            $includeList = "'".implode("','", $filters['includeEvents'])."'";
            $query->andWhere('al.action in ('.$includeList.')');
        }

        if (is_array($filters) && !empty($filters['excludeEvents'])) {
            $excludeList = "'".implode("','", $filters['excludeEvents'])."'";
            $query->andWhere('al.action not in ('.$excludeList.')');
        }

        return $query->execute()->fetchColumn();
    }

    /**
     * @param Lead       $lead
     * @param array      $filters
     * @param array|null $orderBy
     * @param int        $page
     * @param int        $limit
     *
     * @return array
     */
    public function getAuditLogs(Lead $lead, array $filters = null, array $orderBy = null, $page = 1, $limit = 25)
    {
        $query = $this->createQueryBuilder('al')
            ->select('al.userName, al.userId, al.bundle, al.object, al.objectId, al.action, al.details, al.dateAdded, al.ipAddress')
            ->where('al.bundle = \'lead\'')
            ->andWhere('al.object = \'lead\'')
            ->andWhere('al.objectId = :id')
            ->setParameter('id', $lead->getId());

        if (is_array($filters) && !empty($filters['search'])) {
            $query->andWhere('al.details like \'%'.$filters['search'].'%\'');
        }

        if (is_array($filters) && !empty($filters['includeEvents'])) {
            $includeList = "'".implode("','", $filters['includeEvents'])."'";
            $query->andWhere('al.action in ('.$includeList.')');
        }

        if (is_array($filters) && !empty($filters['excludeEvents'])) {
            $excludeList = "'".implode("','", $filters['excludeEvents'])."'";
            $query->andWhere('al.action not in ('.$excludeList.')');
        }

        if (0 === $page) {
            $page = 1;
        }
        $query->setFirstResult(($page - 1) * $limit);
        $query->setMaxResults($limit);

        if (is_array($orderBy)) {
            $orderdir = 'ASC';
            $order    = 'id';
            if (isset($orderBy[0])) {
                $order = $orderBy[0];
            }
            if (isset($orderBy[1])) {
                $orderdir = $orderBy[1];
            }
            if (0 !== strpos($order, 'al.')) {
                $order = 'al.'.$order;
            }

            $query->orderBy($order, $orderdir);
        }

        return $query->getQuery()->getArrayResult();
    }

    /**
     * @param array $filters
     * @param $listOfContacts
     *
     * @return array
     */
    public function getAuditLogsForLeads(array $listOfContacts, array $filters = null, array $orderBy = null, $dateAdded = null)
    {
        $query = $this->createQueryBuilder('al')
            ->select('al.userName, al.userId, al.bundle, al.object, al.objectId, al.action, al.details, al.dateAdded, al.ipAddress')
            ->where('al.bundle = \'lead\'')
            ->andWhere('al.object = \'lead\'');
        $query
            ->andWhere($query->expr()->in('al.objectId', $listOfContacts));

        if (is_array($filters) && !empty($filters['search'])) {
            $query->andWhere('al.details like \'%'.$filters['search'].'%\'');
        }

        if (is_array($filters) && !empty($filters['includeEvents'])) {
            $includeList = "'".implode("','", $filters['includeEvents'])."'";
            $query->andWhere('al.action in ('.$includeList.')');
        }

        if ($dateAdded) {
            $query->andWhere($query->expr()->gte('al.dateAdded', ':dateAdded'))->setParameter('dateAdded', $dateAdded);
        }

        if (is_array($filters) && !empty($filters['excludeEvents'])) {
            $excludeList = "'".implode("','", $filters['excludeEvents'])."'";
            $query->andWhere('al.action not in ('.$excludeList.')');
        }

        if (is_array($orderBy)) {
            $orderdir = 'ASC';
            $order    = 'id';
            if (isset($orderBy[0])) {
                $order = $orderBy[0];
            }
            if (isset($orderBy[1])) {
                $orderdir = $orderBy[1];
            }
            if (0 !== strpos($order, 'al.')) {
                $order = 'al.'.$order;
            }

            $query->orderBy($order, $orderdir);
        }

        return $query->getQuery()->getArrayResult();
    }
    /**
     * Get array of objects which belongs to the object.
     *
     * @param null $object
     * @param null $id
     * @param int  $limit
     * @param null $afterDate
     * @param null $bundle
     *
     * @return array
     */
    public function getLogForObject($object = null, $id = null, $limit = 10, $afterDate = null, $bundle = null)
    {
        $query = $this->createQueryBuilder('al')
            ->select('al.userName, al.userId, al.bundle, al.object, al.objectId, al.action, al.details, al.dateAdded, al.ipAddress')
            ->where('al.object != :category')
            ->setParameter('category', 'category');

        if (null != $object && null !== $id) {
            $query
                ->andWhere('al.object = :object')
                ->andWhere('al.objectId = :id')
                ->setParameter('object', $object)
                ->setParameter('id', $id);
        }

        if ($bundle) {
            $query->andWhere('al.bundle = :bundle')
                ->setParameter('bundle', $bundle);
        }

        // Prevent InnoDB shared IDs
        if ($afterDate) {
            $query->andWhere(
                $query->expr()->gte('al.dateAdded', ':date')
            )
                ->setParameter('date', $afterDate);
        }

        $query->orderBy('al.dateAdded', 'DESC')
            ->setMaxResults($limit);

        return $query->getQuery()->getArrayResult();
    }

    /**
     * @param Lead|null $lead
     * @param array     $options
     *
     * @return array
     */
    public function getLeadIpLogs(Lead $lead = null, array $options = [])
    {
        $qb  = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $sqb = $this->getEntityManager()->getConnection()->createQueryBuilder();

        $sqb
            ->select('MAX(l.date_added) as date_added, MIN(l.id) as id, l.ip_address, l.object_id as lead_id')
            ->from(MAUTIC_TABLE_PREFIX.'audit_log', 'l')
            ->where(
                $sqb->expr()->andX(
                    $sqb->expr()->eq('l.bundle', $sqb->expr()->literal('lead')),
                    $sqb->expr()->eq('l.object', $sqb->expr()->literal('lead')),
                    $sqb->expr()->eq('l.action', $sqb->expr()->literal('ipadded'))
                )
            )
            ->groupBy('l.ip_address');

        if ($lead instanceof Lead) {
            // Just a check to ensure reused IDs (happens with innodb) doesn't infect data
            $dt = new DateTimeHelper($lead->getDateAdded(), 'Y-m-d H:i:s', 'local');

            $sqb->andWhere(
                $sqb->expr()->andX(
                    $sqb->expr()->eq('l.object_id', $lead->getId()),
                    $sqb->expr()->gte('l.date_added', $sqb->expr()->literal($dt->getUtcTimestamp()))
                )
            );
        }

        $qb
            ->select('ip.date_added, ip.ip_address, ip.lead_id, ip.id')
            ->from(sprintf('(%s)', $sqb->getSQL()), 'ip');

        return $this->getTimelineResults($qb, $options, 'ip.ip_address', 'ip.date_added', [], ['date_added']);
    }
}
