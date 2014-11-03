<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Entity;

use Doctrine\ORM\Query\Expr\Join;
use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * EventRepository
 */
class EventRepository extends CommonRepository
{
    /**
     * Get array of published events based on type
     *
     * @param $type
     * @param $eventType
     * @param $campaigns
     * @param $topLevelOnly
     *
     * @return array
     */
    public function getPublishedByType($type, $eventType = null, array $campaigns = null)
    {
        $now = new \DateTime();
        $q = $this->createQueryBuilder('e')
            ->select('c, e, ec, ep, ecc')
            ->join('e.campaign', 'c')
            ->leftJoin('e.children', 'ec')
            ->leftJoin('e.parent', 'ep')
            ->leftJoin('ec.campaign', 'ecc')
            ->orderBy('e.order');

        //make sure the published up and down dates are good
        $expr = $this->getPublishedByDateExpression($q, 'c');
        $expr->add(
            $q->expr()->eq('e.type', ':type')
        );

        if (!empty($eventType)) {
            $expr->add(
                $q->expr()->eq('e.eventType', ':eventType')
            );
            $q->setParameter('eventType', ':eventType');
        }

        $q->where($expr)
            ->setParameter('now', $now)
            ->setParameter('type', $type);

        if (!empty($campaigns)) {
            $q->andWhere($q->expr()->in('c.id', ':campaigns'))
                ->setParameter('campaigns', $campaigns);
        }

        $results = $q->getQuery()->getArrayResult();

        //group them by campaign
        $events = array();
        foreach ($results as $r) {
            $events[$r['campaign']['id']][$r['id']] = $r;
        }

        return $events;
    }

    /**
     * Get array of published events based on type for a specific lead
     *
     * @param      $type
     * @param int  $leadId
     *
     * @return array
     */
    public function getPublishedByTypeForLead($type, $leadId)
    {
        //get a list of campaigns
        $q = $this->_em->getConnection()->createQueryBuilder();
        $q->select('l.campaign_id')
            ->from(MAUTIC_TABLE_PREFIX.'campaign_leads', 'l')
            ->where(
                $q->expr()->eq('l.lead_id', $leadId)
            );
        $results   = $q->execute()->fetchAll();
        $campaigns = array();
        foreach ($results as $r) {
            $campaigns[] = $r['campaign_id'];
        }

        if (empty($campaigns)) {
            //lead not part of any campaign
            return array();
        }

        $now = new \DateTime();
        $q = $this->createQueryBuilder('e')
            ->select('c, e, ec, ep')
            ->join('e.campaign', 'c')
            ->leftJoin('e.parent', 'ep')
            ->leftJoin('e.children', 'ec')
            ->orderBy('c.id, e.order');

        //make sure the published up and down dates are good
        $expr = $this->getPublishedByDateExpression($q, 'c');
        $expr->add(
            $q->expr()->eq('e.type', ':type')
        );

        //limit to campaigns the lead is part of
        $expr->add(
            $q->expr()->in('c.id', $campaigns)
        );

        if (!empty($eventType)) {
            $expr->add(
                $q->expr()->eq('e.eventType', ':eventType')
            );
            $q->setParameter('eventType', ':eventType');
        }

        $q->where($expr)
            ->setParameter('now', $now)
            ->setParameter('type', $type);

        $results = $q->getQuery()->getArrayResult();

        //group them by campaign
        $events = array();
        foreach ($results as $r) {
            $events[$r['campaign']['id']][$r['id']] = $r;
        }

        return $events;
    }

    /**
     * Get an array of events that have been triggered by this lead
     *
     * @param $type
     * @param $leadId
     *
     * @return array
     */
    public function getLeadTriggeredEvents($leadId)
    {
        $q = $this->_em->createQueryBuilder()
            ->select('e, c, l')
            ->from('MauticCampaignBundle:Event', 'e')
            ->join('e.campaign', 'c')
            ->join('e.log', 'l');

        //make sure the published up and down dates are good
        $q->where($q->expr()->eq('IDENTITY(l.lead)', (int) $leadId));

        $results = $q->getQuery()->getArrayResult();

        $return = array();
        foreach ($results as $r) {
            $return[$r['id']] = $r;
        }

        return $return;
    }

    /**
     * Get a list of lead IDs for a specific event
     *
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
            ->where('e.event_id = ' . (int) $eventId)
            ->execute()
            ->fetchAll();

        $return = array();
        foreach ($results as $r) {
            $return[] = $r['lead_id'];
        }

        return $return;
    }

    /**
     * Get a list of scheduled events
     *
     * @param mixed $campaignId
     * @param \DateTime $date
     *
     * @return array
     */
    public function getPublishedScheduled($campaignId = null, \DateTime $date = null)
    {

        if ($date == null) {
            $date = new \Datetime();
        }

        $q = $this->_em->createQueryBuilder()
            ->select('e, c, o, i, l')
            ->from('MauticCampaignBundle:LeadEventLog', 'o')
            ->join('o.event', 'e')
            ->join('e.campaign', 'c')
            ->join('o.ipAddress', 'i')
            ->join('o.lead', 'l');

        $expr = $this->getPublishedByDateExpression($q, 'c');
        $expr->add(
            $q->expr()->eq('o.isScheduled', 1)
        );

        $expr->add(
            $q->expr()->gte('o.triggerDate', ':now')
        );

        if (!empty($campaignId)) {
            $expr->add(
                $q->expr()->eq('c.id', (int) $campaignId)
            );
        }

        $q->where($expr)
            ->setParameter('now', $date);

        $results = $q->getQuery()->getResult();

        return $results;
    }
}
