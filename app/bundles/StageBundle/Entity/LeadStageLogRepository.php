<?php

namespace Mautic\StageBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * @extends CommonRepository<LeadStageLog>
 */
class LeadStageLogRepository extends CommonRepository
{
    /**
     * Updates lead ID (e.g. after a lead merge).
     */
    public function updateLead($fromLeadId, $toLeadId): void
    {
        // First check to ensure the $toLead doesn't already exist
        $results = $this->_em->getConnection()->createQueryBuilder()
            ->select('pl.stage_id')
            ->from(MAUTIC_TABLE_PREFIX.'stage_lead_action_log', 'pl')
            ->where('pl.lead_id = '.$toLeadId)
            ->executeQuery()
            ->fetchAllAssociative();

        $actions = [];
        foreach ($results as $r) {
            $actions[] = $r['stage_id'];
        }

        $q = $this->_em->getConnection()->createQueryBuilder();
        $q->update(MAUTIC_TABLE_PREFIX.'stage_lead_action_log')
            ->set('lead_id', (int) $toLeadId)
            ->where('lead_id = '.(int) $fromLeadId);

        if (!empty($actions)) {
            $q->andWhere(
                $q->expr()->notIn('stage_id', $actions)
            )->executeStatement();

            // Delete remaining leads as the new lead already belongs
            $this->_em->getConnection()->createQueryBuilder()
                ->delete(MAUTIC_TABLE_PREFIX.'stage_lead_action_log')
                ->where('lead_id = '.(int) $fromLeadId)
                ->executeStatement();
        } else {
            $q->executeStatement();
        }
    }

    public function updateStage(int $fromStageId, int $toStageId): void
    {
        $records = $this->_em->getConnection()->createQueryBuilder()
            ->select('pl.lead_id, pl.ip_id, pl.date_fired')
            ->from(MAUTIC_TABLE_PREFIX.'stage_lead_action_log', 'pl')
            ->where('pl.stage_id = '.(int) $fromStageId)
            ->executeQuery()
            ->fetchAllAssociative();

        if (!empty($records)) {
            foreach ($records as $record) {
                $existingRecord = $this->_em->getConnection()->createQueryBuilder()
                    ->select('pl.stage_id')
                    ->from(MAUTIC_TABLE_PREFIX.'stage_lead_action_log', 'pl')
                    ->where('pl.stage_id = '.(int) $toStageId)
                    ->andWhere('pl.lead_id = '.(int) $record['lead_id'])
                    ->executeQuery()
                    ->fetchOne();

                if ($existingRecord) {
                    $this->_em->getConnection()->createQueryBuilder()
                        ->delete(MAUTIC_TABLE_PREFIX.'stage_lead_action_log')
                        ->where('stage_id = '.(int) $fromStageId)
                        ->andWhere('lead_id = '.(int) $record['lead_id'])
                        ->executeStatement();
                } else {
                    $this->_em->getConnection()->createQueryBuilder()
                        ->update(MAUTIC_TABLE_PREFIX.'stage_lead_action_log')
                        ->set('stage_id', (int) $toStageId)
                        ->where('stage_id = '.(int) $fromStageId)
                        ->andWhere('lead_id = '.(int) $record['lead_id'])
                        ->executeStatement();
                }
            }
        }
    }
}
