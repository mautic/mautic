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
    public function getSentCount($emailId = 0, $listId = 0)
    {
        $q = $this->_em->getConnection()->createQueryBuilder();

        $q->select('count(*) as sentCount')
            ->from(MAUTIC_TABLE_PREFIX.'email_stats', 's');

        if ($emailId) {
            $q->where('email_id = ' . $emailId);
        }

        if ($listId) {
            $q->andWhere('list_id = ' . $listId);
        }

        $q->andWhere('is_failed = 0');
        
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
     * @param array   $options
     *
     * @return array
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getLeadStats($leadId, array $options = array())
    {
        $query = $this->createQueryBuilder('s')
            ->select('IDENTITY(s.email) AS email_id, s.dateRead, s.dateSent, e.subject, e.plainText')
            ->leftJoin('MauticEmailBundle:Email', 'e', 'WITH', 'e.id = s.email')
            ->where('s.lead = ' . $leadId);

        if (!empty($options['ipIds'])) {
            $query->orWhere('s.ipAddress IN (' . implode(',', $options['ipIds']) . ')');
        }

        if (isset($options['filters']['search']) && $options['filters']['search']) {
            $query->andWhere($query->expr()->orX(
                $query->expr()->like('e.subject', $query->expr()->literal('%' . $options['filters']['search'] . '%')),
                $query->expr()->like('e.plainText', $query->expr()->literal('%' . $options['filters']['search'] . '%'))
            ));
        }

        return $query->getQuery()->getArrayResult();
    }

    /**
     * Get proper date label format depending on what date scope we want to display
     *
     * @param char $unit: php.net/manual/en/dateinterval.construct.php#refsect1-dateinterval.construct-parameters
     *
     * @return string
     */
    public function getDateLabelFromat($unit = 'D')
    {
        $format = '';
        if ($unit == 'H') {
            $format = 'H:00';
        } elseif ($unit == 'D') {
            $format = 'jS F';
        } elseif ($unit == 'W') {
            $format = 'W';
        } elseif ($unit == 'M') {
            $format = 'F y';
        } elseif ($unit == 'Y') {
            $format = 'Y';
        }
        return $format;
    }

    /**
     * Prepares data structure of labels and values needed for line graph.
     * fromDate variable can be used for SQL query as a limit.
     *
     * @param integer $amount of units
     * @param char $unit: php.net/manual/en/dateinterval.construct.php#refsect1-dateinterval.construct-parameters
     *
     * @return array
     */
    public function prepareStatsGraphDataBefore($amount = 30, $unit = 'D')
    {
        $isTime = '';

        if ($unit == 'H') {
            $isTime = 'T';
        }

        $format = $this->getDateLabelFromat($unit);

        $date = new \DateTime();
        $oneUnit = new \DateInterval('P'.$isTime.'1'.$unit);
        $data = array('labels' => array(), 'values' => array());

        // Prefill $data arrays
        for ($i = 0; $i < $amount; $i++) {
            $data['labels'][$i] = $date->format($format);
            $data['values'][$i] = 0;
            $date->sub($oneUnit);
        }

        $data['fromDate'] = $date;

        return $data;
    }

    /**
     * Fills into graph data values grouped by time unit
     *
     * @param array $data from prepareStatsGraphDataBefore
     * @param array $stats from database
     * @param char $unit: php.net/manual/en/dateinterval.construct.php#refsect1-dateinterval.construct-parameters
     *
     * @return array
     */
    public function prepareStatsGraphDataAfter($data, $stats, $unit)
    {
        // Group hits by date
        foreach ($stats as $stat) {
            if (is_string($stat['dateSent'])) {
                $stat['dateSent'] = new \DateTime($stat['dateSent']);
            }

            $oneItem = $stat['dateSent']->format($this->getDateLabelFromat($unit));
            if (($itemKey = array_search($oneItem, $data['labels'])) !== false) {
                $data['values'][$itemKey]++;
            }
        }

        $data['values'] = array_reverse($data['values']);
        $data['labels'] = array_reverse($data['labels']);

        return $data;
    }
}
