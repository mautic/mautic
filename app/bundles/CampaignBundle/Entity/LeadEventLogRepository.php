<?php

namespace Mautic\CampaignBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Cache\QueryCacheProfile;
use Doctrine\DBAL\Types\Types;
use Mautic\CampaignBundle\Executioner\ContactFinder\Limiter\ContactLimiter;
use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\CoreBundle\Helper\Chart\ChartQuery;
use Mautic\LeadBundle\Entity\TimelineTrait;

/**
 * @extends CommonRepository<LeadEventLog>
 */
class LeadEventLogRepository extends CommonRepository
{
    use TimelineTrait;
    use ContactLimiterTrait;
    use ReplicaConnectionTrait;
    public const LOG_DELETE_BATCH_SIZE = 5000;

    public function getEntities(array $args = [])
    {
        $alias = $this->getTableAlias();
        $q     = $this
            ->createQueryBuilder($alias)
            ->join($alias.'.ipAddress', 'i');

        if (empty($args['campaign_id'])) {
            $q->join($alias.'.event', 'e')
                ->join($alias.'.campaign', 'c');
        } else {
            $q->andWhere(
                $q->expr()->eq('IDENTITY('.$this->getTableAlias().'.campaign)', (int) $args['campaign_id'])
            );
        }

        if (!empty($args['contact_id'])) {
            $q->andWhere(
                $q->expr()->eq('IDENTITY('.$this->getTableAlias().'.lead)', (int) $args['contact_id'])
            );
        }

        $args['qb'] = $q;

        return parent::getEntities($args);
    }

    public function getTableAlias(): string
    {
        return 'll';
    }

    /**
     * Get a lead's page event log.
     *
     * @param int|null $leadId
     *
     * @return array
     */
    public function getLeadLogs($leadId = null, array $options = [])
    {
        $query = $this->getEntityManager()
                      ->getConnection()
                      ->createQueryBuilder()
                      ->select('ll.id as log_id,
                    ll.event_id,
                    ll.campaign_id,
                    ll.date_triggered as dateTriggered,
                    e.name AS event_name,
                    e.description AS event_description,
                    e.parent_id AS parent_id,
                    e.decision_path AS decision_path,
                    c.name AS campaign_name,
                    c.description AS campaign_description,
                    ll.metadata,
                    e.type,
                    ll.is_scheduled as isScheduled,
                    ll.trigger_date as triggerDate,
                    ll.channel,
                    ll.channel_id as channel_id,
                    ll.lead_id,
                    fl.reason as fail_reason
                    '
                      )
                        ->add('from', [
                            'table' => MAUTIC_TABLE_PREFIX.'campaign_lead_event_log',
                            'alias' => 'll',
                            'hint'  => 'USE INDEX ('.MAUTIC_TABLE_PREFIX.'campaign_date_triggered)',
                        ], true)
                        ->join('ll', MAUTIC_TABLE_PREFIX.'campaign_events', 'e', 'll.event_id = e.id')
                        ->join('ll', MAUTIC_TABLE_PREFIX.'campaigns', 'c', 'll.campaign_id = c.id')
                        ->leftJoin('ll', MAUTIC_TABLE_PREFIX.'campaign_lead_event_failed_log', 'fl', 'fl.log_id = ll.id')
                        ->andWhere('e.event_type != :eventType')
                        ->setParameter('eventType', 'decision');

        if ($leadId) {
            $query->where('ll.lead_id = :leadId')
                ->setParameter('leadId', $leadId);
        }

        if (isset($options['scheduledState'])) {
            if ($options['scheduledState']) {
                // Include cancelled as well
                $query->andWhere(
                    $query->expr()->or(
                        $query->expr()->eq('ll.is_scheduled', ':scheduled'),
                        $query->expr()->and(
                            $query->expr()->eq('ll.is_scheduled', 0),
                            $query->expr()->isNull('ll.date_triggered')
                        )
                    )
                );
            } else {
                $query->andWhere(
                    $query->expr()->eq('ll.is_scheduled', ':scheduled')
                );
            }
            $query->setParameter('scheduled', $options['scheduledState'], 'boolean');
        }

        if (isset($options['search']) && $options['search']) {
            $query->andWhere(
                $query->expr()->or(
                    $query->expr()->like('e.name', ':search'),
                    $query->expr()->like('e.description', ':search'),
                    $query->expr()->like('c.name', ':search'),
                    $query->expr()->like('c.description', ':search')
                )
            )->setParameter('search', '%'.$options['search'].'%');
        }

        return $this->getTimelineResults($query, $options, 'e.name', 'll.date_triggered', ['metadata'], ['dateTriggered', 'triggerDate'], null, 'll.id');
    }

