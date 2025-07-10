<?php

namespace Mautic\PluginBundle\Entity;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * @extends CommonRepository<IntegrationEntity>
 */
class IntegrationEntityRepository extends CommonRepository
{
    /**
     * @param array<int>|int|null               $internalEntityIds
     * @param mixed                             $startDate
     * @param mixed                             $endDate
     * @param bool                              $push
     * @param int                               $start
     * @param int                               $limit
     * @param int|string|array<int|string>|null $integrationEntityIds
     */
    public function getIntegrationsEntityId(
        $integration,
        $integrationEntity,
        $internalEntity,
        $internalEntityIds = null,
        $startDate = null,
        $endDate = null,
        $push = false,
        $start = 0,
        $limit = 0,
        $integrationEntityIds = null,
    ): array {
        $q = $this->_em->getConnection()->createQueryBuilder()
            ->select('DISTINCT(i.integration_entity_id), i.id, i.internal_entity_id, i.integration_entity, i.internal_entity')
            ->from(MAUTIC_TABLE_PREFIX.'integration_entity', 'i');

        $q->where('i.integration = :integration')
            ->andWhere('i.internal_entity = :internalEntity')
            ->setParameter('integration', $integration)
            ->setParameter('internalEntity', $internalEntity);

        if ($integrationEntity) {
            $q->andWhere('i.integration_entity = :integrationEntity')
                ->setParameter('integrationEntity', $integrationEntity);
        }

        if ('lead' === $internalEntity) {
            $joinCondition = $q->expr()->and(
                $q->expr()->eq('l.id', 'i.internal_entity_id')
            );

            if ($push) {
                $joinCondition->with(
                    $q->expr()->gte('l.last_active', ':startDate')
                );
                $q->setParameter('startDate', $startDate);
            }

            $q->join('i', MAUTIC_TABLE_PREFIX.'leads', 'l', $joinCondition);
        }

        if ($internalEntityIds) {
            if (is_array($internalEntityIds)) {
                $q->andWhere('i.internal_entity_id in (:internalEntityIds)')
                    ->setParameter('internalEntityIds', $internalEntityIds, ArrayParameterType::STRING);
            } else {
                $q->andWhere('i.internal_entity_id = :internalEntityId')
                    ->setParameter('internalEntityId', $internalEntityIds);
            }
        }

        if ($startDate and !$push) {
            $q->andWhere('i.last_sync_date >= :startDate')
                ->setParameter('startDate', $startDate);
        }

        if ($endDate and !$push) {
            $q->andWhere('i.last_sync_date <= :endDate')
                ->setParameter('endDate', $endDate);
        }

        if ($integrationEntityIds) {
            if (is_array($integrationEntityIds)) {
                $q->andWhere('i.integration_entity_id in (:integrationEntityIds)')
                    ->setParameter('integrationEntityIds', $integrationEntityIds, ArrayParameterType::STRING);
            } else {
                $q->andWhere('i.integration_entity_id = :integrationEntityId')
                    ->setParameter('integrationEntityId', $integrationEntityIds);
            }
        }

        if ($start) {
            $q->setFirstResult((int) $start);
        }

        if ($limit) {
            $q->setMaxResults((int) $limit);
        }

        return $q->executeQuery()->fetchAllAssociative();
    }

    /**
     * @return array
     */
    public function getIntegrationEntity($integration, $integrationEntity, $internalEntity, $internalEntityId, $leadFields = null)
    {
        $q = $this->_em->getConnection()->createQueryBuilder()
            ->from(MAUTIC_TABLE_PREFIX.'integration_entity', 'i')
            ->join('i', MAUTIC_TABLE_PREFIX.'leads', 'l', 'l.id = i.internal_entity_id');
        $q->select('i.integration_entity_id, i.integration_entity, i.id, i.internal_entity_id');
        if ($leadFields) {
            $q->addSelect($leadFields);
        }

        $q->where(
            $q->expr()->and(
                $q->expr()->eq('i.integration', ':integration'),
                $q->expr()->eq('i.internal_entity', ':internalEntity'),
                $q->expr()->eq('i.integration_entity', ':integrationEntity'),
                $q->expr()->eq('i.internal_entity_id', (int) $internalEntityId)
            )
        )
            ->setParameter('integration', $integration)
            ->setParameter('internalEntity', $internalEntity)
            ->setParameter('integrationEntity', $integrationEntity)
            ->setMaxResults(1);

        $results = $q->executeQuery()->fetchAllAssociative();

        return ($results) ? $results[0] : null;
    }

