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

use Mautic\CoreBundle\Entity\CommonRepository;

class EventRepository extends CommonRepository
{
    /**
     * Get a list of entities.
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
     * @param $contactId
     *
     * @return array
     */
    public function getContactPendingEvents($contactId, $type)
    {
        // Limit to events that hasn't been executed or scheduled yet
        $eventQb = $this->getEntityManager()->createQueryBuilder();
        $eventQb->select('IDENTITY(log_event.event)')
            ->from(LeadEventLog::class, 'log_event')
            ->where(
                $eventQb->expr()->andX(
                    $eventQb->expr()->eq('log_event.event', 'e'),
                    $eventQb->expr()->eq('log_event.lead', 'l.lead'),
                    $eventQb->expr()->eq('log_event.rotation', 'l.rotation')
                )
            );

        // Limit to events that has no parent or whose parent has already been executed
        $parentQb = $this->getEntityManager()->createQueryBuilder();
        $parentQb->select('parent_log_event.id')
            ->from(LeadEventLog::class, 'parent_log_event')
            ->where(
                $parentQb->expr()->eq('parent_log_event.event', 'e.parent'),
                $parentQb->expr()->eq('parent_log_event.lead', 'l.lead'),
                $parentQb->expr()->eq('parent_log_event.rotation', 'l.rotation'),
                $parentQb->expr()->eq('parent_log_event.isScheduled', 0)
            );

        $q = $this->createQueryBuilder('e', 'e.id');
        $q->select('e,c')
            ->innerJoin('e.campaign', 'c')
            ->innerJoin('c.leads', 'l')
            ->where(
                $q->expr()->andX(
                    $q->expr()->eq('c.isPublished', 1),
                    $q->expr()->eq('e.type', ':type'),
                    $q->expr()->eq('IDENTITY(l.lead)', ':contactId'),
                    $q->expr()->eq('l.manuallyRemoved', 0),
                    $q->expr()->notIn('e.id', $eventQb->getDQL()),
                    $q->expr()->orX(
                        $q->expr()->isNull('e.parent'),
                        $q->expr()->exists($parentQb->getDQL())
                    )
                )
            )
            ->setParameter('type', $type)
            ->setParameter('contactId', (int) $contactId);

        return $q->getQuery()->getResult();
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

        return $q->getQuery()->getArrayResult();
    }

    /**
     * Null event parents in preparation for deleI'lting a campaign.
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

        return $q->getQuery()->getResult();
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
     * {@inheritdoc}
     *
     * For the API
     */
    protected function addCatchAllWhereClause($q, $filter)
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
    protected function addSearchCommandWhereClause($q, $filter)
    {
        return $this->addStandardSearchCommandWhereClause($q, $filter);
    }

    /**
     * Update the failed count using DBAL to avoid
     * race conditions and deadlocks.
     *
     * @return int The current value of the failed_count
     */
    public function incrementFailedCount(Event $event)
    {
        $q = $this->_em->getConnection()->createQueryBuilder();

        $q->update(MAUTIC_TABLE_PREFIX.'campaign_events')
            ->set('failed_count', 'failed_count + 1')
            ->where($q->expr()->eq('id', ':id'))
            ->setParameter('id', $event->getId());

        $q->execute();

        return $this->getFailedCount($event);
    }

    /**
     * Update the failed count using DBAL to avoid
     * race conditions and deadlocks.
     */
    public function decreaseFailedCount(Event $event): void
    {
        $q = $this->_em->getConnection()->createQueryBuilder();

        $q->update(MAUTIC_TABLE_PREFIX.'campaign_events')
            ->set('failed_count', 'failed_count - 1')
            ->where($q->expr()->eq('id', ':id'))
            ->andWhere($q->expr()->gt('failed_count', 0))
            ->setParameter('id', $event->getId());

        $q->execute();
    }

    /**
     * Get the up to date failed count
     * for the given Event.
     *
     * @return int The current value of the failed_count
     */
    public function getFailedCount(Event $event)
    {
        $q = $this->_em->getConnection()->createQueryBuilder();

        $q->select('failed_count')
            ->from(MAUTIC_TABLE_PREFIX.'campaign_events')
            ->where($q->expr()->eq('id', ':id'))
            ->setParameter(':id', $event->getId());

        return (int) $q->execute()->fetchColumn();
    }

    /**
     * Reset the failed_count's for all events
     * within the given Campaign.
     */
    public function resetFailedCountsForEventsInCampaign(Campaign $campaign)
    {
        $q = $this->_em->getConnection()->createQueryBuilder();

        $q->update(MAUTIC_TABLE_PREFIX.'campaign_events')
            ->set('failed_count', ':failedCount')
            ->where($q->expr()->eq('campaign_id', ':campaignId'))
            ->setParameter('failedCount', 0)
            ->setParameter('campaignId', $campaign->getId());

        $q->execute();
    }

    /**
     * Get the count of failed event for Lead/Event.
     */
    public function getFailedCountLeadEvent(int $leadId, int $eventId): int
    {
        /** @var LeadEventLog $log */
        $q = $this->_em->getConnection()->createQueryBuilder();
        $q->select('count(le.id)')
            ->from(MAUTIC_TABLE_PREFIX.'campaign_lead_event_log', 'le')
            ->innerJoin('le', MAUTIC_TABLE_PREFIX.'campaign_lead_event_failed_log', 'fle', 'le.id = fle.log_id')
            ->where('le.lead_id = :leadId')
            ->andWhere('le.event_id = :eventId')
            ->setParameters(['leadId' => $leadId, 'eventId' => $eventId]);

        return (int) $q->execute()->fetchColumn();
    }
}
