<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * LeadEventLogRepository
 */
class LeadEventLogRepository extends EntityRepository
{
	/**
     * Get a lead's page event log
     *
     * @param integer $leadId
     * @param array   $options
     *
     * @return array
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getLeadLogs($leadId, array $options = array())
    {
        $query = $this->createQueryBuilder('ll')
            ->select('IDENTITY(ll.event) AS event_id,
                    IDENTITY(e.campaign) AS campaign_id,
                    ll.dateTriggered,
                    e.name AS event_name,
                    e.description AS event_description,
                    c.name AS campaign_name,
                    c.description AS campaign_description,
                    ll.metadata,
                    e.type
                    '
            )
            ->leftJoin('MauticCampaignBundle:Event', 'e', 'WITH', 'e.id = ll.event')
            ->leftJoin('MauticCampaignBundle:Campaign', 'c', 'WITH', 'c.id = e.campaign')
            ->where('ll.lead = ' . (int) $leadId)
            ->andWhere('e.eventType = :eventType')
            ->setParameter('eventType', 'action');

        if (!empty($options['ipIds'])) {
            $query->orWhere('ll.ipAddress IN (' . implode(',', $options['ipIds']) . ')');
        }

        if (isset($options['filters']['search']) && $options['filters']['search']) {
            $query->andWhere($query->expr()->orX(
                $query->expr()->like('e.name', $query->expr()->literal('%' . $options['filters']['search'] . '%')),
                $query->expr()->like('e.description', $query->expr()->literal('%' . $options['filters']['search'] . '%')),
                $query->expr()->like('c.name', $query->expr()->literal('%' . $options['filters']['search'] . '%')),
                $query->expr()->like('c.description', $query->expr()->literal('%' . $options['filters']['search'] . '%'))
            ));
        }

        return $query->getQuery()->getArrayResult();
    }

    /**
     * Get a lead's upcoming events
     *
     * @param array $options
     *
     * @return array
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getUpcomingEvents(array $options = null)
    {
        $leadIps = array();

        $query = $this->createQueryBuilder('ll');
        $query->select('IDENTITY(ll.event) AS event_id,
                    IDENTITY(e.campaign) AS campaign_id,
                    ll.triggerDate,
                    IDENTITY(ll.lead) AS lead_id,
                    e.name AS event_name,
                    e.description AS event_description,
                    c.name AS campaign_name,
                    c.description AS campaign_description')
            ->leftJoin('MauticCampaignBundle:Event', 'e', 'WITH', 'e.id = ll.event')
            ->leftJoin('MauticCampaignBundle:Campaign', 'c', 'WITH', 'c.id = e.campaign')
            ->where($query->expr()->gte('ll.triggerDate', ':today'))
            ->setParameter('today', new \DateTime());

        if (isset($options['lead'])) {
            /** @var \Mautic\CoreBundle\Entity\IpAddress $ip */
            foreach ($options['lead']->getIpAddresses() as $ip) {
                $leadIps[] = $ip->getId();
            }

            $query->andWhere('ll.lead = :leadId')
                ->setParameter('leadId', $options['lead']->getId());
        }

        if (isset($options['scheduled'])) {
            $query->andWhere('ll.isScheduled = :scheduled')
                ->setParameter('scheduled', $options['scheduled'], 'boolean');
        }

        if (isset($options['eventType'])) {
            $query->andwhere('e.eventType = :eventType')
                ->setParameter('eventType', $options['eventType']);
        }

        if (isset($options['type'])) {
            $query->andwhere('e.type = :type')
                ->setParameter('type', $options['type']);
        }

        if (isset($options['limit'])) {
            $query->setMaxResults($options['limit']);
        } else {
            $query->setMaxResults(10);
        }

        $query->orderBy('ll.triggerDate');

        if (!empty($ipIds)) {
            $query->orWhere('ll.ipAddress IN (' . implode(',', $ipIds) . ')');
        }

        return $query->getQuery()
            ->getArrayResult();
    }

    /**
     * @param int        $campaignId
     * @param null       $eventId
     * @param null|array $leadIds
     */
    public function getCampaignLogCounts($campaignId, $leadIds)
    {
        $q = $this->_em->getConnection()->createQueryBuilder()
            ->select('o.event_id, count(o.lead_id) as lead_count')
            ->from(MAUTIC_TABLE_PREFIX.'campaign_lead_event_log', 'o');

        if (empty($leadIds)) {
            // Just force nothing
            $leadIds = array(0);
        }

        $q->where(
            $q->expr()->andX(
                $q->expr()->eq('o.campaign_id', (int) $campaignId),
                $q->expr()->in('o.lead_id', $leadIds),
                $q->expr()->orX(
                    $q->expr()->isNull('o.non_action_path_taken'),
                    $q->expr()->eq('o.non_action_path_taken', ':false')
                )
            )
        )
            ->setParameter('false', false, 'boolean')
            ->groupBy('o.event_id');

        $results = $q->execute()->fetchAll();

        $return = array();

        //group by event id
        foreach ($results as $l) {
            $return[$l['event_id']] = $l['lead_count'];
        }

        return $return;
    }

    /**
     * @param $campaignId
     * @param $leadId
     */
    public function removeScheduledEvents($campaignId, $leadId)
    {
        $conn = $this->_em->getConnection();
        $conn->delete(MAUTIC_TABLE_PREFIX.'campaign_lead_event_log', array(
            'lead_id'     => (int) $leadId,
            'campaign_id' => (int) $campaignId,
            'is_scheduled' => 1
        ));
    }

    /**
     * Updates lead ID (e.g. after a lead merge)
     *
     * @param $fromLeadId
     * @param $toLeadId
     */
    public function updateLead($fromLeadId, $toLeadId)
    {
        // First check to ensure the $toLead doesn't already exist
        $results = $this->_em->getConnection()->createQueryBuilder()
            ->select('cl.event_id')
            ->from(MAUTIC_TABLE_PREFIX.'campaign_lead_event_log', 'cl')
            ->where('cl.lead_id = '.$toLeadId)
            ->execute()
            ->fetchAll();
        $exists = array();
        foreach ($results as $r) {
            $exists[] = $r['event_id'];
        }

        $q = $this->_em->getConnection()->createQueryBuilder();
        $q->update(MAUTIC_TABLE_PREFIX.'campaign_lead_event_log')
            ->set('lead_id', (int) $toLeadId)
            ->where('lead_id = '.(int) $fromLeadId);

        if (!empty($exists)) {
            $q->andWhere(
                $q->expr()->notIn('event_id', $exists)
            )->execute();

            // Delete remaining leads as the new lead already belongs
            $this->_em->getConnection()->createQueryBuilder()
                ->delete(MAUTIC_TABLE_PREFIX.'campaign_lead_event_log')
                ->where('lead_id = '.(int) $fromLeadId)
                ->execute();
        } else {
            $q->execute();
        }
    }
}