    /**
     * @return IntegrationEntity[]
     */
    public function getIntegrationEntities($integration, $integrationEntity, $internalEntity, $internalEntityIds)
    {
        $q = $this->createQueryBuilder('i', 'i.internalEntityId');

        $q->where(
            $q->expr()->andX(
                $q->expr()->eq('i.integration', ':integration'),
                $q->expr()->eq('i.internalEntity', ':internalEntity'),
                $q->expr()->eq('i.integrationEntity', ':integrationEntity'),
                $q->expr()->in('i.internalEntityId', ':internalEntityIds')
            )
        )
            ->setParameter('integration', $integration)
            ->setParameter('internalEntity', $internalEntity)
            ->setParameter('integrationEntity', $integrationEntity)
            ->setParameter('internalEntityIds', $internalEntityIds);

        return $q->getQuery()->getResult();
    }

    /**
     * @param int          $limit
     * @param array|string $integrationEntity
     * @param array        $excludeIntegrationIds
     *
     * @return mixed[]
     */
    public function findLeadsToUpdate(
        $integration,
        $internalEntity,
        $leadFields,
        $limit = 25,
        $fromDate = null,
        $toDate = null,
        $integrationEntity = ['Contact', 'Lead'],
        $excludeIntegrationIds = [],
    ): array {
        if ('company' == $internalEntity) {
            $joinTable = 'companies';
        } else {
            $joinTable = 'leads';
        }
        $q = $this->_em->getConnection()->createQueryBuilder()
            ->from(MAUTIC_TABLE_PREFIX.'integration_entity', 'i')
            ->join('i', MAUTIC_TABLE_PREFIX.$joinTable, 'l', 'l.id = i.internal_entity_id');

        if (false === $limit) {
            $q->select('count(i.integration_entity_id) as total');

            if ($integrationEntity) {
                $q->addSelect('i.integration_entity');
            }
        } else {
            $q->select('i.integration_entity_id, i.integration_entity, i.id, i.internal_entity_id,'.$leadFields);
        }

        $q->where('i.integration = :integration');

        if ($integrationEntity) {
            if (!is_array($integrationEntity)) {
                $integrationEntity = [$integrationEntity];
            }
            $sub = null;
            foreach ($integrationEntity as $key => $entity) {
                if (null === $sub) {
                    $sub = CompositeExpression::or($q->expr()->eq('i.integration_entity', ':entity'.$key));
                    $q->setParameter('entity'.$key, $entity);
                    continue;
                }

                $sub->with($q->expr()->eq('i.integration_entity', ':entity'.$key));
                $q->setParameter('entity'.$key, $entity);
            }
            $q->andWhere($sub);
        }

        $q->andWhere('i.internal_entity = :internalEntity')
            ->setParameter('integration', $integration)
            ->setParameter('internalEntity', $internalEntity);

        if (!empty($excludeIntegrationIds)) {
            $q->andWhere(
                $q->expr()->notIn(
                    'i.integration_entity_id',
                    array_map(
                        fn ($x): string => "'".$x."'",
                        $excludeIntegrationIds
                    )
                )
            );
        }

        $q->andWhere(
            $q->expr()->and(
                $q->expr()->isNotNull('i.integration_entity_id'),
                $q->expr()->or(
                    $q->expr()->and(
                        $q->expr()->isNotNull('i.last_sync_date'),
                        $q->expr()->gt('l.date_modified', 'i.last_sync_date')
                    ),
                    $q->expr()->and(
                        $q->expr()->isNull('i.last_sync_date'),
                        $q->expr()->isNotNull('l.date_modified'),
                        $q->expr()->gt('l.date_modified', 'l.date_added')
                    )
                )
            )
        );

        if ('lead' == $internalEntity) {
            $q->andWhere(
                $q->expr()->and($q->expr()->isNotNull('l.email')));
        } else {
            $q->andWhere(
                $q->expr()->and($q->expr()->isNotNull('l.companyname')));
        }

        if ($fromDate) {
            if ($toDate) {
                $q->andWhere(
                    $q->expr()->comparison('l.date_modified', 'BETWEEN', ':dateFrom and :dateTo')
                )
                    ->setParameter('dateFrom', $fromDate)
                    ->setParameter('dateTo', $toDate);
            } else {
                $q->andWhere(
                    $q->expr()->gte('l.date_modified', ':dateFrom')
                )
                    ->setParameter('dateFrom', $fromDate);
            }
        } elseif ($toDate) {
            $q->andWhere(
                $q->expr()->lte('l.date_modified', ':dateTo')
            )
                ->setParameter('dateTo', $toDate);
        }

        // Group by email to prevent duplicates from affecting this

        if (false === $limit and $integrationEntity) {
            $q->groupBy('i.integration_entity')->having('total');
        }
        if ($limit) {
            $q->setMaxResults($limit);
        }

        $results = $q->executeQuery()->fetchAllAssociative();

        $leads   = [];

        if ($integrationEntity) {
            foreach ($integrationEntity as $entity) {
                $leads[$entity] = (false === $limit) ? 0 : [];
            }
        }

        foreach ($results as $result) {
            if ($integrationEntity) {
                if (false === $limit) {
                    $leads[$result['integration_entity']] = (int) $result['total'];
                } else {
                    $leads[$result['integration_entity']][$result['internal_entity_id']] = $result;
                }
            } else {
                $leads[$result['internal_entity_id']] = $result['internal_entity_id'];
            }
        }

        return $leads;
    }

