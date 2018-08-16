<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * @deprecated 2.13.0 to be removed in 3.0
 */
class LegacyEventRepository extends CommonRepository
{
    /**
     * Get a list of scheduled events.
     *
     * @param      $campaignId
     * @param bool $count
     * @param int  $limit
     *
     * @return array|bool
     */
    public function getScheduledEvents($campaignId, $count = false, $limit = 0)
    {
        $date = new \Datetime();

        $q = $this->getEntityManager()->createQueryBuilder()
            ->from('MauticCampaignBundle:LeadEventLog', 'o');

        $q->where(
            $q->expr()->andX(
                $q->expr()->eq('IDENTITY(o.campaign)', (int) $campaignId),
                $q->expr()->eq('o.isScheduled', ':true'),
                $q->expr()->lte('o.triggerDate', ':now')
            )
        )
            ->setParameter('now', $date)
            ->setParameter('true', true, 'boolean');

        if ($count) {
            $q->select('COUNT(o) as event_count');

            $results = $results = $q->getQuery()->getArrayResult();
            $count   = $results[0]['event_count'];

            return $count;
        }

        $q->select('o, IDENTITY(o.lead) as lead_id, IDENTITY(o.event) AS event_id')
            ->orderBy('o.triggerDate', 'DESC');

        if ($limit) {
            $q->setFirstResult(0)
                ->setMaxResults($limit);
        }

        $results = $q->getQuery()->getArrayResult();

        // Organize by lead
        $logs = [];
        foreach ($results as $e) {
            $logs[$e['lead_id']][$e['event_id']] = array_merge($e[0], ['lead_id' => $e['lead_id'], 'event_id' => $e['event_id']]);
        }
        unset($results);

        return $logs;
    }

    /**
     * Get array of published events based on type.
     *
     * @param       $type
     * @param array $campaigns
     * @param null  $leadId           If included, only events that have not been triggered by the lead yet will be included
     * @param bool  $positivePathOnly If negative, all events including those with a negative path will be returned
     *
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
            $dq = $this->getEntityManager()->createQueryBuilder();
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
                    $q->expr()->neq(
                        'e.decisionPath',
                        $q->expr()->literal('no')
                    ),
                    $q->expr()->isNull('e.decisionPath')
                )
            );
        }

        $results = $q->getQuery()->getArrayResult();

        //group them by campaign
        $events = [];
        foreach ($results as $r) {
            $events[$r['campaign']['id']][$r['id']] = $r;
        }

        return $events;
    }

    /**
     * Get the top level events for a campaign.
     *
     * @param $id
     * @param $includeDecisions
     *
     * @return array
     */
    public function getRootLevelEvents($id, $includeDecisions = false)
    {
        $q = $this->getEntityManager()->createQueryBuilder();

        $q->select('e')
            ->from('MauticCampaignBundle:Event', 'e', 'e.id')
            ->where(
                $q->expr()->andX(
                    $q->expr()->eq('IDENTITY(e.campaign)', (int) $id),
                    $q->expr()->isNull('e.parent')
                )
            );

        if (!$includeDecisions) {
            $q->andWhere(
                $q->expr()->neq('e.eventType', $q->expr()->literal('decision'))
            );
        }

        $results = $q->getQuery()->getArrayResult();

        return $results;
    }

    /**
     * Gets ids of leads who have already triggered the event.
     *
     * @param $events
     * @param $leadId
     *
     * @return array
     */
    public function getEventLogLeads($events, $leadId = null)
    {
        $q = $this->getEntityManager()->getConnection()->createQueryBuilder();

        $q->select('distinct(e.lead_id)')
            ->from(MAUTIC_TABLE_PREFIX.'campaign_lead_event_log', 'e')
            ->where(
                $q->expr()->in('e.event_id', $events)
            )
            ->setParameter('false', false, 'boolean');

        if ($leadId) {
            $q->andWhere(
                $q->expr()->eq('e.lead_id', (int) $leadId)
            );
        }

        $results = $q->execute()->fetchAll();

        $log = [];
        foreach ($results as $r) {
            $log[] = $r['lead_id'];
        }

        unset($results);

        return $log;
    }

