<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Entity;

use Doctrine\DBAL\Connection;
use Mautic\CoreBundle\Entity\CommonRepository;

class FieldChangeRepository extends CommonRepository
{
    /**
     * Takes an object id & type and deletes all entities
     * that match the given column names.
     *
     * @param int    $objectId
     * @param string $objectType
     * @param array  $columnNames
     *
     */
    public function deleteEntitiesForObjectByColumnName(int $objectId, string $objectType, array $columnNames)
    {
        $qb = $this->getEntityManager()->getConnection()->createQueryBuilder();

        $qb
            ->delete(MAUTIC_TABLE_PREFIX.'sync_object_field_change_report')
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->eq('object_id', ":objectId"),
                    $qb->expr()->eq('object_type', ":objectType"),
                    $qb->expr()->in('column_name', ":columnNames")
                )
            )
            ->setParameter('objectId', $objectId)
            ->setParameter('objectType', $objectType)
            ->setParameter('columnNames', $columnNames, Connection::PARAM_STR_ARRAY)
            ->execute();
    }

    /**
     * Takes an object id & type and deletes all entities that match.
     *
     * @param int    $objectId
     * @param string $objectType
     *
     */
    public function deleteEntitiesForObject(int $objectId, string $objectType)
    {
        $qb = $this->getEntityManager()->getConnection()->createQueryBuilder();

        $qb
            ->delete(MAUTIC_TABLE_PREFIX.'sync_object_field_change_report')
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->eq('object_id', ":objectId"),
                    $qb->expr()->eq('object_type', ":objectType")
                )
            )
            ->setParameter('objectId', $objectId)
            ->setParameter('objectType', $objectType)
            ->execute();
    }

    /**
     * @param     $objectType
     * @param     $fromTimestamp
     * @param int $objectCount
     *
     * @return array
     */
    public function findChangesAfter($objectType, $fromTimestamp, $objectCount = 100)
    {
        // Get a list of object IDs so that we can get complete snapshots of the objects
        $qb = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $qb
            ->select('f.object_id')
            ->from(MAUTIC_TABLE_PREFIX.'sync_object_field_change_report', 'f')
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->eq('f.object_type', ':objectType'),
                    $qb->expr()->gte('f.modified_at', ':startDateTime')
                )
            )
            ->setParameter('objectType', $objectType)
            ->setParameter('startDateTime', (new \DateTime($fromTimestamp, new \DateTimeZone('UTC')))->format('Y-m-d H:i:s'))
            ->groupBy('f.object_id')
            ->setMaxResults($objectCount);

        $results   = $qb->execute()->fetchAll();
        $objectIds = [];
        foreach ($results as $result) {
            $objectIds[] = (int) $result['object_id'];
        }

        if (!$objectIds) {
            return [];
        }

        // Get all the field changes for the requested objects objects
        $qb
            ->resetQueryParts()
            ->select('*')
            ->from(MAUTIC_TABLE_PREFIX.'sync_object_field_change_report', 'f')
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->eq('f.object_type', ':objectType'),
                    $qb->expr()->in('f.object_id', $objectIds)
                )
            )
            ->setParameter('objectType', $objectType);

        return $qb->execute()->fetchAll();
    }

    /**
     * @param $objectType
     * @param $objectId
     *
     * @return array
     */
    public function findChangesForObject($objectType, $objectId)
    {
        // Get a list of object IDs so that we can get complete snapshots of the objects
        $qb = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $qb
            ->select('*')
            ->from(MAUTIC_TABLE_PREFIX.'sync_object_field_change_report', 'f')
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->eq('f.object_type', ':objectType'),
                    $qb->expr()->gte('f.object_id', ':objectId')
                )
            )
            ->setParameter('objectType', $objectType)
            ->setParameter('objectId', $objectId);

        return $qb->execute()->fetchAll();
    }


//    /**
//     * @param $objectType
//     * @param $fromTimestamp
//     * @param $toTimestamp
//     */
//    public function deleteChangesBetween($objectType, $fromTimestamp, $toTimestamp)
//    {
//        $qb = $this->getEntityManager()->getConnection()->createQueryBuilder();
//
//        $qb
//            ->delete(MAUTIC_TABLE_PREFIX.'sync_object_field_change_report')
//            ->where(
//                $qb->expr()->andX(
//                    $qb->expr()->eq('object_type', ':objectType'),
//                    sprintf('modified_at BETWEEN :startDateTime and :endDateTime')
//                )
//            )
//            ->setParameter('objectType', $objectType)
//            ->setParameter('startDateTime', (new \DateTime($fromTimestamp, new \DateTimeZone('UTC')))->format('Y-m-d H:i:s'))
//            ->setParameter('endDateTime', (new \DateTime($toTimestamp, new \DateTimeZone('UTC')))->format('Y-m-d H:i:s'))
//            ->execute();
//    }
}