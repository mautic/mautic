<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Entity;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\CoreBundle\Helper\DateTimeHelper;

/**
 * Class StatRepository
 *
 * @package Mautic\EmailBundle\Entity
 */
class StatRepository extends CommonRepository
{

    /**
     * @param $trackingHash
     *
     * @return mixed
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getEmailStatus($trackingHash)
    {
        $q = $this->createQueryBuilder('s');
        $q->select('s')
            ->leftJoin('s.lead', 'l')
            ->leftJoin('s.email', 'e')
            ->where(
                $q->expr()->eq('s.trackingHash', ':hash')
            )
            ->setParameter('hash', $trackingHash);
        $result = $q->getQuery()->getSingleResult();

        return $result;
    }

    /**
     * @param        $emailId
     * @param string $listId
     */
    public function getSentStats($emailId, $listId = null)
    {
        $q = $this->_em->getConnection()->createQueryBuilder();
        $q->select('s.*')
            ->from(MAUTIC_TABLE_PREFIX.'email_stats', 's')
            ->where('s.email_id = :email')
            ->setParameter('email', $emailId);

        if ($listId) {
            $q->andWhere('s.list_id = :list')
                ->setParameter('list', $listId);
        }

        $result = $q->execute()->fetchAll();

        //index by lead
        $stats = array();
        foreach ($result as $r) {
            $stats[$r['lead_id']] = $r;
        }

        return $stats;
    }

    /**
     * @param $emailId
     * @param $listId
     */
    public function getSentCount($emailId, $listId)
    {
        $q = $this->_em->getConnection()->createQueryBuilder();

        $q->select('count(*) as sentCount')
            ->from(MAUTIC_TABLE_PREFIX.'email_stats', 's')
            ->where('email_id = ' . $emailId)
            ->andWhere('list_id = ' . $listId)
            ->andWhere('is_failed = 0');
        $results = $q->execute()->fetchAll();

        return (isset($results[0])) ? $results[0]['sentCount'] : 0;
    }

    /**
     * @param $emailId
     * @param $listId
     */
    public function getReadCount($emailId, $listId)
    {
        $q = $this->_em->getConnection()->createQueryBuilder();

        $q->select('count(*) as readCount')
            ->from(MAUTIC_TABLE_PREFIX.'email_stats', 's')
            ->where('email_id = ' . $emailId)
            ->andWhere('list_id = ' . $listId)
            ->andWhere('is_read = 1');
        $results = $q->execute()->fetchAll();

        return (isset($results[0])) ? $results[0]['readCount'] : 0;
    }

    /**
     * @param           $emailIds
     * @param \DateTime $fromDate
     *
     * @return array
     */
    public function getOpenedRates($emailIds, \DateTime $fromDate = null)
    {
        $inIds = (!is_array($emailIds)) ? array($emailIds) : $emailIds;

        $sq = $this->_em->getConnection()->createQueryBuilder();
        $sq->select('e.email_id, count(*) as theCount')
            ->from(MAUTIC_TABLE_PREFIX.'email_stats', 'e')
            ->where('e.is_failed = 0')
            ->andWhere($sq->expr()->in('e.email_id', $inIds));

        if ($fromDate !== null) {
            //make sure the date is UTC
            $dt = new DateTimeHelper($fromDate);
            $sq->andWhere(
                $sq->expr()->gte('e.date_read', $sq->expr()->literal($dt->toUtcString()))
            );
        }
        $sq->groupBy('e.email_id');

        //get a total number of sent emails first
        $totalCounts = $sq->execute()->fetchAll();

        $return  = array();
        foreach ($inIds as $id) {
            $return[$id] = array(
                'totalCount' => 0,
                'readCount'  => 0,
                'readRate'   => 0
            );
        }

        foreach ($totalCounts as $t) {
            if ($t['email_id'] != null) {
                $return[$t['email_id']]['totalCount'] = (int) $t['theCount'];
            }
        }

        //now get a read count
        $sq->andWhere('e.is_read = 1');
        $readCounts = $sq->execute()->fetchAll();

        foreach ($readCounts as $r) {
            $return[$r['email_id']]['readCount'] = (int) $r['theCount'];
            $return[$r['email_id']]['readRate']  = ($return[$r['email_id']]['totalCount']) ?
                round(($r['theCount'] / $return[$r['email_id']]['totalCount']) * 100, 2) :
                0;
        }

        return (!is_array($emailIds)) ? $return[$emailIds] : $return;
    }

    /**
     * @param $emailId
     * @param $listId
     */
    public function getFailedCount($emailId, $listId)
    {
        $q = $this->_em->getConnection()->createQueryBuilder();

        $q->select('count(*) as failedCount')
            ->from(MAUTIC_TABLE_PREFIX.'email_stats', 's')
            ->where('email_id = ' . $emailId)
            ->andWhere('list_id = ' . $listId)
            ->andWhere('is_failed = 1');
        $results = $q->execute()->fetchAll();

        return (isset($results[0])) ? $results[0]['failedCount'] : 0;
    }

    /**
     * Get a lead's email stat
     *
     * @param integer $leadId
     * @param array   $ipIds
     *
     * @return array
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getLeadStats($leadId, array $ipIds = array())
    {
        $query = $this->createQueryBuilder('s')
            ->select('IDENTITY(s.email) AS email_id, s.dateRead, s.dateSent, e.subject, e.plainText')
            ->leftJoin('MauticEmailBundle:Email', 'e', 'WITH', 'e.id = s.email')
            ->where('s.lead = ' . $leadId);

        if (!empty($ipIds)) {
            $query->orWhere('s.ipAddress IN (' . implode(',', $ipIds) . ')');
        }

        return $query->getQuery()
            ->getArrayResult();
    }
}
