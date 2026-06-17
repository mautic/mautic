<?php

namespace Mautic\StageBundle\Entity;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * @extends CommonRepository<LeadStageLog>
 */
class LeadStageLogRepository extends CommonRepository
{
    /**
     * Updates lead ID (e.g. after a lead merge).
     */
    public function updateLead(int $fromLeadId, int $toLeadId): void
    {
        $connection = $this->_em->getConnection();
        $table      = MAUTIC_TABLE_PREFIX.LeadStageLog::TABLE_NAME;

        // First check to ensure the $toLead doesn't already exist
        $stageIds = $connection->createQueryBuilder()
            ->select('pl.stage_id')
            ->from($table, 'pl')
            ->where('pl.lead_id = :toLeadId')
            ->setParameter('toLeadId', $toLeadId)
            ->executeQuery()
            ->fetchFirstColumn();

        $q = $connection->createQueryBuilder();
        $q->update($table)
            ->set('lead_id', ':toLeadId')
            ->where('lead_id = :fromLeadId')
            ->setParameter('fromLeadId', $fromLeadId)
            ->setParameter('toLeadId', $toLeadId);

        if (!empty($stageIds)) {
            $q->andWhere(
                $q->expr()->notIn('stage_id', ':stageIds')
            )->setParameter(
                'stageIds',
                $stageIds,
                ArrayParameterType::INTEGER
            )->executeStatement();

            // Delete remaining leads as the new lead already belongs
            $connection->createQueryBuilder()
                ->delete($table)
                ->where('lead_id = :fromLeadId')
                ->setParameter('fromLeadId', $fromLeadId)
                ->executeStatement();
        } else {
            $q->executeStatement();
        }
    }

    public function updateStage(int $fromStageId, int $toStageId): void
    {
        $connection = $this->_em->getConnection();
        $table      = MAUTIC_TABLE_PREFIX.LeadStageLog::TABLE_NAME;

        // Lead and stage are a composite key, so delete source rows that would duplicate an existing target row.
        $connection->executeStatement(
            sprintf(
                'DELETE source_log FROM %s source_log INNER JOIN %s target_log ON target_log.lead_id = source_log.lead_id AND target_log.stage_id = :toStageId WHERE source_log.stage_id = :fromStageId',
                $table,
                $table
            ),
            [
                'fromStageId' => $fromStageId,
                'toStageId'   => $toStageId,
            ],
            [
                'fromStageId' => ParameterType::INTEGER,
                'toStageId'   => ParameterType::INTEGER,
            ]
        );

        $connection->createQueryBuilder()
            ->update($table)
            ->set('stage_id', ':toStageId')
            ->where('stage_id = :fromStageId')
            ->setParameter('fromStageId', $fromStageId, ParameterType::INTEGER)
            ->setParameter('toStageId', $toStageId, ParameterType::INTEGER)
            ->executeStatement();
    }
}
