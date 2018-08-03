<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticIntegrationsBundle\Entity;

use Doctrine\DBAL\Connection;
use Mautic\CoreBundle\Entity\CommonRepository;

class FieldChangeRepository extends CommonRepository
{
    /**
     * Will return array of data based on passed mapping parameters.
     * Does _not_ return array of FieldChange entities.
     *
     * @TODO
     *
     * @param $mapping
     *
     * @return array
     */
    public function basedOnMapping($mapping)
    {

    }

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
            ->delete(MAUTIC_TABLE_PREFIX.'object_field_change_report')
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
            ->delete(MAUTIC_TABLE_PREFIX.'object_field_change_report')
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
}