    /**
     * Get an array of events that have been triggered by this lead.
     *
     * @param $leadId
     *
     * @return array
     */
    public function getLeadTriggeredEvents($leadId)
    {
        $q = $this->getEntityManager()->createQueryBuilder()
            ->select('e, c, l')
            ->from('MauticCampaignBundle:Event', 'e')
            ->join('e.campaign', 'c')
            ->join('e.log', 'l');

        //make sure the published up and down dates are good
        $q->where($q->expr()->eq('IDENTITY(l.lead)', (int) $leadId));

        $results = $q->getQuery()->getArrayResult();

        $return = [];
        foreach ($results as $r) {
            $return[$r['id']] = $r;
        }

        return $return;
    }

    /**
     * @param $campaignId
     *
     * @return array
     */
    public function getCampaignActionAndConditionEvents($campaignId)
    {
        $q = $this->getEntityManager()->createQueryBuilder();
        $q->select('e')
            ->from('MauticCampaignBundle:Event', 'e', 'e.id')
            ->where($q->expr()->eq('IDENTITY(e.campaign)', (int) $campaignId))
            ->andWhere($q->expr()->in('e.eventType', ['action', 'condition']));

        $events = $q->getQuery()->getArrayResult();

        return $events;
    }

    /**
     * Get the non-action log.
     *
     * @param            $campaignId
     * @param array      $leads
     * @param array      $havingEvents
     * @param array      $excludeEvents
     * @param bool|false $excludeScheduledFromHavingEvents
     *
     * @return array
     */
    public function getEventLog($campaignId, $leads = [], $havingEvents = [], $excludeEvents = [], $excludeScheduledFromHavingEvents = false)
    {
        $q = $this->getEntityManager()->getConnection()->createQueryBuilder();

        $q->select('e.lead_id, e.event_id, e.date_triggered, e.is_scheduled')
            ->from(MAUTIC_TABLE_PREFIX.'campaign_lead_event_log', 'e')
            ->where(
                $q->expr()->eq('e.campaign_id', (int) $campaignId)
            )
            ->groupBy('e.lead_id, e.event_id, e.date_triggered, e.is_scheduled');

        if (!empty($leads)) {
            $leadsQb = $this->getEntityManager()->getConnection()->createQueryBuilder();

            $leadsQb->select('null')
                ->from(MAUTIC_TABLE_PREFIX.'campaign_lead_event_log', 'include_leads')
                ->where(
                    $leadsQb->expr()->eq('include_leads.lead_id', 'e.lead_id'),
                    $leadsQb->expr()->in('include_leads.lead_id', $leads)
                );

            $q->andWhere(
                sprintf('EXISTS (%s)', $leadsQb->getSQL())
            );
        }

        if (!empty($havingEvents)) {
            $eventsQb = $this->getEntityManager()->getConnection()->createQueryBuilder();

            $eventsQb->select('null')
                ->from(MAUTIC_TABLE_PREFIX.'campaign_lead_event_log', 'include_events')
                ->where(
                    $eventsQb->expr()->eq('include_events.lead_id', 'e.lead_id'),
                    $eventsQb->expr()->in('include_events.event_id', $havingEvents)
                );

            if ($excludeScheduledFromHavingEvents) {
                $eventsQb->andWhere(
                    $eventsQb->expr()->eq('include_events.is_scheduled', ':false')
                );
                $q->setParameter('false', false, 'boolean');
            }

            $q->having(
                sprintf('EXISTS (%s)', $eventsQb->getSQL())
            );
        }

        if (!empty($excludeEvents)) {
            $eventsQb = $this->getEntityManager()->getConnection()->createQueryBuilder();

            $eventsQb->select('null')
                ->from(MAUTIC_TABLE_PREFIX.'campaign_lead_event_log', 'exclude_events')
                ->where(
                    $eventsQb->expr()->eq('exclude_events.lead_id', 'e.lead_id'),
                    $eventsQb->expr()->in('exclude_events.event_id', $excludeEvents)
                );

            $eventsQb->andHaving(
                sprintf('NOT EXISTS (%s)', $eventsQb->getSQL())
            );
        }

        $results = $q->execute()->fetchAll();

        $log = [];
        foreach ($results as $r) {
            $leadId  = $r['lead_id'];
            $eventId = $r['event_id'];

            unset($r['lead_id']);
            unset($r['event_id']);

            $log[$leadId][$eventId] = $r;
        }

        unset($results);

        return $log;
    }
}
