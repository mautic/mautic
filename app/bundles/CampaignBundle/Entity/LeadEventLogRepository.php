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
use Mautic\CoreBundle\Helper\Chart\ChartQuery;
use Mautic\LeadBundle\Entity\TimelineTrait;

/**
 * LeadEventLogRepository.
 */
class LeadEventLogRepository extends CommonRepository
{
    use TimelineTrait;

    public function getEntities($args = [])
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
     * @param int   $leadId
     * @param array $options
     *
     * @return array
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getLeadLogs($leadId, array $options = [])
    {
        $query = $this->getEntityManager()
                      ->getConnection()
                      ->createQueryBuilder()
                      ->select('ll.event_id,
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
                    ll.channel_id as channel_id
                    '
                      )
                      ->from(MAUTIC_TABLE_PREFIX.'campaign_lead_event_log', 'll')
                      ->leftJoin('ll', MAUTIC_TABLE_PREFIX.'campaign_events', 'e', 'll.event_id = e.id')
                      ->leftJoin('ll', MAUTIC_TABLE_PREFIX.'campaigns', 'c', 'll.campaign_id = c.id')
                      ->where('ll.lead_id = '.(int) $leadId)
                      ->andWhere('e.event_type != :eventType')
                      ->setParameter('eventType', 'decision');

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
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
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

        if (!empty($options['canViewOthers']) && isset($this->currentUser)) {
            $query->andWhere('c.created_by = :userId')
                ->setParameter('userId', $this->currentUser->getId());
        }

        return $query->execute()->fetchAll();
    }

    /**
     * @param      $campaignId
     * @param bool $excludeScheduled
     *
     * @return array
     */
    public function getCampaignLogCounts($campaignId, $excludeScheduled = false)
    {
        $q = $this->_em->getConnection()->createQueryBuilder()
                       ->select('o.event_id, count(o.lead_id) as lead_count')
                       ->from(MAUTIC_TABLE_PREFIX.'campaign_lead_event_log', 'o')
                       ->innerJoin(
                           'o',
                           MAUTIC_TABLE_PREFIX.'campaign_leads',
                           'l',
                           'l.campaign_id = '.(int) $campaignId.' and l.manually_removed = 0 and o.lead_id = l.lead_id and l.rotation = o.rotation'
                       );

        $expr = $q->expr()->andX(
            $q->expr()->eq('o.campaign_id', (int) $campaignId),
            $q->expr()->orX(
                $q->expr()->isNull('o.non_action_path_taken'),
                $q->expr()->eq('o.non_action_path_taken', ':false')
            )
        );

        if ($excludeScheduled) {
            $expr->add(
                $q->expr()->eq('o.is_scheduled', ':false')
            );
        }

        // Exclude failed events
        $failedSq = $this->_em->getConnection()->createQueryBuilder();
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
          ->groupBy('o.event_id');

        $results = $q->execute()->fetchAll();

        $return = [];

        //group by event id
        foreach ($results as $l) {
            $return[$l['event_id']] = $l['lead_count'];
        }

        return $return;
    }

    /**
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
        $chartQuery = new ChartQuery($this->getEntityManager()->getConnection(), $options['dateFrom'], $options['dateTo']);

        // Load points for selected period
        $query = $this->_em->getConnection()->createQueryBuilder();
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
}
