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
}