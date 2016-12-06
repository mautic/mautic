<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PointBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * Class LeadPointLogRepository.
 */
class LeadPointLogRepository extends CommonRepository
{
    /**
     * Updates lead ID (e.g. after a lead merge).
     *
     * @param $fromLeadId
     * @param $toLeadId
     */
    public function updateLead($fromLeadId, $toLeadId)
    {
        // First check to ensure the $toLead doesn't already exist
        $results = $this->_em->getConnection()->createQueryBuilder()
            ->select('pl.point_id')
            ->from(MAUTIC_TABLE_PREFIX.'point_lead_action_log', 'pl')
            ->where('pl.lead_id = '.$toLeadId)
            ->execute()
            ->fetchAll();
        $actions = [];
        foreach ($results as $r) {
            $actions[] = $r['point_id'];
        }

        $q = $this->_em->getConnection()->createQueryBuilder();
        $q->update(MAUTIC_TABLE_PREFIX.'point_lead_action_log')
            ->set('lead_id', (int) $toLeadId)
            ->where('lead_id = '.(int) $fromLeadId);

        if (!empty($actions)) {
            $q->andWhere(
                $q->expr()->notIn('point_id', $actions)
            )->execute();

            // Delete remaining leads as the new lead already belongs
            $this->_em->getConnection()->createQueryBuilder()
                ->delete(MAUTIC_TABLE_PREFIX.'point_lead_action_log')
                ->where('lead_id = '.(int) $fromLeadId)
                ->execute();
        } else {
            $q->execute();
        }
    }
}
