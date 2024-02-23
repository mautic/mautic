<?php

namespace Mautic\CampaignBundle\Entity;

use Doctrine\DBAL\ArrayParameterType;
use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * @extends CommonRepository<FailedLeadEventLog>
 */
class FailedLeadEventLogRepository extends CommonRepository
{
    /**
     * @param array<string|int> $ids
     */
    public function deleteByIds(array $ids): void
    {
        if (!$ids) {
            return;
        }

        $this->_em->getConnection()
            ->createQueryBuilder()
            ->delete(MAUTIC_TABLE_PREFIX.'campaign_lead_event_failed_log')
            ->where('log_id IN (:ids)')
            ->setParameter('ids', $ids, ArrayParameterType::STRING)
            ->executeStatement();
    }
}