    /**
     * Get a lead's upcoming events.
     */
    public function getUpcomingEvents(array $options = null): array
    {
        $leadIps = [];

        $query = $this->_em->getConnection()->createQueryBuilder();
        $query->from(MAUTIC_TABLE_PREFIX.'campaign_lead_event_log', 'll')
            ->select('ll.event_id,
                    ll.campaign_id,
                    ll.trigger_date,
                    ll.lead_id,
                    e.name AS event_name,
                    e.description AS event_description,
                    c.name AS campaign_name,
                    c.description AS campaign_description,
                    ll.metadata,
                    CONCAT(CONCAT(l.firstname, \' \'), l.lastname) AS lead_name')
            ->leftJoin('ll', MAUTIC_TABLE_PREFIX.'campaign_events', 'e', 'e.id = ll.event_id')
            ->leftJoin('ll', MAUTIC_TABLE_PREFIX.'campaigns', 'c', 'c.id = e.campaign_id')
            ->leftJoin('ll', MAUTIC_TABLE_PREFIX.'leads', 'l', 'l.id = ll.lead_id')
            ->where($query->expr()->eq('ll.is_scheduled', 1));

        if (isset($options['lead'])) {
            /** @var \Mautic\CoreBundle\Entity\IpAddress $ip */
            foreach ($options['lead']->getIpAddresses() as $ip) {
                $leadIps[] = $ip->getId();
            }

            $query->andWhere('ll.lead_id = :leadId')
                ->setParameter('leadId', $options['lead']->getId());
        }

        if (isset($options['type'])) {
            $query->andwhere('e.type = :type')
                  ->setParameter('type', $options['type']);
        }

        if (isset($options['eventType'])) {
            if (is_array($options['eventType'])) {
                $query->andWhere(
                    $query->expr()->in('e.event_type', array_map([$query->expr(), 'literal'], $options['eventType']))
                );
            } else {
                $query->andwhere('e.event_type = :eventTypes')
                    ->setParameter('eventTypes', $options['eventType']);
            }
        }

        if (isset($options['limit'])) {
            $query->setMaxResults($options['limit']);
        } else {
            $query->setMaxResults(10);
        }

        $query->orderBy('ll.trigger_date');

        if (empty($options['canViewOthers']) && isset($this->currentUser)) {
            $query->andWhere('c.created_by = :userId')
                ->setParameter('userId', $this->currentUser->getId());
        }

        return $query->executeQuery()->fetchAllAssociative();
    }

    /**
     * @param int  $campaignId
     * @param bool $excludeScheduled
     * @param bool $excludeNegative
     * @param bool $all
     *
     * @throws \Doctrine\DBAL\Cache\CacheException
     */
    public function getCampaignLogCounts(
        $campaignId,
        $excludeScheduled = false,
        $excludeNegative = true,
        $all = false,
        \DateTimeInterface $dateFrom = null,
        \DateTimeInterface $dateTo = null,
        int $eventId = null,
    ): array {
        $join = $all ? 'leftJoin' : 'innerJoin';

        $q = $this->_em->getConnection()->createQueryBuilder();
        $q->from(MAUTIC_TABLE_PREFIX.'campaign_lead_event_log', 'o');
        $q->$join(
            'o',
            MAUTIC_TABLE_PREFIX.'campaign_leads',
            'l',
            'l.campaign_id = '.(int) $campaignId.' and o.lead_id = l.lead_id'
        );

        $expr = $q->expr()->and(
            $q->expr()->eq('o.campaign_id', (int) $campaignId)
        );

        if ($eventId) {
            $expr = $expr->with(
                $q->expr()->eq('o.event_id', $eventId)
            );
        }

        $groupBy = 'o.event_id';
        if ($excludeNegative) {
            $q->select('o.event_id, count(o.lead_id) as lead_count');
            $expr = $expr->with(
                $q->expr()->or(
                    $q->expr()->isNull('o.non_action_path_taken'),
                    $q->expr()->eq('o.non_action_path_taken', ':false')
                )
            );
        } else {
            $q->select('o.event_id, count(o.lead_id) as lead_count, o.non_action_path_taken');
            $groupBy .= ', o.non_action_path_taken';
        }

        if ($excludeScheduled) {
            $expr = $expr->with(
                $q->expr()->eq('o.is_scheduled', ':false')
            );
        }

        // Exclude failed events
        $failedSq = $this->getReplicaConnection()->createQueryBuilder();
        $failedSq->select('null')
            ->from(MAUTIC_TABLE_PREFIX.'campaign_lead_event_failed_log', 'fe')
            ->where(
                $failedSq->expr()->eq('fe.log_id', 'o.id')
            );
        if ($dateFrom && $dateTo) {
            $failedSq->andWhere('fe.date_added BETWEEN FROM_UNIXTIME(:dateFrom) AND FROM_UNIXTIME(:dateTo)')
                ->setParameter('dateFrom', $dateFrom->getTimestamp(), \PDO::PARAM_INT)
                ->setParameter('dateTo', $dateTo->getTimestamp(), \PDO::PARAM_INT);
        }
        $expr = $expr->with(
            sprintf('NOT EXISTS (%s)', $failedSq->getSQL())
        );

        $q->where($expr)
          ->setParameter('false', false, 'boolean')
          ->groupBy($groupBy);

        if ($dateFrom && $dateTo) {
            $q->andWhere('o.date_triggered BETWEEN FROM_UNIXTIME(:dateFrom) AND FROM_UNIXTIME(:dateTo)')
                ->setParameter('dateFrom', $dateFrom->getTimestamp(), \PDO::PARAM_INT)
                ->setParameter('dateTo', $dateTo->getTimestamp(), \PDO::PARAM_INT);
        }

        if ($this->_em->getConnection()->getConfiguration()->getResultCache()) {
            $results = $this->_em->getConnection()->executeCacheQuery(
                $q->getSQL(),
                $q->getParameters(),
                $q->getParameterTypes(),
                new QueryCacheProfile(600)
            )->fetchAllAssociative();
        } else {
            $results = $q->executeQuery()->fetchAllAssociative();
        }

        $return = [];

        // group by event id
        foreach ($results as $l) {
            if (!$excludeNegative) {
                if (!isset($return[$l['event_id']])) {
                    $return[$l['event_id']] = [
                        0 => 0,
                        1 => 0,
                    ];
                }

                $key                          = (int) $l['non_action_path_taken'] ? 0 : 1;
                $return[$l['event_id']][$key] = (int) $l['lead_count'];
            } else {
                $return[$l['event_id']] = (int) $l['lead_count'];
            }
        }

        return $return;
    }

    /**
     * Updates lead ID (e.g. after a lead merge).
     */
    public function updateLead($fromLeadId, $toLeadId): void
    {
        // First check to ensure the $toLead doesn't already exist
        $results = $this->_em->getConnection()->createQueryBuilder()
            ->select('cl.event_id')
            ->from(MAUTIC_TABLE_PREFIX.'campaign_lead_event_log', 'cl')
            ->where('cl.lead_id = '.$toLeadId)
            ->executeQuery()
            ->fetchAllAssociative();
        $exists = [];
        foreach ($results as $r) {
            $exists[] = $r['event_id'];
        }

        $q = $this->_em->getConnection()->createQueryBuilder();
        $q->update(MAUTIC_TABLE_PREFIX.'campaign_lead_event_log')
            ->set('lead_id', (int) $toLeadId)
            ->where('lead_id = '.(int) $fromLeadId);

        if (!empty($exists)) {
            $q->andWhere(
                $q->expr()->notIn('event_id', $exists)
            )->executeStatement();

            // Delete remaining leads as the new lead already belongs
            $this->_em->getConnection()->createQueryBuilder()
                ->delete(MAUTIC_TABLE_PREFIX.'campaign_lead_event_log')
                ->where('lead_id = '.(int) $fromLeadId)
                ->executeStatement();
        } else {
            $q->executeStatement();
        }
    }

    public function getChartQuery($options): array
    {
        $chartQuery = new ChartQuery($this->getReplicaConnection(), $options['dateFrom'], $options['dateTo']);

        // Load points for selected period
        $query = $this->getReplicaConnection()->createQueryBuilder();
        $query->select('ll.id, ll.date_triggered')
            ->from(MAUTIC_TABLE_PREFIX.'campaign_lead_event_log', 'll')
            ->join('ll', MAUTIC_TABLE_PREFIX.'campaign_events', 'e', 'e.id = ll.event_id');

        if (isset($options['channel'])) {
            $query->andWhere('e.channel = '.$query->expr()->literal($options['channel']));
        }

        if (isset($options['channelId'])) {
            $query->andWhere('e.channel_id = '.(int) $options['channelId']);
        }

        if (isset($options['type'])) {
            $query->andWhere('e.type = '.$query->expr()->literal($options['type']));
        }

        if (isset($options['logChannel'])) {
            $query->andWhere('ll.channel = '.$query->expr()->literal($options['logChannel']));
        }

        if (isset($options['logChannelId'])) {
            $query->andWhere('ll.channel_id = '.(int) $options['logChannelId']);
        }

        $isScheduled = isset($options['is_scheduled']) ? 1 : 0;
        $query->andWhere('ll.is_scheduled = '.$isScheduled);

        return $chartQuery->fetchTimeData('('.$query.')', 'date_triggered');
    }

    /**
     * @param int $eventId
     *
     * @return ArrayCollection
     *
     * @throws \Doctrine\ORM\Query\QueryException
     */
    public function getScheduled($eventId, \DateTime $now, ContactLimiter $limiter)
    {
        if ($limiter->hasCampaignLimit() && 0 === $limiter->getCampaignLimitRemaining()) {
            return new ArrayCollection();
        }

        $this->getReplicaConnection($limiter);

        $q = $this->createQueryBuilder('o');

        $q->select('o, e, c')
            ->indexBy('o', 'o.id')
            ->innerJoin('o.event', 'e')
            ->innerJoin('e.campaign', 'c')
            ->where(
                $q->expr()->andX(
                    $q->expr()->eq('IDENTITY(o.event)', ':eventId'),
                    $q->expr()->eq('o.isScheduled', ':true'),
                    $q->expr()->lte('o.triggerDate', ':now'),
                    $q->expr()->eq('c.isPublished', 1)
                )
            )
            ->setParameter('eventId', (int) $eventId)
            ->setParameter('now', $now)
            ->setParameter('true', true, Types::BOOLEAN);

        $this->updateOrmQueryFromContactLimiter('o', $q, $limiter);

        if ($limiter->hasCampaignLimit() && $limiter->getCampaignLimitRemaining() < $limiter->getBatchLimit()) {
            $q->setMaxResults($limiter->getCampaignLimitRemaining());
        }

        $result = new ArrayCollection($q->getQuery()->getResult());

        if ($limiter->hasCampaignLimit()) {
            $limiter->reduceCampaignLimitRemaining($result->count());
        }

        return $result;
    }

    /**
     * @throws \Doctrine\ORM\Query\QueryException
     */
    public function getScheduledByIds(array $ids): ArrayCollection
    {
        $this->getReplicaConnection();
        $q = $this->createQueryBuilder('o');

        $q->select('o, e, c')
            ->indexBy('o', 'o.id')
            ->innerJoin('o.event', 'e')
            ->innerJoin('e.campaign', 'c')
            ->where(
                $q->expr()->andX(
                    $q->expr()->in('o.id', $ids),
                    $q->expr()->eq('o.isScheduled', 1),
                    $q->expr()->eq('c.isPublished', 1),
                    $q->expr()->isNull('c.deleted'),
                    $q->expr()->isNull('e.deleted')
                )
            );

        return new ArrayCollection($q->getQuery()->getResult());
    }

    /**
     * @param int $campaignId
     */
    public function getScheduledCounts($campaignId, \DateTime $date, ContactLimiter $limiter): array
    {
        $now = clone $date;
        $now->setTimezone(new \DateTimeZone('UTC'));

        $q = $this->getReplicaConnection($limiter)->createQueryBuilder();

        $expr = $q->expr()->and(
            $q->expr()->eq('l.campaign_id', ':campaignId'),
            $q->expr()->eq('l.is_scheduled', ':true'),
            $q->expr()->lte('l.trigger_date', ':now'),
            $q->expr()->eq('c.is_published', 1)
        );

        $this->updateQueryFromContactLimiter('l', $q, $limiter, true);

        $results = $q->select('COUNT(*) as event_count, l.event_id')
            ->from(MAUTIC_TABLE_PREFIX.'campaign_lead_event_log', 'l')
            ->join('l', MAUTIC_TABLE_PREFIX.'campaigns', 'c', 'l.campaign_id = c.id')
            ->where($expr)
            ->setParameter('campaignId', (int) $campaignId)
            ->setParameter('now', $now->format('Y-m-d H:i:s'))
            ->setParameter('true', true, \PDO::PARAM_BOOL)
            ->groupBy('l.event_id')
            ->executeQuery()
            ->fetchAllAssociative();

        $events = [];

        foreach ($results as $result) {
            $events[$result['event_id']] = (int) $result['event_count'];
        }

        return $events;
    }

    public function getDatesExecuted($eventId, array $contactIds): array
    {
        $qb = $this->getReplicaConnection()->createQueryBuilder();
        $qb->select('log.lead_id, log.date_triggered, log.is_scheduled')
            ->from(MAUTIC_TABLE_PREFIX.'campaign_lead_event_log', 'log')
            ->where(
                $qb->expr()->and(
                    $qb->expr()->eq('log.event_id', $eventId),
                    $qb->expr()->in('log.lead_id', $contactIds)
                )
            );

        $results = $qb->executeQuery()->fetchAllAssociative();

        $dates = [];
        foreach ($results as $result) {
            $dates[$result['lead_id']] = new \DateTime($result['date_triggered'], new \DateTimeZone('UTC'));
            if (1 === (int) $result['is_scheduled']) {
                unset($dates[$result['lead_id']]);
            }
        }

        return $dates;
    }

    public function getOldestTriggeredDate(): ?\DateTime
    {
        $qb = $this->getReplicaConnection()->createQueryBuilder();
        $qb->select('log.date_triggered')
            ->from(MAUTIC_TABLE_PREFIX.'campaign_lead_event_log', 'log')
            ->orderBy('log.date_triggered', 'ASC')
            ->setMaxResults(1);

        $results = $qb->executeQuery()->fetchAllAssociative();

        return isset($results[0]['date_triggered']) ? new \DateTime($results[0]['date_triggered']) : null;
    }

    /**
     * @param int $contactId
     * @param int $campaignId
     * @param int $rotation
     */
    public function hasBeenInCampaignRotation($contactId, $campaignId, $rotation): bool
    {
        $qb = $this->getReplicaConnection()->createQueryBuilder();
        $qb->select('log.rotation')
            ->from(MAUTIC_TABLE_PREFIX.'campaign_lead_event_log', 'log')
            ->where(
                $qb->expr()->and(
                    $qb->expr()->eq('log.lead_id', ':contactId'),
                    $qb->expr()->eq('log.campaign_id', ':campaignId'),
                    $qb->expr()->in('log.rotation', ':rotation')
                )
            )
            ->setParameter('contactId', (int) $contactId)
            ->setParameter('campaignId', (int) $campaignId)
            ->setParameter('rotation', (int) $rotation)
            ->setMaxResults(1);

        $results = $qb->executeQuery()->fetchAllAssociative();

        return !empty($results);
    }

    /**
     * @param string $message
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function unscheduleEvents(Lead $campaignMember, $message): void
    {
        $contactId  = $campaignMember->getLead()->getId();
        $campaignId = $campaignMember->getCampaign()->getId();
        $rotation   = $campaignMember->getRotation();
        $dateAdded  = (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s');
        // Insert entries into the failed log so it's known why they were never executed
        $prefix = MAUTIC_TABLE_PREFIX;
        $sql    = <<<SQL
REPLACE INTO {$prefix}campaign_lead_event_failed_log( `log_id`, `date_added`, `reason`)
SELECT id, :dateAdded as date_added, :message as reason from {$prefix}campaign_lead_event_log
WHERE is_scheduled = 1 AND lead_id = :contactId AND campaign_id = :campaignId AND rotation = :rotation
SQL;

        $connection = $this->getEntityManager()->getConnection();
        $stmt       = $connection->prepare($sql);
        $stmt->bindValue('dateAdded', $dateAdded, \PDO::PARAM_STR);
        $stmt->bindValue('message', $message, \PDO::PARAM_STR);
        $stmt->bindValue('contactId', $contactId, \PDO::PARAM_INT);
        $stmt->bindValue('campaignId', $campaignId, \PDO::PARAM_INT);
        $stmt->bindValue('rotation', $rotation, \PDO::PARAM_INT);
        $stmt->executeStatement();

        // Now unschedule them
        $qb = $connection->createQueryBuilder();
        $qb->update(MAUTIC_TABLE_PREFIX.'campaign_lead_event_log')
            ->set('is_scheduled', 0)
            ->where(
                $qb->expr()->and(
                    $qb->expr()->eq('is_scheduled', 1),
                    $qb->expr()->eq('lead_id', ':contactId'),
                    $qb->expr()->eq('campaign_id', ':campaignId'),
                    $qb->expr()->eq('rotation', ':rotation')
                )
            )
            ->setParameters(
                [
                    'contactId'     => (int) $contactId,
                    'campaignId'    => (int) $campaignId,
                    'rotation'      => (int) $rotation,
                ]
            )
            ->executeStatement();
    }

    public function removeEventLogsByCampaignId(int $campaignId): void
    {
        $table_name    = $this->getTableName();
        $sql           = "DELETE FROM {$table_name} WHERE campaign_id = (?) LIMIT ".self::LOG_DELETE_BATCH_SIZE;
        $conn          = $this->getEntityManager()->getConnection();
        $deleteEntries = true;
        while ($deleteEntries) {
            $deleteEntries = $conn->executeStatement($sql, [$campaignId], [Types::INTEGER]);
        }
    }

    /**
     * @param string[] $eventIds
     */
    public function removeEventLogs(array $eventIds): void
    {
        $table_name    = $this->getTableName();
        $sql           = "DELETE FROM {$table_name} WHERE event_id IN (?) ORDER BY event_id ASC LIMIT ".self::LOG_DELETE_BATCH_SIZE;
        $conn          = $this->getEntityManager()->getConnection();
        $deleteEntries = true;
        while ($deleteEntries) {
            $deleteEntries = $conn->executeStatement($sql, [$eventIds], [ArrayParameterType::INTEGER]);
        }
    }

    /**
     * Check if last lead/event failed.
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function isLastFailed(int $leadId, int $eventId): bool
    {
        /** @var LeadEventLog $log */
        $log = $this->findOneBy(['lead' => $leadId, 'event' => $eventId], ['dateTriggered' => 'DESC']);

        if (null !== $log && null !== $log->getFailedLog()) {
            return true;
        }

        return false;
    }
}
