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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Types\Type;
use Mautic\CampaignBundle\Executioner\ContactFinder\Limiter\ContactLimiter;
use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\CoreBundle\Helper\Chart\ChartQuery;
use Mautic\LeadBundle\Entity\TimelineTrait;

/**
 * LeadEventLogRepository.
 */
class LeadEventLogRepository extends CommonRepository
{
    use TimelineTrait;
    use ContactLimiterTrait;
    use SlaveConnectionTrait;

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

    /**
     * @return string
     */
    public function getTableAlias()
    {
        return 'll';
    }

    /**
     * Get a lead's page event log.
     *
     * @param int|null $leadId
     * @param array    $options
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
                        ->from(MAUTIC_TABLE_PREFIX.'campaign_lead_event_log', 'll')
                        ->join('ll', MAUTIC_TABLE_PREFIX.'campaign_events', 'e', 'll.event_id = e.id')
                        ->join('ll', MAUTIC_TABLE_PREFIX.'campaigns', 'c', 'll.campaign_id = c.id')
                        ->leftJoin('ll', MAUTIC_TABLE_PREFIX.'campaign_lead_event_failed_log', 'fl', 'fl.log_id = ll.id')
                        ->andWhere('e.event_type != :eventType')
                        ->setParameter('eventType', 'decision');

        if ($leadId) {
            $query->where('ll.lead_id = '.(int) $leadId);
        }

        if (isset($options['scheduledState'])) {
            if ($options['scheduledState']) {
                // Include cancelled as well
                $query->andWhere(
                    $query->expr()->orX(
                        $query->expr()->eq('ll.is_scheduled', ':scheduled'),
                        $query->expr()->andX(
                            $query->expr()->eq('ll.is_scheduled', 0),
                            $query->expr()->isNull('ll.date_triggered', 0)
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
                $query->expr()->orX(
                    $query->expr()->like('e.name', $query->expr()->literal('%'.$options['search'].'%')),
                    $query->expr()->like('e.description', $query->expr()->literal('%'.$options['search'].'%')),
                    $query->expr()->like('c.name', $query->expr()->literal('%'.$options['search'].'%')),
                    $query->expr()->like('c.description', $query->expr()->literal('%'.$options['search'].'%'))
                )
            );
        }

        return $this->getTimelineResults($query, $options, 'e.name', 'll.date_triggered', ['metadata'], ['dateTriggered', 'triggerDate']);
    }

    /**
     * Get a lead's upcoming events.
     *
     * @param array $options
     *
     * @return array
     */
    public function getUpcomingEvents(array $options = null)
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

        if (!empty($ipIds)) {
            $query->orWhere('ll.ip_address IN ('.implode(',', $ipIds).')');
        }

        if (empty($options['canViewOthers']) && isset($this->currentUser)) {
            $query->andWhere('c.created_by = :userId')
                ->setParameter('userId', $this->currentUser->getId());
        }

