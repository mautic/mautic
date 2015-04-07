<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\LeadBundle\Entity\Lead as RealLead;

/**
 * EventRepository
 */
class EventRepository extends CommonRepository
{

    /**
     * Get a list of entities
     *
     * @param array $args
     *
     * @return Paginator
     */
    public function getEntities($args = array())
    {
        $q = $this
            ->createQueryBuilder('e')
            ->select('e, ec, ep')
            ->join('e.campaign', 'c')
            ->leftJoin('e.children', 'ec')
            ->leftJoin('e.parent', 'ep');

        $args['qb'] = $q;

        return parent::getEntities($args);
    }

    /**
     * Get array of published events based on type
     *
     * @param $type
     * @param $campaigns
     * @param $leadId           If included, only events that have not been triggered by the lead yet will be included
     * @param $positivePathOnly If negative, all events including those with a negative path will be returned
     * @return array
     */
    public function getPublishedByType($type, array $campaigns = null, $leadId = null, $positivePathOnly = true)
    {
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

        $q->where($expr)
            ->setParameter('type', $type);

        if (!empty($campaigns)) {
            $q->andWhere($q->expr()->in('c.id', ':campaigns'))
                ->setParameter('campaigns', $campaigns);
        }

        if ($leadId != null) {
            // Events that aren't fired yet
            $dq = $this->_em->createQueryBuilder();
            $dq->select('ellev.id')
                ->from('MauticCampaignBundle:LeadEventLog', 'ell')
                ->leftJoin('ell.event', 'ellev')
                ->leftJoin('ell.lead', 'el')
                ->where('ellev.id = e.id')
                ->andWhere(
                    $dq->expr()->eq('el.id', ':leadId')
                );

            $q->andWhere('e.id NOT IN('.$dq->getDQL().')')
                ->setParameter('leadId', $leadId);
        }

        if ($positivePathOnly) {
            $q->andWhere(
                $q->expr()->orX(
                    $q->expr()->neq('e.decisionPath',
                        $q->expr()->literal('no')
                    ),
                    $q->expr()->isNull('e.decisionPath')
                )
            );

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
     * Get the top level actions for a campaign and lead
     *
     * @param $campaign
     *
     * @return array
     */
    public function getRootLevelActions($campaign = null, $lead = null)
    {
        $leadId = ($lead instanceof RealLead) ? $lead->getId() : (int) $lead;

        $q = $this->createQueryBuilder('e');

        if ($leadId) {
            $q->select('c, e');
        } else {
            $q->select('c, e, l');
        }

        $q->join('e.campaign', 'c')
            ->leftJoin('c.leads', 'l')
            ->leftJoin('l.lead', 'cl');

        //make sure the published up and down dates are good
        $expr = $this->getPublishedByDateExpression($q, 'c');
        $expr->addMultiple(array(
            $q->expr()->eq('e.eventType', $q->expr()->literal('action')),
            $q->expr()->isNull('e.parent'),
        ));

        if ($campaign !== null) {
            $expr->add(
                $q->expr()->eq('c.id', ':campaign')
            );

            $campaignId = ($campaign instanceof Campaign) ? $campaign->getId() : $campaign;

            $q->setParameter('campaign', (int) $campaignId);
        }

        if ($leadId) {
            $expr->add(
                $q->expr()->eq('cl.id', $leadId)
            );
        }

        $q->where($expr);

        // Only events that have not been fired yet
        $dq2 = $this->_em->createQueryBuilder();
        if ($leadId) {
            $dq2->select('log_event.id');
        } else {
            $dq2->select('log_lead.id');
        }

        $dq2->from('MauticCampaignBundle:LeadEventLog', 'log')
            ->leftJoin('log.event', 'log_event')
            ->leftJoin('log.lead', 'log_lead')
            ->where('log_event.id = e.id')
            ->andWhere('IDENTITY(log.lead) = cl.id');

        if ($leadId) {
            // Only applicable ['campaign']['leads'] and thus applicable events
            $q->andWhere('e.id NOT IN('.$dq2->getDQL().')');
        } else {
            // Only applicable ['campaign']['leads'] and thus applicable events
            $q->andWhere('cl.id NOT IN('.$dq2->getDQL().')');
        }

        $results = $q->getQuery()->getArrayResult();

        return $results;
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
     * @param \DateTime $date   Defaults to events scheduled before now
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

        $expr = $this->getPublishedByDateExpression($q, 'c', false);
        $expr->add(
            $q->expr()->eq('o.isScheduled', 1)
        );

        $expr->add(
            $q->expr()->lte('o.triggerDate', ':now')
        );

        if (!empty($campaignId)) {
            $expr->add(
                $q->expr()->eq('c.id', (int) $campaignId)
            );
        }

        $q->where($expr)
            ->setParameter('now', $date);

        $results = $q->getQuery()->getArrayResult();

        return $results;
    }

    /**
     * Find the negative events, i.e. the events with a no decision path that do not have a "yes" decision that's been triggered
     *
     * @param null $campaignId
     */
    public function getNegativePendingEvents($campaignId = null)
    {
        $q = $this->createQueryBuilder('e')
            ->select('c, e, event_parent, event_grandparent, l')
            ->join('e.campaign', 'c')
            ->leftJoin('c.leads', 'l')
            ->leftJoin('l.lead', 'cl')
            ->leftJoin('e.parent', 'event_parent')
            ->leftJoin('event_parent.parent', 'event_grandparent')
            ->orderBy('e.order');

        //make sure the published up and down dates are good
        $expr = $this->getPublishedByDateExpression($q, 'c');

        $q->where($expr);

        if (!empty($campaignId)) {
            $q->andWhere($q->expr()->eq('c.id', ':campaign'))
                ->setParameter('campaign', $campaignId);
        }

        //only the "no" decision path
        $q->andWhere(
            $q->expr()->eq('e.decisionPath', $q->expr()->literal('no'))
        );

        // Only events that have not been fired yet
        $dq = $this->_em->createQueryBuilder();
        $dq->select('log_lead.id')
            ->from('MauticCampaignBundle:LeadEventLog', 'log')
            ->leftJoin('log.event', 'log_event')
            ->leftJoin('log.lead', 'log_lead')
            ->where('log_event.id = e.id')
            ->andWhere('IDENTITY(log.lead) = cl.id');

        // Only applicable ['campaign']['leads'] and thus applicable events
        $q->andWhere('cl.id NOT IN('.$dq->getDQL().')');

        // Only events that have do not have a yes that's been fired
        $dq2 = $this->_em->createQueryBuilder();
        $dq2->select('log_event_yes_lead.id')
            ->from('MauticCampaignBundle:LeadEventLog', 'log_yes')
            ->join('log_yes.event', 'log_event_yes')
            ->leftJoin('log_yes.lead', 'log_event_yes_lead')
            ->where(
                $dq2->expr()->andX(
                    //yes path
                    $dq2->expr()->eq('log_event_yes.decisionPath', $dq2->expr()->literal('yes')),

                    //on the same level as the no path
                    $dq2->expr()->eq('log_event_yes.order', 'e.order'),
                    $dq2->expr()->isNotNull('log_event_yes.parent'),
                    $dq2->expr()->eq('IDENTITY(log_event_yes.parent)', 'e.parent'),

                    //for the same lead
                    $dq2->expr()->eq('IDENTITY(log_yes.lead)', 'cl.id')
                )
            );

        // Only applicable ['campaign']['leads'] and thus applicable events
        $q->andWhere('cl.id NOT IN('.$dq2->getDQL().')');

        $results = $q->getQuery()->getArrayResult();

        return $results;
    }

    /**
     * Get array of events with stats
     *
     * @param array $args
     * @return array
     */
    public function getEvents($args = array())
    {
        $q = $this->createQueryBuilder('e')
            ->select('e, ec, ep')
            ->join('e.campaign', 'c')
            ->leftJoin('e.children', 'ec')
            ->leftJoin('e.parent', 'ep')
            ->orderBy('e.order');

        if (!empty($args['campaigns'])) {
            $q->andWhere($q->expr()->in('e.campaign', ':campaigns'))
                ->setParameter('campaigns', $args['campaigns']);
        }

        if (isset($args['positivePathOnly'])) {
            $q->andWhere(
                $q->expr()->orX(
                    $q->expr()->neq('e.decisionPath',
                        $q->expr()->literal('no')
                    ),
                    $q->expr()->isNull('e.decisionPath')
                )
            );

        }

        $events = $q->getQuery()->getArrayResult();

        return $events;
    }
}
