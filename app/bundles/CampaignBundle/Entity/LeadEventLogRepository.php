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
     * Get a lead's page hits
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
            ->select('IDENTITY(ll.event) AS event_id, IDENTITY(e.campaign) AS campaign_id, ll.dateTriggered, e.name AS eventName, c.name AS campaignName')
            ->leftJoin('MauticCampaignBundle:Event', 'e', 'WITH', 'e.id = ll.event')
            ->leftJoin('MauticCampaignBundle:Campaign', 'c', 'WITH', 'c.id = e.campaign')
            ->where('ll.lead = ' . $leadId)
            ->andWhere('e.eventType = :eventType')
            ->setParameter('eventType', 'trigger');


        if (!empty($ipIds)) {
            $query->orWhere('ll.ipAddress IN (' . implode(',', $ipIds) . ')');
        }

        return $query->getQuery()
            ->getArrayResult();
    }
}