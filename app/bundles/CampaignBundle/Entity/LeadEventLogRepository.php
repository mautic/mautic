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
                    e.name AS eventName,
                    e.description AS eventDescription,
                    c.name AS campaignName,
                    c.description AS campaignDescription')
            ->leftJoin('MauticCampaignBundle:Event', 'e', 'WITH', 'e.id = ll.event')
            ->leftJoin('MauticCampaignBundle:Campaign', 'c', 'WITH', 'c.id = e.campaign')
            ->where('ll.lead = ' . (int) $leadId)
            ->andWhere('e.eventType = :eventType')
            ->setParameter('eventType', 'trigger');

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
                    e.name AS eventName,
                    e.description AS eventDescription,
                    c.name AS campaignName,
                    c.description AS campaignDescription')
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
                ->setParameter('scheduled', $options['scheduled']);
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
}