    /**
     * @param int $limit
     *
     * @return array|int
     */
    public function findLeadsToCreate($integration, $leadFields, $limit = 25, $fromDate = null, $toDate = null, $internalEntity = 'lead')
    {
        if ('company' == $internalEntity) {
            $joinTable = 'companies';
        } else {
            $joinTable = 'leads';
        }
        $q = $this->_em->getConnection()->createQueryBuilder()
            ->from(MAUTIC_TABLE_PREFIX.$joinTable, 'l');

        if (false === $limit) {
            $q->select('count(*) as total');
        } else {
            $q->select('l.id as internal_entity_id,'.$leadFields);
        }
        if ('company' == $internalEntity) {
            $q->where('not exists (select null from '.MAUTIC_TABLE_PREFIX
                .'integration_entity i where i.integration = :integration and i.internal_entity LIKE "'.$internalEntity.'%" and i.internal_entity_id = l.id)')
                ->setParameter('integration', $integration);
        } else {
            $q->where('l.date_identified is not null')
                ->andWhere(
                    'not exists (select null from '.MAUTIC_TABLE_PREFIX
                    .'integration_entity i where i.integration = :integration and i.internal_entity LIKE "'.$internalEntity.'%" and i.internal_entity_id = l.id)'
                )
                ->setParameter('integration', $integration);
        }

        if ('company' == $internalEntity) {
            $q->andWhere('l.companyname is not null');
        } else {
            $q->andWhere('l.email is not null');
        }
        if ($limit) {
            $q->setMaxResults($limit);
        }

        if ($fromDate) {
            if ($toDate) {
                $q->andWhere(
                    $q->expr()->or(
                        $q->expr()->and(
                            $q->expr()->isNotNull('l.date_modified'),
                            $q->expr()->comparison('l.date_modified', 'BETWEEN', ':dateFrom and :dateTo')
                        ),
                        $q->expr()->and(
                            $q->expr()->isNull('l.date_modified'),
                            $q->expr()->comparison('l.date_added', 'BETWEEN', ':dateFrom and :dateTo')
                        )
                    )
                )
                    ->setParameter('dateFrom', $fromDate)
                    ->setParameter('dateTo', $toDate);
            } else {
                $q->andWhere(
                    $q->expr()->or(
                        $q->expr()->and(
                            $q->expr()->isNotNull('l.date_modified'),
                            $q->expr()->gte('l.date_modified', ':dateFrom')
                        ),
                        $q->expr()->and(
                            $q->expr()->isNull('l.date_modified'),
                            $q->expr()->gte('l.date_added', ':dateFrom')
                        )
                    )
                )
                    ->setParameter('dateFrom', $fromDate);
            }
        } elseif ($toDate) {
            $q->andWhere(
                $q->expr()->or(
                    $q->expr()->and(
                        $q->expr()->isNotNull('l.date_modified'),
                        $q->expr()->lte('l.date_modified', ':dateTo')
                    ),
                    $q->expr()->and(
                        $q->expr()->isNull('l.date_modified'),
                        $q->expr()->lte('l.date_added', ':dateTo')
                    )
                )
            )
                ->setParameter('dateTo', $toDate);
        }

        $results = $q->executeQuery()->fetchAllAssociative();

        if (false === $limit) {
            return (int) $results[0]['total'];
        }

        $leads = [];
        foreach ($results as $result) {
            $leads[$result['internal_entity_id']] = $result;
        }

        return $leads;
    }

    /**
     * @return int
     */
    public function getIntegrationEntityCount($leadId, $integration = null, $integrationEntity = null, $internalEntity = null)
    {
        return $this->getIntegrationEntityByLead($leadId, $integration, $integrationEntity, $internalEntity, false);
    }

