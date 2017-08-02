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
            ->select('MAX(l.date_added) as date_added, l.ip_address, l.object_id as lead_id')
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
            ->select('ip.date_added, ip.ip_address, ip.lead_id')
            ->from(sprintf('(%s)', $sqb->getSQL()), 'ip');

        return $this->getTimelineResults($qb, $options, 'ip.ip_address', 'ip.date_added', [], ['date_added']);
    }
}
