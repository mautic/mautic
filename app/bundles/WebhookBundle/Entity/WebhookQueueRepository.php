<?php

/*
 * @copyright   Mautic, Inc
 * @author      Mautic, Inc
 *
 * @link        http://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\WebhookBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

class WebhookQueueRepository extends CommonRepository
{
    /**
     * Deletes all the webhook queues by ID.
     *
     * @param $idList array of webhookqueue IDs
     */
    public function deleteQueuesById(array $idList)
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
            ->execute();
    }

    /**
     * Gets a count of the webhook queues filtered by the webhook id.
     *
     * @param $id int (for Webhooks)
     *
     * @return int
     */
    public function getQueueCountByWebhookId($id)
    {
        // if no idea was sent (the hook was deleted) then return a count of 0
        if (!$id) {
            return 0;
        }

        $qb    = $this->_em->getConnection()->createQueryBuilder();
        $count = $qb->select('count('.$this->getTableAlias().'.id) as webhook_count')
            ->from(MAUTIC_TABLE_PREFIX.'webhook_queue', $this->getTableAlias())
            ->where($this->getTableAlias().'webhook_id = :id')
            ->setParameter('id', $id)
            ->execute()
            ->fetch();

        if (isset($count['webhook_count'])) {
            return $count['webhook_count'];
        }

        return 0;
    }
}
