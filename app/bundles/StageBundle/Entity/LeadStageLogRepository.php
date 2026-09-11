<?php

namespace Mautic\StageBundle\Entity;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\Persistence\ManagerRegistry;
use Mautic\CoreBundle\Entity\CommonRepository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * @extends CommonRepository<LeadStageLog>
 */
final class LeadStageLogRepository extends CommonRepository
{
    private const UPDATE_STAGE_BATCH_SIZE = 500;

    public function __construct(
        ManagerRegistry $registry,
        #[Autowire(service: 'doctrine.dbal.unbuffered_connection')]
        private readonly Connection $unbufferedConnection,
    ) {
        parent::__construct($registry);
    }

    /**
     * Updates lead ID (e.g. after a lead merge).
     */
    public function updateLead(string $fromLeadId, string $toLeadId): void
    {
        $connection = $this->_em->getConnection();
        $table      = MAUTIC_TABLE_PREFIX.LeadStageLog::TABLE_NAME;

        // First check to ensure the $toLead doesn't already exist
        $stageIds = $connection->createQueryBuilder()
            ->select('pl.stage_id')
            ->from($table, 'pl')
            ->where('pl.lead_id = :toLeadId')
            ->setParameter('toLeadId', $toLeadId, ParameterType::STRING)
            ->executeQuery()
            ->fetchFirstColumn();

        $q = $connection->createQueryBuilder();
        $q->update($table)
            ->set('lead_id', ':toLeadId')
            ->where('lead_id = :fromLeadId')
            ->setParameter('fromLeadId', $fromLeadId, ParameterType::STRING)
            ->setParameter('toLeadId', $toLeadId, ParameterType::STRING);

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
                ->setParameter('fromLeadId', $fromLeadId, ParameterType::STRING)
                ->executeStatement();
        } else {
            $q->executeStatement();
        }
    }

    public function updateStage(int $fromStageId, int $toStageId): void
    {
        $connection = $this->_em->getConnection();
        $table      = MAUTIC_TABLE_PREFIX.LeadStageLog::TABLE_NAME;

        foreach ($this->getLeadIdBatchesForStage($fromStageId, $table) as $leadIds) {
            $this->deleteDuplicateStageLogs($connection, $table, $fromStageId, $toStageId, $leadIds);
            $this->updateStageLogs($connection, $table, $fromStageId, $toStageId, $leadIds);
        }
    }

    /**
     * @return iterable<array<string>>
     */
    private function getLeadIdBatchesForStage(int $stageId, string $table): iterable
    {
        $hasRows = false;
        foreach ($this->getLeadIdBatchesFromConnection($this->unbufferedConnection, $stageId, $table) as $leadIds) {
            $hasRows = true;

            yield $leadIds;
        }

        $connection = $this->_em->getConnection();
        if ($hasRows) {
            return;
        }

        // The separate unbuffered connection can be isolated from the active connection in tests.
        foreach ($this->getLeadIdBatchesFromConnection($connection, $stageId, $table) as $leadIds) {
            yield $leadIds;
        }
    }

    /**
     * @return iterable<array<string>>
     */
    private function getLeadIdBatchesFromConnection(Connection $connection, int $stageId, string $table): iterable
    {
        $result = $connection->executeQuery(
            sprintf('SELECT lead_id FROM %s WHERE stage_id = :stageId ORDER BY lead_id', $table),
            ['stageId' => $stageId],
            ['stageId' => ParameterType::INTEGER]
        );

        $leadIds = [];
        while (false !== $row = $result->fetchAssociative()) {
            $leadIds[] = (string) $row['lead_id'];

            if (self::UPDATE_STAGE_BATCH_SIZE === count($leadIds)) {
                yield $leadIds;

                $leadIds = [];
            }
        }

        if ([] !== $leadIds) {
            yield $leadIds;
        }
    }

    /**
     * @param array<string> $leadIds
     */
    private function deleteDuplicateStageLogs(Connection $connection, string $table, int $fromStageId, int $toStageId, array $leadIds): void
    {
        if ([] === $leadIds) {
            return;
        }

        // Step 1: Find leads that already have the target stage
        $conflictingLeads = $connection->fetchFirstColumn(
            sprintf('SELECT lead_id FROM %s WHERE stage_id = :toStageId AND lead_id IN (:leadIds)', $table),
            [
                'toStageId' => $toStageId,
                'leadIds'   => $leadIds,
            ],
            [
                'toStageId' => ParameterType::INTEGER,
                'leadIds'   => ArrayParameterType::STRING,
            ]
        );

        if ([] === $conflictingLeads) {
            return;
        }

        // Step 2: Delete only the conflicting source rows
        $connection->executeStatement(
            sprintf(
                'DELETE FROM %s
             WHERE stage_id = :fromStageId
               AND lead_id IN (:conflictingLeads)',
                $table
            ),
            [
                'fromStageId'      => $fromStageId,
                'conflictingLeads' => $conflictingLeads,
            ],
            [
                'fromStageId'      => ParameterType::INTEGER,
                'conflictingLeads' => ArrayParameterType::STRING,
            ]
        );
    }

    /**
     * @param array<string> $leadIds
     */
    private function updateStageLogs(Connection $connection, string $table, int $fromStageId, int $toStageId, array $leadIds): void
    {
        $connection->createQueryBuilder()
            ->update($table)
            ->set('stage_id', ':toStageId')
            ->where('stage_id = :fromStageId')
            ->andWhere('lead_id IN (:leadIds)')
            ->setParameter('fromStageId', $fromStageId, ParameterType::INTEGER)
            ->setParameter('leadIds', $leadIds, ArrayParameterType::STRING)
            ->setParameter('toStageId', $toStageId, ParameterType::INTEGER)
            ->executeStatement();
    }
}
