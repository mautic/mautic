<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\SmsBundle\Entity;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\CoreBundle\Helper\DateTimeHelper;

/**
 * Class StatRepository
 *
 * @package Mautic\SmsBundle\Entity
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
    public function getSmsStatus($trackingHash)
    {
        $q = $this->createQueryBuilder('s');
        $q->select('s')
            ->leftJoin('s.lead', 'l')
            ->leftJoin('s.sms', 'e')
            ->where(
                $q->expr()->eq('s.trackingHash', ':hash')
            )
            ->setParameter('hash', $trackingHash);

        $result = $q->getQuery()->getResult();

        return (!empty($result)) ? $result[0] : null;
    }

    /**
     * @param      $smsId
     * @param null $listId
     *
     * @return array
     */
    public function getSentStats($smsId, $listId = null)
    {
        $q = $this->_em->getConnection()->createQueryBuilder();
        $q->select('s.lead_id')
            ->from(MAUTIC_TABLE_PREFIX . 'sms_messages_stats', 's')
            ->where('s.sms_id = :sms')
            ->setParameter('sms', $smsId);

        if ($listId) {
            $q->andWhere('s.list_id = :list')
                ->setParameter('list', $listId);
        }

        $result = $q->execute()->fetchAll();

        //index by lead
        $stats = array();
        foreach ($result as $r) {
            $stats[$r['lead_id']] = $r['lead_id'];
        }

        unset($result);

        return $stats;
    }

    /**
     * @param int|array $smsIds
     * @param int       $listId
     *
     * @return int
     */
    public function getSentCount($smsIds = null, $listId = null)
    {
        $q = $this->_em->getConnection()->createQueryBuilder();

        $q->select('count(s.id) as sent_count')
            ->from(MAUTIC_TABLE_PREFIX . 'sms_message_stats', 's');

        if ($smsIds) {
            if (!is_array($smsIds)) {
                $smsIds = array((int) $smsIds);
            }
            $q->where(
                $q->expr()->in('s.sms_id', $smsIds)
            );
        }

        if ($listId) {
            $q->andWhere('s.list_id = ' . (int) $listId);
        }

        $q->andWhere('s.is_failed = :false')
            ->setParameter('false', false, 'boolean');

        $results = $q->execute()->fetchAll();

        return (isset($results[0])) ? $results[0]['sent_count'] : 0;
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
        $query = $this->createQueryBuilder('s');

        $query->select('IDENTITY(s.sms) AS sms_id, s.id, s.dateSent, e.title, IDENTITY(s.list) AS list_id, l.name as list_name, s.trackingHash as idHash')
            ->leftJoin('MauticSmsBundle:Sms', 'e', 'WITH', 'e.id = s.sms')
            ->leftJoin('MauticLeadBundle:LeadList', 'l', 'WITH', 'l.id = s.list')
            ->where(
                $query->expr()->eq('IDENTITY(s.lead)', $leadId)
            );

        if (!empty($options['ipIds'])) {
            $query->orWhere('s.ipAddress IN (' . implode(',', $options['ipIds']) . ')');
        }

        if (isset($options['filters']['search']) && $options['filters']['search']) {
            $query->andWhere(
                $query->expr()->like('e.title', $query->expr()->literal('%' . $options['filters']['search'] . '%'))
            );
        }

        $stats = $query->getQuery()->getArrayResult();

        return $stats;
    }

    /**
     * Updates lead ID (e.g. after a lead merge)
     *
     * @param $fromLeadId
     * @param $toLeadId
     */
    public function updateLead($fromLeadId, $toLeadId)
    {
        $q = $this->_em->getConnection()->createQueryBuilder();
        $q->update(MAUTIC_TABLE_PREFIX . 'sms_message_stats')
            ->set('sms_id', (int) $toLeadId)
            ->where('sms_id = ' . (int) $fromLeadId)
            ->execute();
    }

    /**
     * Delete a stat
     *
     * @param $id
     */
    public function deleteStat($id)
    {
        $this->_em->getConnection()->delete(MAUTIC_TABLE_PREFIX . 'sms_message_stats', array('id' => (int) $id));
    }

    /**
     * Fetch stats for some period of time.
     *
     * @param $smsIds
     * @param $fromDate
     * @param $state
     *
     * @return mixed
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getSmsStats($smsIds, $fromDate, $state)
    {
        if (!is_array($smsIds)) {
            $smsIds = array((int) $smsIds);
        }

        // Load points for selected period
        $q = $this->createQueryBuilder('s');

        $q->select('s.id, 1 as data, s.dateSent as date');

        $q->where(
            $q->expr()->in('IDENTITY(s.sms)', ':smses')
        )
            ->setParameter('smses', $smsIds);

        if ($state != 'sent') {
            $q->andWhere(
                $q->expr()->eq('s.is'.ucfirst($state), ':true')
            )
                ->setParameter('true', true, 'boolean');
        }

        $q->andwhere(
            $q->expr()->gte('s.dateSent', ':date')
        )
            ->setParameter('date', $fromDate)
            ->orderBy('s.dateSent', 'ASC');

        $stats = $q->getQuery()->getArrayResult();

        return $stats;
    }

    /**
     * @return string
     */
    public function getTableAlias()
    {
        return 's';
    }
}
