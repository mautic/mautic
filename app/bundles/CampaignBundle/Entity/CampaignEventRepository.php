<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\CoreBundle\Helper\DateTimeHelper;

/**
 * ActionRepository
 */
class CampaignEventRepository extends CommonRepository
{

    /**
     * Get array of published campaigns based on campaign total
     *
     * @param $campaigns
     *
     * @return array
     */
    public function getPublishedByCampaignTotal($campaigns = 0)
    {
        $now = new \DateTime();

        $q = $this->createQueryBuilder('a')
            ->select('partial a.{id, type, name, properties, settings}, partial r.{id, name, campaigns, color}')
            ->leftJoin('a.range', 'r')
            ->orderBy('a.order');

        //make sure the published up and down dates are good
        $q->where(
            $q->expr()->andX(
                $q->expr()->gte('r.campaigns', $campaigns),
                $q->expr()->eq('r.isPublished', true),
                $q->expr()->orX(
                    $q->expr()->isNull('r.publishUp'),
                    $q->expr()->gte('r.publishUp', ':now')
                ),
                $q->expr()->orX(
                    $q->expr()->isNull('r.publishDown'),
                    $q->expr()->lte('r.publishDown', ':now')
                )
            )
        )
            ->setParameter('now', $now);

        $results = $q->getQuery()->getResult();
        return $results;
    }

    /**
     * Get array of published actions based on type
     *
     * @param $type
     *
     * @return array
     */
    public function getPublishedByType($type)
    {
        $now = new \DateTime();
        $q = $this->createQueryBuilder('e')
            ->select('partial e.{id, type, name, properties, settings}, partial t.{id, name, campaigns, color}')
            ->join('e.campaign', 't')
            ->orderBy('e.order');

        //make sure the published up and down dates are good
        $q->where(
            $q->expr()->andX(
                $q->expr()->eq('e.type', ':type'),
                $q->expr()->eq('t.isPublished', true),
                $q->expr()->orX(
                    $q->expr()->isNull('t.publishUp'),
                    $q->expr()->gte('t.publishUp', ':now')
                ),
                $q->expr()->orX(
                    $q->expr()->isNull('t.publishDown'),
                    $q->expr()->lte('t.publishDown', ':now')
                )
            )
        )
            ->setParameter('now', $now)
            ->setParameter('type', $type);

        $results = $q->getQuery()->getResult();
        return $results;
    }

    /**
     * @param $type
     * @param $leadId
     *
     * @return array
     */
    public function getLeadCampaignedEvents($leadId)
    {
        $q = $this->_em->getConnection()->createQueryBuilder()
            ->select('e')
            ->from(MAUTIC_TABLE_PREFIX . 'campaign_lead_event_log', 'x')
            ->innerJoin('x', MAUTIC_TABLE_PREFIX . 'campaign_campaign_events', 'e', 'x.campaignevent_id = e.id')
            ->innerJoin('e', MAUTIC_TABLE_PREFIX . 'campaign_campaigns', 't', 'e.campaign_id = t.id');

        //make sure the published up and down dates are good
        $q->where($q->expr()->eq('x.lead_id', (int) $leadId));

        $results = $q->execute()->fetchAll();

        $return = array();
        foreach ($results as $r) {
            $return[$r['id']] = $r;
        }

        return $return;
    }


    /**
     * @param $type
     * @param $eventId
     *
     * @return array
     */
    public function getLeadsForEvent($eventId)
    {
        $results = $this->_em->getConnection()->createQueryBuilder()
            ->select('e.lead_id')
            ->from(MAUTIC_TABLE_PREFIX.'campaign_lead_event_log', 'e')
            ->where('e.campaignevent_id = ' . (int) $eventId)
            ->execute()
            ->fetchAll();

        $return = array();
        foreach ($results as $r) {
            $return[] = $r['lead_id'];
        }

        return $return;
    }
}
