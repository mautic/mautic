<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PluginBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * IntegrationRepository.
 */
class IntegrationEntityRepository extends CommonRepository
{
    /**
     * @param      $integration
     * @param      $integrationEntity
     * @param      $internalEntity
     * @param null $internalEntityId
     * @param null $startDate
     * @param null $endDate
     * @param bool $push
     * @param int  $start
     * @param int  $limit
     *
     * @return array
     */
    public function getIntegrationsEntityId($integration, $integrationEntity, $internalEntity, $internalEntityId = null, $startDate = null, $endDate = null, $push = false, $start = 0, $limit = 0)
    {
        $q = $this->_em->getConnection()->createQueryBuilder()
            ->select('i.integration_entity_id, i.id, i.internal_entity_id, i.integration_entity')
            ->from(MAUTIC_TABLE_PREFIX.'integration_entity', 'i');

        $q->where('i.integration = :integration')
            ->andWhere('i.internal_entity = :internalEntity')
            ->setParameter('integration', $integration)
            ->setParameter('internalEntity', $internalEntity);

        if ($integrationEntity) {
            $q->andWhere('i.integration_entity = :integrationEntity')
                ->setParameter('integrationEntity', $integrationEntity);
        }

        if ($push) {
            $q->join('i', MAUTIC_TABLE_PREFIX.'leads', 'l', 'l.id = i.internal_entity_id and l.last_active >= :startDate')
                ->setParameter('startDate', $startDate);
        }

        if ($internalEntityId) {
            $q->andWhere('i.internal_entity_id = :internalEntityId')
                ->setParameter('internalEntityId', $internalEntityId);
        }

        if ($startDate and !$push) {
            $q->andWhere('i.last_sync_date >= :startDate')
                ->setParameter('startDate', $startDate);
        }
        if ($endDate and !$push) {
            $q->andWhere('i.last_sync_date <= :endDate')
                ->setParameter('endDate', $endDate);
        }

        if ($start) {
            $q->setFirstResult((int) $start);
        }
        if ($limit) {
            $q->setMaxResults((int) $limit);
        }

        $results = $q->execute()->fetchAll();

        return $results;
    }

    /**
     * @param $integration
     * @param $internalEntity
     * @param null $startDate
     * @param null $endDate
     * @param $leadFields
     *
     * @return array
     */
    public function findLeadsToUpdate($integration, $internalEntity, $leadFields, $limit = 25)
    {
        $q = $this->_em->getConnection()->createQueryBuilder()
            ->select('i.integration_entity_id, i.integration_entity, i.id, i.internal_entity_id,'.$leadFields)
            ->from(MAUTIC_TABLE_PREFIX.'integration_entity', 'i');

        $q->where('i.integration = :integration')
            ->andWhere('i.internal_entity = :internalEntity')
            ->setParameter('integration', $integration)
            ->setParameter('internalEntity', $internalEntity);

        $q->join('i', MAUTIC_TABLE_PREFIX.'leads', 'l', 'l.id = i.internal_entity_id and l.date_modified > i.last_sync_date');

        $q->setMaxResults($limit);

        $results = $q->execute()->fetchAll();

        return $results;
    }

    /**
     * @param $integration
     * @param $leadFields
     *
     * @return array
     */
    public function findLeadsToCreate($integration, $leadFields, $limit = 25)
    {
        $q = $this->_em->getConnection()->createQueryBuilder()
            ->select('l.id,'.$leadFields)
            ->from(MAUTIC_TABLE_PREFIX.'leads', 'l');

        $q->where('l.id not in (select i.internal_entity_id from '.MAUTIC_TABLE_PREFIX.'integration_entity i where i.integration = :integration and i.internal_entity = "lead")')
            ->setParameter('integration', $integration);

        $q->setMaxResults($limit);

        $results = $q->execute()->fetchAll();

        return $results;
    }
}
