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

class LogRepository extends CommonRepository
{
    /**
     * Retains a rolling number of log records for a webhook id.
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
            ->execute()->fetch();

        if ((int) $count['log_count'] >= (int) $logMax) {
            $qb = $this->_em->getConnection()->createQueryBuilder();

            $id = $qb->select('id')
                ->from(MAUTIC_TABLE_PREFIX.'webhook_logs', $this->getTableAlias())
                ->where('webhook_id = '.$webhookId)
                ->orderBy('date_added', 'ASC')->setMaxResults(1)
                ->execute()->fetch();

            $qb = $this->_em->getConnection()->createQueryBuilder();

            $qb->delete(MAUTIC_TABLE_PREFIX.'webhook_logs')
                ->where($qb->expr()->in('id', $id))
                ->execute();
        }
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

        $result = $countAllQb->execute()->fetch();

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

        $result = $countSuccessQb->execute()->fetch();

        if (isset($result['thecount'])) {
            $successCount = (int) $result['thecount'];
        }

        if (!empty($allCount) && isset($successCount)) {
            return $successCount / $allCount;
        }

        return null;
    }
}
