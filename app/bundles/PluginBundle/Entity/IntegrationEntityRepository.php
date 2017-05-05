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
    public function getIntegrationsEntityId($integration, $integrationEntity, $internalEntity, $internalEntityId = null, $startDate = null, $endDate = null, $push = false, $start = 0, $limit = 0, $integrationEntityIds = null)
    {
        $q = $this->_em->getConnection()->createQueryBuilder()
            ->select('DISTINCT(i.integration_entity_id), i.id, i.internal_entity_id, i.integration_entity')
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
        if ($integrationEntityIds) {
            $q->andWhere('i.integration_entity_id in ('.$integrationEntityIds.')');
        }

        $results = $q->execute()->fetchAll();

        return $results;
    }

    /**
     * @param $integration
     * @param $internalEntity
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
            ->andWhere('i.integration_entity = "Lead" or i.integration_entity = "Contact"')
            ->andWhere('(i.last_sync_date is not null and l.date_modified > i.last_sync_date) or (i.last_sync_date is null and l.date_modified > i.date_added) and l.email is not null')
            ->setParameter('integration', $integration)
            ->setParameter('internalEntity', $internalEntity);

        $q->join('i', MAUTIC_TABLE_PREFIX.'leads', 'l', 'l.id = i.internal_entity_id');

        $q->setMaxResults($limit);
        echo $q->getSQL();
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
            ->select('l.id as internal_entity_id,'.$leadFields)
            ->from(MAUTIC_TABLE_PREFIX.'leads', 'l');

        $q->where('l.date_identified is not null')
            ->andWhere('l.email is not null')
            ->andWhere('not exists (select null from '.MAUTIC_TABLE_PREFIX.'integration_entity i where i.integration = :integration and (i.internal_entity = "lead" or i.internal_entity = "lead-deleted" or i.internal_entity = "lead-error") and i.internal_entity_id = l.id )')
            ->setParameter('integration', $integration);

        $q->setMaxResults($limit);

        $results = $q->execute()->fetchAll();

        $leads = [];
        foreach ($results as $result) {
            $leads[$result['internal_entity_id']] = $result;
        }

        return $leads;
    }

    /**
     * @param array $integrationIds
     * @param       $integration
     * @param       $internalEntityType
     */
    public function markAsDeleted(array $integrationIds, $integration, $internalEntityType)
    {
        $q = $this->_em->getConnection()->createQueryBuilder();
        $q->update(MAUTIC_TABLE_PREFIX.'integration_entity')
            ->set('internal_entity', ':entity')
            ->where(
                $q->expr()->andX(
                    $q->expr()->eq('integration', ':integration'),
                    $q->expr()->in('integration_entity_id', array_map([$q->expr(), 'literal'], $integrationIds))
                )
            )
            ->setParameter('integration', $integration)
            ->setParameter('entity', $internalEntityType.'-deleted')
            ->execute();
    }

    /**
     * @param $integration
     * @param $internalEntity
     * @param $leadId
     *
     * @return array
     */
    public function findLeadsToDelete($internalEntity, $leadId)
    {
        $q = $this->_em->getConnection()->createQueryBuilder()
            ->delete(MAUTIC_TABLE_PREFIX.'integration_entity')
            ->from(MAUTIC_TABLE_PREFIX.'integration_entity');

        $q->where('internal_entity_id = :leadId')
            ->andWhere($q->expr()->like('internal_entity', ':internalEntity'))
            ->setParameter('leadId', $leadId)
            ->setParameter('internalEntity', $internalEntity)
        ->execute();
    }
}