        return $query->execute()->fetchAll();
    }

    /**
     * @param      $campaignId
     * @param bool $excludeScheduled
     * @param bool $excludeNegative
     * @param bool $all
     *
     * @return array
     */
    public function getCampaignLogCounts($campaignId, $excludeScheduled = false, $excludeNegative = true, $all = false)
    {
        $q = $this->getSlaveConnection()->createQueryBuilder()
                      ->from(MAUTIC_TABLE_PREFIX.'campaign_lead_event_log', 'o');

        $join = 'innerJoin';
        if ($all === true) {
            $join = 'leftJoin';
        }
        $q->$join(
                    'o',
                    MAUTIC_TABLE_PREFIX.'campaign_leads',
                    'l',
                    'l.campaign_id = '.(int) $campaignId.' and l.manually_removed = 0 and o.lead_id = l.lead_id and l.rotation = o.rotation'
                );

        $expr = $q->expr()->andX(
            $q->expr()->eq('o.campaign_id', (int) $campaignId)
        );

        $groupBy = 'o.event_id';
        if ($excludeNegative) {
            $q->select('o.event_id, count(o.lead_id) as lead_count');
            $expr->add(
                $q->expr()->orX(
                    $q->expr()->isNull('o.non_action_path_taken'),
                    $q->expr()->eq('o.non_action_path_taken', ':false')
                )
            );
        } else {
            $q->select('o.event_id, count(o.lead_id) as lead_count, o.non_action_path_taken');
            $groupBy .= ', o.non_action_path_taken';
        }

        if ($excludeScheduled) {
            $expr->add(
                $q->expr()->eq('o.is_scheduled', ':false')
            );
        }

        // Exclude failed events
        $failedSq = $this->getSlaveConnection()->createQueryBuilder();
        $failedSq->select('null')
            ->from(MAUTIC_TABLE_PREFIX.'campaign_lead_event_failed_log', 'fe')
            ->where(
                $failedSq->expr()->eq('fe.log_id', 'o.id')
            );
        $expr->add(
            sprintf('NOT EXISTS (%s)', $failedSq->getSQL())
        );

        $q->where($expr)
          ->setParameter('false', false, 'boolean')
          ->groupBy($groupBy);

        $results = $q->execute()->fetchAll();

        $return = [];

        //group by event id
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
     *
     * @param $fromLeadId
     * @param $toLeadId
     */
    public function updateLead($fromLeadId, $toLeadId)
    {
        // First check to ensure the $toLead doesn't already exist
        $results = $this->_em->getConnection()->createQueryBuilder()
            ->select('cl.event_id')
            ->from(MAUTIC_TABLE_PREFIX.'campaign_lead_event_log', 'cl')
            ->where('cl.lead_id = '.$toLeadId)
            ->execute()
            ->fetchAll();
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
            )->execute();

            // Delete remaining leads as the new lead already belongs
            $this->_em->getConnection()->createQueryBuilder()
                ->delete(MAUTIC_TABLE_PREFIX.'campaign_lead_event_log')
                ->where('lead_id = '.(int) $fromLeadId)
                ->execute();
        } else {
            $q->execute();
        }
    }

    /**
     * @param $options
     *
     * @return array
     */
    public function getChartQuery($options)
    {
        $chartQuery = new ChartQuery($this->getSlaveConnection(), $options['dateFrom'], $options['dateTo']);

        // Load points for selected period
        $query = $this->getSlaveConnection()->createQueryBuilder();
        $query->select('ll.id, ll.date_triggered')
            ->from(MAUTIC_TABLE_PREFIX.'campaign_lead_event_log', 'll')
            ->join('ll', MAUTIC_TABLE_PREFIX.'campaign_events', 'e', 'e.id = ll.event_id');

        if (isset($options['channel'])) {
            $query->andwhere("e.channel = '".$options['channel']."'");
        }

        if (isset($options['channelId'])) {
            $query->andwhere('e.channel_id = '.(int) $options['channelId']);
        }

        if (isset($options['type'])) {
            $query->andwhere("e.type = '".$options['type']."'");
        }

        if (isset($options['logChannel'])) {
            $query->andwhere("ll.channel = '".$options['logChannel']."'");
        }

        if (isset($options['logChannelId'])) {
            $query->andwhere('ll.channel_id = '.(int) $options['logChannelId']);
        }

        if (!isset($options['is_scheduled'])) {
            $query->andWhere($query->expr()->eq('ll.is_scheduled', 0));
        } else {
            $query->andWhere($query->expr()->eq('ll.is_scheduled', 1));
        }

        return $chartQuery->fetchTimeData('('.$query.')', 'date_triggered');
    }

    /**
     * @param int            $eventId
     * @param \DateTime      $now
     * @param ContactLimiter $limiter
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

        $this->getSlaveConnection($limiter);

        $q = $this->createQueryBuilder('o');

        $q->select('o, e, c')
            ->indexBy('o', 'o.id')
            ->innerJoin('o.event', 'e')
            ->innerJoin('o.campaign', 'c')
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
            ->setParameter('true', true, Type::BOOLEAN);

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
     * @param array $ids
     *
     * @return ArrayCollection
     *
     * @throws \Doctrine\ORM\Query\QueryException
     */
    public function getScheduledByIds(array $ids)
    {
        $this->getSlaveConnection();
        $q = $this->createQueryBuilder('o');

        $q->select('o, e, c')
            ->indexBy('o', 'o.id')
            ->innerJoin('o.event', 'e')
            ->innerJoin('o.campaign', 'c')
            ->where(
                $q->expr()->andX(
                    $q->expr()->in('o.id', $ids),
                    $q->expr()->eq('o.isScheduled', 1),
                    $q->expr()->eq('c.isPublished', 1)
                )
            );

        return new ArrayCollection($q->getQuery()->getResult());
    }

    /**
     * @param int            $campaignId
     * @param \DateTime      $date
     * @param ContactLimiter $limiter
     *
     * @return array
     */
    public function getScheduledCounts($campaignId, \DateTime $date, ContactLimiter $limiter)
    {
        $now = clone $date;
        $now->setTimezone(new \DateTimeZone('UTC'));

        $q = $this->getSlaveConnection($limiter)->createQueryBuilder();

        $expr = $q->expr()->andX(
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
            ->setParameter('campaignId', $campaignId)
            ->setParameter('now', $now->format('Y-m-d H:i:s'))
            ->setParameter('true', true, \PDO::PARAM_BOOL)
            ->groupBy('l.event_id')
            ->execute()
            ->fetchAll();

        $events = [];

        foreach ($results as $result) {
            $events[$result['event_id']] = (int) $result['event_count'];
        }

        return $events;
    }

    /**
     * @param       $eventId
     * @param array $contactIds
     *
     * @return array
     */
    public function getDatesExecuted($eventId, array $contactIds)
    {
        $qb = $this->getSlaveConnection()->createQueryBuilder();
        $qb->select('log.lead_id, log.date_triggered')
            ->from(MAUTIC_TABLE_PREFIX.'campaign_lead_event_log', 'log')
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->eq('log.event_id', $eventId),
                    $qb->expr()->eq('log.is_scheduled', 0),
                    $qb->expr()->in('log.lead_id', $contactIds)
                )
            );

        $results = $qb->execute()->fetchAll();

        $dates = [];
        foreach ($results as $result) {
            $dates[$result['lead_id']] = new \DateTime($result['date_triggered'], new \DateTimeZone('UTC'));
        }

        return $dates;
    }

    /**
     * @param int $contactId
     * @param int $campaignId
     * @param int $rotation
     *
     * @return bool
     */
    public function hasBeenInCampaignRotation($contactId, $campaignId, $rotation)
    {
        $qb = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $qb->select('log.rotation')
            ->from(MAUTIC_TABLE_PREFIX.'campaign_lead_event_log', 'log')
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->eq('log.lead_id', ':contactId'),
                    $qb->expr()->eq('log.campaign_id', ':campaignId'),
                    $qb->expr()->in('log.rotation', ':rotation')
                )
            )
            ->setParameter('contactId', (int) $contactId)
            ->setParameter('campaignId', (int) $campaignId)
            ->setParameter('rotation', (int) $rotation)
            ->setMaxResults(1);

        $results = $qb->execute()->fetchAll();

        return !empty($results);
    }

    /**
     * @param Lead   $campaignMember
     * @param string $message
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function unscheduleEvents(Lead $campaignMember, $message)
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
        $stmt->bindParam('dateAdded', $dateAdded, \PDO::PARAM_STR);
        $stmt->bindParam('message', $message, \PDO::PARAM_STR);
        $stmt->bindParam('contactId', $contactId, \PDO::PARAM_INT);
        $stmt->bindParam('campaignId', $campaignId, \PDO::PARAM_INT);
        $stmt->bindParam('rotation', $rotation, \PDO::PARAM_INT);
        $stmt->execute();

        // Now unschedule them
        $qb = $connection->createQueryBuilder();
        $qb->update(MAUTIC_TABLE_PREFIX.'campaign_lead_event_log')
            ->set('is_scheduled', 0)
            ->where(
                $qb->expr()->andX(
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
            ->execute();
    }

    /**
     * @deprecated 2.14 to be removed in 3.0
     *
     * @param $campaignId
     * @param $leadId
     */
    public function removeScheduledEvents($campaignId, $leadId)
    {
        $conn = $this->_em->getConnection();
        $conn->delete(MAUTIC_TABLE_PREFIX.'campaign_lead_event_log', [
            'lead_id'      => (int) $leadId,
            'campaign_id'  => (int) $campaignId,
            'is_scheduled' => 1,
        ]);
    }
}
