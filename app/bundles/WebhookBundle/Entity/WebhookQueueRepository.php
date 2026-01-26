<?php

namespace Mautic\WebhookBundle\Entity;

use Doctrine\DBAL\ArrayParameterType;
use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\CoreBundle\Helper\DateTimeHelper;

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
     * @param array<int> $idList
     */
    public function incrementRetryCount(array $idList): void
    {
        if (!count($idList)) {
            return;
        }

        $qb = $this->_em->getConnection()->createQueryBuilder();
        $qb->update(MAUTIC_TABLE_PREFIX.'webhook_queue')
            ->where(
                $qb->expr()->in('id', ':ids')
            )
            ->set('retries', 'retries + 1')
            ->set('date_modified', ':date_modified')
            ->setParameter('ids', $idList, ArrayParameterType::INTEGER)
            ->setParameter('date_modified', (new \DateTimeImmutable())->format(DateTimeHelper::FORMAT_DB))
            ->executeStatement();
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
            ->executeQuery()
            ->fetchOne();

        return (bool) $result;
    }
}
