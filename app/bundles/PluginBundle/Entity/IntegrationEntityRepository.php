<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PluginBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * IntegrationRepository
 */
class IntegrationEntityRepository extends CommonRepository
{

    public function getIntegrationsEntityId($integration, $integrationEntity, $internalEntity, $internalEntityId = null, $startDate =  null, $endDate =  null, $push = false)
    {
        $q = $this->_em->getConnection()->createQueryBuilder()
            ->select('i.integration_entity_id, i.id, i.internal_entity_id')
            ->from(MAUTIC_TABLE_PREFIX . 'integration_entity', 'i');

        $q->where('i.integration = :integration')
            ->andWhere('i.integration_entity = :integrationEntity')
            ->andWhere('i.internal_entity = :internalEntity')

            ->setParameter('integration', $integration)
            ->setParameter('integrationEntity', $integrationEntity)
            ->setParameter('internalEntity', $internalEntity);
        if ($push) {
            $q->join('i', MAUTIC_TABLE_PREFIX . 'leads', 'l', 'l.last_active >= :startDate')
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
        $results = $q->execute()->fetchAll();

        return $results;
    }
}