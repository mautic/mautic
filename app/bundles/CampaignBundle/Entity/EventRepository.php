<?php

namespace Mautic\CampaignBundle\Entity;

use Doctrine\Common\Collections\Order;
use Doctrine\DBAL\ArrayParameterType;
use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * @extends CommonRepository<Event>
 */
class EventRepository extends CommonRepository
{
    /**
     * Get a list of entities.
     *
     * @param mixed[] $args
     *
     * @return \Doctrine\ORM\Tools\Pagination\Paginator<object>|object[]|mixed[]
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
     * @param int    $contactId
     * @param string $type
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
                    $q->expr()->orX(
                        $q->expr()->isNull('c.publishUp'),
                        $q->expr()->lt('c.publishUp', 'CURRENT_TIMESTAMP()'),
                    ),
                    $q->expr()->orX(
                        $q->expr()->isNull('c.publishDown'),
                        $q->expr()->gt('c.publishDown', 'CURRENT_TIMESTAMP()'),
                    ),
                    $q->expr()->isNull('c.deleted'),
                    $q->expr()->eq('e.type', ':type'),
                    $q->expr()->isNull('e.deleted'),
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
     * @param int         $parentId
     * @param string|null $decisionPath
     * @param string|null $eventType
     *
     * @return array
     */
    public function getEventsByParent($parentId, $decisionPath = null, $eventType = null)
    {
        $q = $this->getEntityManager()->createQueryBuilder();

        $q->select('e')
            ->from(Event::class, 'e', 'e.id')
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
     * @param int  $campaignId
     * @param bool $ignoreDeleted
     *
     * @return array<int,mixed[]>
     */
    public function getCampaignEvents($campaignId, $ignoreDeleted = true): array
    {
        $q = $this->getEntityManager()->createQueryBuilder();
        $q->select('e, IDENTITY(e.parent)')
            ->from(Event::class, 'e', 'e.id')
            ->where(
                $q->expr()->eq('IDENTITY(e.campaign)', (int) $campaignId)
            )
            ->orderBy('e.order', Order::Ascending->value);

        if ($ignoreDeleted) {
            $q->andWhere($q->expr()->isNull('e.deleted'));
        }

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
     * @return string[]
     */
    public function getCampaignEventIds(int $campaignId): array
    {
        $q = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $q->select('e.id')
            ->from(MAUTIC_TABLE_PREFIX.Event::TABLE_NAME, 'e')
            ->where($q->expr()->eq('e.campaign_id', $campaignId));

        return array_column($q->executeQuery()->fetchAllAssociative(), 'id');
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
     * @param int $campaignId
     */
    public function nullEventParents($campaignId): void
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
     * @param string[] $events
     */
    public function nullEventRelationships($events): void
    {
        $qb = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $qb->update(MAUTIC_TABLE_PREFIX.'campaign_events')
            ->set('parent_id', ':null')
            ->setParameter('null', null)
            ->where(
                $qb->expr()->in('parent_id', $events)
            )
            ->executeStatement();
    }

    /**
     * @param string[] $eventIds
     */
    public function deleteEvents(array $eventIds): void
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->delete(Event::class, 'e')
            ->where($qb->expr()->in('e.id', ':event_ids'))
            ->setParameter('event_ids', $eventIds, ArrayParameterType::INTEGER)
            ->getQuery()
            ->execute();
    }

    /**
     * @param string[] $eventIds
     */
    public function setEventsAsDeleted(array $eventIds): void
    {
        $dateTime = (new \DateTime())->format('Y-m-d H:i:s');
        $qb       = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $qb->update(MAUTIC_TABLE_PREFIX.Event::TABLE_NAME)
            ->set('deleted', ':deleted')
            ->setParameter('deleted', $dateTime)
            ->where(
                $qb->expr()->in('id', $eventIds)
            )
            ->executeStatement();
    }

    public function getTableAlias(): string
    {
        return 'e';
    }

    /**
     * For the API.
     *
     * @return string[]
     */
    public function getSearchCommands(): array
    {
        return $this->getStandardSearchCommands();
    }

    /**
     * Get an array of events that have been triggered by this lead.
     */
    public function getLeadTriggeredEvents($leadId): array
    {
        $q = $this->getEntityManager()->createQueryBuilder()
            ->select('e, c, l')
            ->from(Event::class, 'e')
            ->join('e.campaign', 'c')
            ->join('e.log', 'l');

        // make sure the published up and down dates are good
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
    protected function addCatchAllWhereClause($q, $filter): array
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
    protected function addSearchCommandWhereClause($q, $filter): array
    {
        return $this->addStandardSearchCommandWhereClause($q, $filter);
    }

    /**
     * Update the failed count using DBAL to avoid
     * race conditions and deadlocks.
     */
    public function incrementFailedCount(Event $event): int
    {
        $q = $this->_em->getConnection()->createQueryBuilder();

        $q->update(MAUTIC_TABLE_PREFIX.'campaign_events')
            ->set('failed_count', 'failed_count + 1')
            ->where($q->expr()->eq('id', ':id'))
            ->setParameter('id', $event->getId());

        $q->executeStatement();

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

        $q->executeStatement();
    }

    /**
     * Get the up to date failed count
     * for the given Event.
     */
    public function getFailedCount(Event $event): int
    {
        $q = $this->_em->getConnection()->createQueryBuilder();

        $q->select('failed_count')
            ->from(MAUTIC_TABLE_PREFIX.'campaign_events')
            ->where($q->expr()->eq('id', ':id'))
            ->setParameter('id', $event->getId());

        return (int) $q->executeQuery()->fetchOne();
    }

    /**
     * Reset the failed_count's for all events
     * within the given Campaign.
     */
    public function resetFailedCountsForEventsInCampaign(Campaign $campaign): void
    {
        $q = $this->_em->getConnection()->createQueryBuilder();

        $q->update(MAUTIC_TABLE_PREFIX.'campaign_events')
            ->set('failed_count', ':failedCount')
            ->where($q->expr()->eq('campaign_id', ':campaignId'))
            ->setParameter('failedCount', 0)
            ->setParameter('campaignId', $campaign->getId());

        $q->executeStatement();
    }

    /**
     * Get the count of failed event for Lead/Event.
     */
    public function getFailedCountLeadEvent(int $leadId, int $eventId): int
    {
        $q = $this->_em->getConnection()->createQueryBuilder();
        $q->select('count(le.id)')
            ->from(MAUTIC_TABLE_PREFIX.'campaign_lead_event_log', 'le')
            ->innerJoin('le', MAUTIC_TABLE_PREFIX.'campaign_lead_event_failed_log', 'fle', 'le.id = fle.log_id')
            ->where('le.lead_id = :leadId')
            ->andWhere('le.event_id = :eventId')
            ->setParameters(['leadId' => $leadId, 'eventId' => $eventId]);

        return (int) $q->executeQuery()->fetchOne();
    }
}
