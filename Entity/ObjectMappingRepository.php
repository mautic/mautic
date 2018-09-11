<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Entity;


use Mautic\CoreBundle\Entity\CommonRepository;

class ObjectMappingRepository  extends CommonRepository
{
    /**
     * @param $integration
     * @param $integrationObjectName
     * @param $integrationObjectId
     * @param $internalObjectName
     *
     * @return array|null
     */
    public function getInternalObject($integration, $integrationObjectName, $integrationObjectId, $internalObjectName)
    {
        $qb = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $qb->select('*')
            ->from(MAUTIC_TABLE_PREFIX.'sync_object_mapping', 'i')
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->eq('i.integration', ':integration'),
                    $qb->expr()->eq('i.integration_object_name', ':integrationObjectName'),
                    $qb->expr()->eq('i.integration_object_id', ':integrationObjectId'),
                    $qb->expr()->eq('i.internal_object_name', ':internalObjectName')
                )
            )
            ->setParameter('integration', $integration)
            ->setParameter('integrationObjectName', $integrationObjectName)
            ->setParameter('integrationObjectId', $integrationObjectId)
            ->setParameter('internalObjectName', $internalObjectName);

        $result = $qb->execute()->fetch();

        return $result ? $result : null;
    }

    /**
     * @param $integration
     * @param $internalObjectName
     * @param $internalObjectId
     * @param $integrationObjectName
     *
     * @return array|null
     */
    public function getIntegrationObject($integration, $internalObjectName, $internalObjectId, $integrationObjectName)
    {
        $qb = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $qb->select('*')
            ->from(MAUTIC_TABLE_PREFIX.'sync_object_mapping', 'i')
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->eq('i.integration', ':integration'),
                    $qb->expr()->eq('i.internal_object_name', ':internalObjectName'),
                    $qb->expr()->eq('i.internal_object_id', ':internalObjectId'),
                    $qb->expr()->eq('i.integration_object_name', ':integrationObjectName')
                )
            )
            ->setParameter('integration', $integration)
            ->setParameter('internalObjectName', $internalObjectName)
            ->setParameter('internalObjectId', $internalObjectId)
            ->setParameter('integrationObjectName', $integrationObjectName);

        $result = $qb->execute()->fetch();

        return $result ? $result : null;
    }

    /**
     * @param string $integration
     * @param string $oldObjectName
     * @param mixed  $oldObjectId
     * @param string $newObjectName
     * @param mixed  $newObjectId
     *
     * @return int
     */
    public function updateIntegrationObject($integration, $oldObjectName, $oldObjectId, $newObjectName, $newObjectId)
    {
        $qb = $this->getEntityManager()->getConnection()->createQueryBuilder();

        $qb->update(MAUTIC_TABLE_PREFIX.'sync_object_mapping')
            ->set('integration_object_name', ':newObjectName')
            ->set('integration_object_id', ':newObjectId')
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->eq('i.integration', ':integration'),
                    $qb->expr()->eq('i.integration_object_name', ':oldObjectName'),
                    $qb->expr()->eq('i.integration_object_id', ':oldObjectId')
                )
            )
            ->setParameter('newObjectName', $newObjectName)
            ->setParameter('newObjectId', $newObjectId)
            ->setParameter('integration', $integration)
            ->setParameter('oldObjectName', $oldObjectName)
            ->setParameter('oldObjectId', $oldObjectId);

        return $qb->execute()->rowCount();
    }

    /**
     * @param string $integration
     * @param string $objectName
     * @param mixed  $objectId
     */
    public function markAsDeleted($integration, $objectName, $objectId)
    {
        $qb = $this->getEntityManager()->getConnection()->createQueryBuilder();

        $qb->update(MAUTIC_TABLE_PREFIX.'sync_object_mapping')
            ->set('is_deleted', 1)
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->eq('i.integration', ':integration'),
                    $qb->expr()->eq('i.integration_object_name', ':objectName'),
                    $qb->expr()->eq('i.integration_object_id', ':objectId')
                )
            )
            ->setParameter('integration', $integration)
            ->setParameter('objectName', $objectName)
            ->setParameter('objectId', $objectId);
    }
}