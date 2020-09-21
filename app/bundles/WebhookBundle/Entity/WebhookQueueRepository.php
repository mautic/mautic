<?php

namespace Mautic\WebhookBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * @extends CommonRepository<WebhookQueue>
 */
class WebhookQueueRepository extends CommonRepository
{
    /**
     * Deletes all the webhook queues by ID.
     *
     * @param $idList array of webhookqueue IDs
     */
    public function deleteQueuesById(array $idList): void
    {
        // don't process the list if there are no items in it
        if (!count($idList)) {
            return;
        }

        $qb = $this->_em->getConnection()->createQueryBuilder();
        $qb->delete(MAUTIC_TABLE_PREFIX.'webhook_queue')
            ->where(
                $qb->expr()->in('id', $idList)
            )
            ->executeStatement();
    }

    /**
     * Gets a count of the webhook queues filtered by the webhook id.
     *
     * @param $id int (for Webhooks)
     *
     * @deprecated
     */
    public function getQueueCountByWebhookId($id): int
    {
        // if no id was sent (the hook was deleted) then return a count of 0
        if (!$id) {
            return 0;
        }

        $qb = $this->_em->getConnection()->createQueryBuilder();

        return (int) $qb->select('count(*) as webhook_count')
            ->from(MAUTIC_TABLE_PREFIX.'webhook_queue', $this->getTableAlias())
            ->where($this->getTableAlias().'.webhook_id = :id')
            ->setParameter('id', $id)
            ->executeQuery()
            ->fetchOne();
    }

    /**
     * Check if there is webhook to process.
     */
    public function exists(int $id): bool
    {
        $qb     = $this->_em->getConnection()->createQueryBuilder();
        $result = $qb->select($this->getTableAlias().'.id')
            ->from(MAUTIC_TABLE_PREFIX.'webhook_queue', $this->getTableAlias())
            ->where($this->getTableAlias().'.webhook_id = :id')
            ->setParameter('id', $id)
            ->setMaxResults(1)
            ->execute()
            ->fetch();

        return (bool) $result;
    }

    /**
     * Gets consecutive queue IDs as ranges.
     *
     * @param $webhookId
     *
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    public function getConsecutiveIDsAsRanges($webhookId)
    {
        $webhookId = (int) $webhookId;
        if (1 > $webhookId) {
            throw new \InvalidArgumentException('webhook ID must be greater than zero');
        }

        $connection = $this->_em->getConnection();
        $query      = sprintf('SELECT 
            MIN(id) AS min_id,
            MAX(id) AS max_id
        FROM
            (SELECT 
                @row_number:=CASE
                        WHEN @id = webhook_id THEN @row_number
                        ELSE @row_number + 1
                    END AS webhook_id_group,
                    @id:=webhook_id AS webhook_id,
                    id
            FROM
                %swebhook_queue, (SELECT @row_number:=0, @id:=1) AS t
            ORDER BY id) AS MWQ
        WHERE
            MWQ.webhook_id = :webhookId
        GROUP BY webhook_id_group', MAUTIC_TABLE_PREFIX);

        $statement = $connection->prepare($query);
        $statement->execute([
            ':webhookId' => $webhookId,
        ]);

        return $statement->fetchAll();
    }
}
