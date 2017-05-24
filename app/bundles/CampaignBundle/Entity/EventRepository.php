<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Entity;

use Doctrine\ORM\QueryBuilder;
use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * EventRepository.
 */
class EventRepository extends CommonRepository
{
    /**
     * Get a list of entities.
     *
     * @param array $args
     *
     * @return \Doctrine\ORM\Tools\Pagination\Paginator
     */
    public function getEntities(array $args = [])
    {
        $select = 'e';
        $q      = $this
            ->createQueryBuilder('e')
            ->join('e.campaign', 'c');

        if (!empty($args['campaign_id'])) {
            $q->andWhere(
                $q->expr()->eq('IDENTITY(e.campaign)', (int) $args['campaign_id'])
            );
        }

        if (empty($args['ignore_children'])) {
            $select .= ', ec, ep';
            $q->leftJoin('e.children', 'ec')
                ->leftJoin('e.parent', 'ep');
        }

        $q->select($select);

        $args['qb'] = $q;

        return parent::getEntities($args);
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
     * Get array of events by parent.
     *
     * @param      $parentId
     * @param null $decisionPath
     * @param null $eventType
     *
     * @return array
     */
    public function getEventsByParent($parentId, $decisionPath = null, $eventType = null)
    {
        $q = $this->getEntityManager()->createQueryBuilder();

        $q->select('e')
            ->from('MauticCampaignBundle:Event', 'e', 'e.id')
            ->where(
                $q->expr()->eq('IDENTITY(e.parent)', (int) $parentId)
            );

        if (null !== $decisionPath) {
            $q->andWhere(
                $q->expr()->eq('e.decisionPath', ':decisionPath')
            )
                ->setParameter('decisionPath', $decisionPath);
        }

        if (null !== $eventType) {
            $q->andWhere(
                $q->expr()->eq('e.eventType', ':eventType')
            )
              ->setParameter('eventType', $eventType);
        }

        return $q->getQuery()->getArrayResult();
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
     * @param $campaignId
     *
     * @return array
     */
    public function getCampaignEvents($campaignId)
    {
        $q = $this->getEntityManager()->createQueryBuilder();
        $q->select('e, IDENTITY(e.parent)')
            ->from('MauticCampaignBundle:Event', 'e', 'e.id')
            ->where(
                $q->expr()->eq('IDENTITY(e.campaign)', (int) $campaignId)
            )
            ->orderBy('e.order', 'ASC');

        $results = $q->getQuery()->getArrayResult();

        // Fix the parent ID
        $events = [];
        foreach ($results as $id => $r) {
            $r[0]['parent_id'] = $r[1];
            $events[$id]       = $r[0];
        }
        unset($results);

        return $events;
    }

    /**
     * Get array of events with stats.
     *
     * @param array $args
     *
     * @return array
     */
    public function getEvents($args = [])
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
                    $q->expr()->neq(
                        'e.decisionPath',
                        $q->expr()->literal('no')
                    ),
                    $q->expr()->isNull('e.decisionPath')
                )
            );
        }

        $events = $q->getQuery()->getArrayResult();

        return $events;
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

    /**
     * Null event parents in preparation for deleting a campaign.
     *
     * @param $campaignId
     */
    public function nullEventParents($campaignId)
    {
        $this->getEntityManager()->getConnection()->update(
            MAUTIC_TABLE_PREFIX.'campaign_events',
            ['parent_id'   => null],
            ['campaign_id' => (int) $campaignId]
        );
    }

    /**
     * Null event parents in preparation for deleting events from a campaign.
     *
     * @param $events
     */
    public function nullEventRelationships($events)
    {
        $qb = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $qb->update(MAUTIC_TABLE_PREFIX.'campaign_events')
            ->set('parent_id', ':null')
            ->setParameter('null', null)
            ->where(
                $qb->expr()->in('parent_id', $events)
            )
            ->execute();
    }

    /**
     * @return string
     */
    public function getTableAlias()
    {
        return 'e';
    }

    /**
     * {@inheritdoc}
     *
     * For the API
     */
    public function getSearchCommands()
    {
        return $this->getStandardSearchCommands();
    }

    /**
     * @param        $channel
     * @param null   $campaignId
     * @param string $eventType
     */
    public function getEventsByChannel($channel, $campaignId = null, $eventType = 'action')
    {
        $q = $this->getEntityManager()->createQueryBuilder();

        $q->select('e')
            ->from('MauticCampaignBundle:Event', 'e', 'e.id');

        $expr = $q->expr()->andX();
        if ($campaignId) {
            $expr->add(
                $q->expr()->eq('IDENTITY(e.campaign)', (int) $campaignId)
            );

            $q->orderBy('e.order');
        }

        $expr->add(
            $q->expr()->eq('e.channel', ':channel')
        );
        $q->setParameter('channel', $channel);

        if ($eventType) {
            $expr->add(
                $q->expr()->eq('e.eventType', ':eventType')
            );
            $q->setParameter('eventType', $eventType);
        }

        $q->where($expr);

        $results = $q->getQuery()->getResult();

        return $results;
    }

    /**
     * {@inheritdoc}
     *
     * For the API
     */
    protected function addCatchAllWhereClause(QueryBuilder $q, $filter)
    {
        return $this->addStandardCatchAllWhereClause(
            $q,
            $filter,
            [
                $this->getTableAlias().'.name',
            ]
        );
    }

    /**
     * {@inheritdoc}
     *
     * For the API
     */
    protected function addSearchCommandWhereClause(QueryBuilder $q, $filter)
    {
        return $this->addStandardSearchCommandWhereClause($q, $filter);
    }
}
