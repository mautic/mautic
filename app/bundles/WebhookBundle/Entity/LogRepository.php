<?php

namespace Mautic\WebhookBundle\Entity;

use Doctrine\DBAL\ParameterType;
use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * @extends CommonRepository<Log>
 */
class LogRepository extends CommonRepository
{
    private const LOG_DELETE_BATCH_SIZE = 5000;

    /**
     * @return int[]
     */
    public function getWebhooksBasedOnLogLimit(int $logMaxLimit): array
    {
        $qb = $this->_em->getConnection()->createQueryBuilder();
        $qb->select('webhook_id')
            ->from(MAUTIC_TABLE_PREFIX.'webhook_logs', $this->getTableAlias())
            ->groupBy('webhook_id')
            ->having('count(id) > :logMaxLimit')
            ->setParameter('logMaxLimit', $logMaxLimit);

        return array_map(
            static fn ($row): int => (int) $row['webhook_id'],
            $qb->executeQuery()->fetchAllAssociative()
        );
    }

    /**
     * Retains a rolling number of log records for a webhook id.
     *
     * @depreacated use removeLimitExceedLogs() instead
     *
     * @param int $webhookId
     * @param int $logMax    how many recent logs should remain, the rest will be deleted
     *
     * @return int
     */
    public function removeOldLogs($webhookId, $logMax)
    {
        // if the hook was deleted then return a count of 0
        if (!$webhookId) {
            return false;
        }

        $qb = $this->_em->getConnection()->createQueryBuilder();

        $count = $qb->select('count(id) as log_count')
            ->from(MAUTIC_TABLE_PREFIX.'webhook_logs', $this->getTableAlias())
            ->where('webhook_id = '.$webhookId)
            ->executeQuery()
            ->fetchAssociative();

        if ((int) $count['log_count'] >= (int) $logMax) {
            $qb = $this->_em->getConnection()->createQueryBuilder();

            $id = $qb->select('id')
                ->from(MAUTIC_TABLE_PREFIX.'webhook_logs', $this->getTableAlias())
                ->where('webhook_id = '.$webhookId)
                ->orderBy('date_added', 'ASC')->setMaxResults(1)
                ->executeQuery()
                ->fetchAssociative();

            $qb = $this->_em->getConnection()->createQueryBuilder();

            $qb->delete(MAUTIC_TABLE_PREFIX.'webhook_logs')
                ->where($qb->expr()->in('id', $id))
                ->executeStatement();
        }
    }

    /**
     * Retains a rolling number of log records for a webhook id.
     */
    public function removeLimitExceedLogs(int $webHookId, int $logMax): int
    {
        $deletedLogs   = 0;
        $table_name    = $this->getTableName();
        $conn          = $this->getEntityManager()->getConnection();

        $id = $conn->createQueryBuilder()
            ->select('id')
            ->from($table_name)
            ->where('webhook_id = '.$webHookId)
            ->orderBy('id', 'DESC')
            ->setMaxResults(1)
            ->setFirstResult($logMax) // if log max limit is 1000 then it will fetch id of 1001'th record from last and we will delete all log which have id less than or equal to this id.
            ->executeQuery()->fetchOne();

        if ($id) {
            $sql = "DELETE FROM {$table_name} WHERE webhook_id = (?) and id <= (?) LIMIT ".self::LOG_DELETE_BATCH_SIZE;
            while ($rows = $conn->executeQuery($sql, [$webHookId, $id], [ParameterType::INTEGER, ParameterType::INTEGER])->rowCount()) {
                $deletedLogs += $rows;
            }
        }

        return $deletedLogs;
    }

    /**
     * Lets assume that all HTTP status codes 2** are a success.
     * This method will count the latest success codes until the $limit
     * and divide them with the all requests until the limit.
     *
     * 0 = 100% responses failed
     * 1 = 100% responses are successful
     * null = no log rows yet
     *
     * @param int $webhookId
     * @param int $limit
     *
     * @return float|null
     */
    public function getSuccessVsErrorStatusCodeRatio($webhookId, $limit)
    {
        // Generate query to select last X = $limit rows
        $selectqb = $this->_em->getConnection()->createQueryBuilder();
        $selectqb->select('*')
            ->from(MAUTIC_TABLE_PREFIX.'webhook_logs', $this->getTableAlias())
            ->where($this->getTableAlias().'.webhook_id = :webhookId')
            ->setFirstResult(0)
            ->setMaxResults($limit)
            ->orderBy($this->getTableAlias().'.date_added', 'DESC');

        // Count all responses
        $countAllQb = $this->_em->getConnection()->createQueryBuilder();
        $countAllQb->select('COUNT('.$this->getTableAlias().'.id) AS thecount')
            ->from(sprintf('(%s)', $selectqb->getSQL()), $this->getTableAlias())
            ->setParameter('webhookId', $webhookId);

        $result = $countAllQb->executeQuery()->fetchAssociative();

        if (isset($result['thecount'])) {
            $allCount = (int) $result['thecount'];
        } else {
            return null;
        }

        // Count successful responses
        $countSuccessQb = $this->_em->getConnection()->createQueryBuilder();
        $countSuccessQb->select('COUNT('.$this->getTableAlias().'.id) AS thecount')
            ->from(sprintf('(%s)', $selectqb->getSQL()), $this->getTableAlias())
            ->andWhere($countSuccessQb->expr()->gte($this->getTableAlias().'.status_code', 200))
            ->andWhere($countSuccessQb->expr()->lt($this->getTableAlias().'.status_code', 300))
            ->setParameter('webhookId', $webhookId);

        $result = $countSuccessQb->executeQuery()->fetchAssociative();

        if (isset($result['thecount'])) {
            $successCount = (int) $result['thecount'];
        }

        if (!empty($allCount) && isset($successCount)) {
            return $successCount / $allCount;
        }

        return null;
    }
}