    /**
     * @param int|bool $limit
     *
     * @return array|int
     */
    public function getIntegrationEntityByLead($leadId, $integration = null, $integrationEntity = null, $internalEntity = null, $limit = 100)
    {
        $q = $this->_em->getConnection()->createQueryBuilder()
            ->from(MAUTIC_TABLE_PREFIX.'integration_entity', 'i');

        if (false === $limit) {
            $q->select('count(*) as total');
        } else {
            $q->select('i.integration, i.integration_entity, i.integration_entity_id, i.date_added, i.last_sync_date, i.internal');
        }

        $q->where('i.internal not like \'%error%\' and i.integration_entity_id is not null');
        $q->orderBy('i.last_sync_date', 'DESC');

        if (empty($integration)) {
            // get list of published integrations
            $pq = $this->_em->getConnection()->createQueryBuilder()
                ->select('p.name')
                ->from(MAUTIC_TABLE_PREFIX.'plugin_integration_settings', 'p')
                ->where('p.is_published = 1');
            $rows    = $pq->executeQuery()->fetchAllAssociative();
            $plugins = array_map(static fn ($i): string => "'{$i['name']}'", $rows);
            if (count($plugins) > 0) {
                $q->andWhere($q->expr()->in('i.integration', $plugins));
            } else {
                return [];
            }
        } else {
            $q->andWhere($q->expr()->eq('i.integration', ':integration'));
            $q->setParameter('integration', $integration);
        }

        $q->andWhere(
            $q->expr()->and(
                "i.internal_entity='lead'",
                $q->expr()->eq('i.internal_entity_id', ':internalEntityId')
            )
        );

        $q->setParameter('internalEntityId', $leadId);

        if (!empty($internalEntity)) {
            $q->andWhere($q->expr()->eq('i.internalEntity', ':internalEntity'));
            $q->setParameter('internalEntity', $internalEntity);
        }

        if (!empty($integrationEntity)) {
            $q->andWhere($q->expr()->eq('i.integrationEntity', ':integrationEntity'));
            $q->setParameter('integrationEntity', $integrationEntity);
        }

        $results = $q->executeQuery()->fetchAllAssociative();

        if (false === $limit && count($results) > 0) {
            return (int) $results[0]['total'];
        }

        return $results;
    }

    public function markAsDeleted(array $integrationIds, $integration, $internalEntityType): void
    {
        $q = $this->_em->getConnection()->createQueryBuilder();
        $q->update(MAUTIC_TABLE_PREFIX.'integration_entity')
            ->set('internal_entity', ':entity')
            ->where(
                $q->expr()->and(
                    $q->expr()->eq('integration', ':integration'),
                    $q->expr()->in('integration_entity_id', array_map([$q->expr(), 'literal'], $integrationIds))
                )
            )
            ->setParameter('integration', $integration)
            ->setParameter('entity', $internalEntityType.'-deleted')
            ->executeStatement();
    }

    public function findLeadsToDelete($internalEntity, $leadId): void
    {
        $q = $this->_em->getConnection()->createQueryBuilder()
            ->delete(MAUTIC_TABLE_PREFIX.'integration_entity')
            ->from(MAUTIC_TABLE_PREFIX.'integration_entity');

        $q->where('internal_entity_id = :leadId')
            ->andWhere($q->expr()->like('internal_entity', ':internalEntity'))
            ->setParameter('leadId', $leadId)
            ->setParameter('internalEntity', $internalEntity)
            ->executeStatement();
    }

    public function updateErrorLeads($internalEntity, $leadId): void
    {
        $q = $this->_em->getConnection()->createQueryBuilder()
            ->update(MAUTIC_TABLE_PREFIX.'integration_entity')
            ->set('internal_entity', ':lead')->setParameter('lead', 'lead');

        $q->where('internal_entity_id = :leadId')
            ->andWhere($q->expr()->isNotNull('integration_entity_id'))
            ->andWhere($q->expr()->eq('internal_entity', ':internalEntity'))
            ->setParameter('leadId', $leadId)
            ->setParameter('internalEntity', $internalEntity)
            ->executeStatement();

        $z = $this->_em->getConnection()->createQueryBuilder()
            ->delete(MAUTIC_TABLE_PREFIX.'integration_entity')
            ->from(MAUTIC_TABLE_PREFIX.'integration_entity');

        $z->where('internal_entity_id = :leadId')
            ->andWhere($q->expr()->isNull('integration_entity_id'))
            ->andWhere($q->expr()->like('internal_entity', ':internalEntity'))
            ->setParameter('leadId', $leadId)
            ->setParameter('internalEntity', $internalEntity)
            ->executeStatement();
    }
}
