<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
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
     * @param array   $ipIds
     *
     * @return array
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getLeadLogs($leadId, array $ipIds = array())
    {
        $query = $this->createQueryBuilder('ll')
            ->select('IDENTITY(ll.event) AS event_id,
                    IDENTITY(e.campaign) AS campaign_id,
                    ll.dateTriggered,
                    e.name AS eventName,
                    e.description AS eventDescription,
                    c.name AS campaignName,
                    c.description AS campaignDescription')
            ->leftJoin('MauticCampaignBundle:Event', 'e', 'WITH', 'e.id = ll.event')
            ->leftJoin('MauticCampaignBundle:Campaign', 'c', 'WITH', 'c.id = e.campaign')
            ->where('ll.lead = ' . (int) $leadId)
            ->andWhere('e.eventType = :eventType')
            ->setParameter('eventType', 'trigger');

        if (!empty($ipIds)) {
            $query->orWhere('ll.ipAddress IN (' . implode(',', $ipIds) . ')');
        }

        return $query->getQuery()
            ->getArrayResult();
    }

    /**
     * Get a lead's upcoming events
     *
     * @param \Mautic\LeadBundle\Entity\Lead $lead entity
     *
     * @return array
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getUpcomingEvents(\Mautic\LeadBundle\Entity\Lead $lead)
    {
        $leadIps = array();

        /** @var \Mautic\CoreBundle\Entity\IpAddress $ip */
        foreach ($lead->getIpAddresses() as $ip) {
            $leadIps[] = $ip->getId();
        }

        $query = $this->createQueryBuilder('ll');
        $query->select('IDENTITY(ll.event) AS event_id,
                    IDENTITY(e.campaign) AS campaign_id,
                    ll.triggerDate,
                    e.name AS eventName,
                    e.description AS eventDescription,
                    c.name AS campaignName,
                    c.description AS campaignDescription')
            ->leftJoin('MauticCampaignBundle:Event', 'e', 'WITH', 'e.id = ll.event')
            ->leftJoin('MauticCampaignBundle:Campaign', 'c', 'WITH', 'c.id = e.campaign')
            ->where('ll.lead = :leadId')
            ->setParameter('leadId', $lead->getId())
            ->andWhere('e.eventType = :eventType')
            ->setParameter('eventType', 'action')
            ->andWhere('ll.isScheduled = :scheduled')
            ->setParameter('scheduled', 1)
            ->andwhere($query->expr()->gte('ll.triggerDate', ':today'))
            ->setParameter('today', new \DateTime())
            ->orderBy('ll.triggerDate');

        if (!empty($ipIds)) {
            $query->orWhere('ll.ipAddress IN (' . implode(',', $ipIds) . ')');
        }

        return $query->getQuery()
            ->getArrayResult();
    }
}
