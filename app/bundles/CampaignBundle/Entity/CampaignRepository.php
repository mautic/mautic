<?php

namespace Mautic\CampaignBundle\Entity;

use Doctrine\DBAL\Cache\QueryCacheProfile;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Mautic\CampaignBundle\Entity\Result\CountResult;
use Mautic\CampaignBundle\Executioner\ContactFinder\Limiter\ContactLimiter;
use Mautic\CoreBundle\Helper\Chart\ChartQuery;
use Mautic\LeadBundle\Entity\TimelineTrait;

class CampaignRepository extends EntityRepository
{
    use TimelineTrait;

    /**
     * @param string|null $search
     * @param int         $page
     */
    public function getCampaignsBySource($source, $sourceId, $search = null, $limit = 10, $page = 1, array $options = [])
    {
        $q = $this->getEntityManager()->createQueryBuilder();
        $q->select('c, s, partial l.{id}')
            ->from('MauticCampaignBundle:Campaign', 'c')
            ->join('c.sources', 's')
            ->leftJoin('c.leads', 'l');

        $q->where(
            $q->expr()->andX(
                $q->expr()->eq('IDENTITY(s.source)', ':source'),
                $q->expr()->eq('s.sourceId', ':sourceId')
            )
        )
            ->setParameter('source', $source)
            ->setParameter('sourceId', $sourceId);

        if (!empty($search)) {
            $q->andWhere($q->expr()->like('c.name', ':search'))
                ->setParameter('search', "%{$search}%");
        }

        $q->orderBy('c.dateAdded', 'DESC');

        if (isset($options['canViewOthers']) && !$options['canViewOthers']) {
            $q->andWhere('c.createdBy = :id')
                ->setParameter('id', $options['createdBy']);
        }

        $query = $q->getQuery();

        if ($limit) {
            $query->setFirstResult(($page - 1) * $limit)
                ->setMaxResults($limit);
        }

        return new Paginator($query);
    }

    /**
     * Get entities for a specific lead.
     *
     * @param int $leadId
     */
    public function getEntitiesByLead($leadId = null, array $options = [])
    {
        $sb = $this->getEntityManager()->createQueryBuilder();
        $sb->select('cl.campaignId')
            ->from('MauticCampaignBundle:Lead', 'cl');

        if ($leadId) {
            $sb->where(
                $sb->expr()->eq('cl.lead', $leadId)
            );
        }

        $q = $this->getEntityManager()->createQueryBuilder();
        $q->select('c, s, partial l.{id}')
            ->from('MauticCampaignBundle:Campaign', 'c')
            ->leftJoin('c.sources', 's')
            ->leftJoin('c.leads', 'l')
            ->where(
                $q->expr()->in(
                    'c.id',
                    $sb->getDQL()
                )
            );

        if (isset($options['canViewOthers']) && !$options['canViewOthers']) {
            $q->andWhere('c.createdBy = :id')
                ->setParameter('id', $options['createdBy']);
        }

        $q->orderBy('c.dateAdded', 'DESC');

        if (isset($options['scheduledState'])) {
            if ('scheduled' == $options['scheduledState']) {
                // Only scheduled
                $q->andWhere('c.publishUp IS NOT NULL');
            } elseif ('published' == $options['scheduledState']) {
                // Only published
                $q->andWhere(
                    $q->expr()->andX(
                        $q->expr()->isNull('c.publishUp'),
                        $q->expr()->isNull('c.publishDown')
                    )
                );
            } elseif ('unpublished' == $options['scheduledState']) {
                // Only unpublished
                $q->andWhere(
                    $q->expr()->andX(
                        $q->expr()->isNull('c.publishUp'),
                        $q->expr()->isNotNull('c.publishDown')
                    )
                );
            } elseif ('expired' == $options['scheduledState']) {
                // Only expired
                $q->andWhere(
                    $q->expr()->andX(
                        $q->expr()->isNotNull('c.publishUp'),
                        $q->expr()->isNotNull('c.publishDown')
                    )
                );
            }
        }

        return $q->getQuery()->getResult();
    }

    /**
     * Get entities for specific source lists.
     *
     * @param string    $sourceType
     * @param bool      $isPublic
     * @param bool|null $isPublished
     * @param bool|null $isScheduled      true = is scheduled, false = is not scheduled, null = all
     * @param bool      $excludeScheduled For public/published lists, excludes scheduled
     * @param array     $alphabetFilter   Filter by name starting with ex. ['a', 'c']
     */
    public function getEntitiesBySources(
        $sourceType,
        array $sourceIds = null,
        array $campaignIds = null,
        $isPublic = false,
        $isPublished = null,
        $isScheduled = null,
        $excludeScheduled = false,
        array $alphabetFilter = [],
    ) {
        $q = $this->getEntityManager()->createQueryBuilder();
        $q->select('c')
            ->from('MauticCampaignBundle:Campaign', 'c', 'c.id');

        if (!empty($sourceIds)) {
            $q->join('c.sources', 's');

            $expr = $q->expr()->andX(
                $q->expr()->eq('s.source', $q->expr()->literal($sourceType))
            );

            $expr->add(
                $q->expr()->in('s.sourceId', $sourceIds)
            );

            $q->where($expr);
        }

        if ($campaignIds || $isPublic || $isPublished) {
            $expr = $q->expr()->andX();
            if (!empty($campaignIds)) {
                $expr->add(
                    $q->expr()->in('c.id', ':campaigns')
                );

                $q->setParameter('campaigns', $campaignIds);
            }

            if ($isPublic) {
                $expr->add(
                    $q->expr()->eq('c.isPublished', ':true')
                );

                $q->setParameter('true', true, 'boolean');

                if ($excludeScheduled) {
                    $expr->add(
                        $q->expr()->orX(
                            $q->expr()->isNull('c.publishUp'),
                            $q->expr()->lte('c.publishUp', ':now')
                        )
                    );

                    $q->setParameter('now', new \DateTime(), \Doctrine\DBAL\Types\Type::DATETIME_MUTABLE);
                }
            }

            if ($isPublished) {
                // Only published
                $publishQb = $q->expr()->andX();
                $publishQb->add(
                    $q->expr()->eq('c.isPublished', 1)
                );

                if ($excludeScheduled) {
                    $publishQb->add(
                        $q->expr()->orX(
                            $q->expr()->isNull('c.publishUp'),
                            $q->expr()->lte('c.publishUp', ':now')
                        )
                    );

                    $q->setParameter('now', new \DateTime(), \Doctrine\DBAL\Types\Type::DATETIME_MUTABLE);
                }

                $expr->add($publishQb);
            }

            if (null !== $isScheduled) {
                $scheduledQb = $q->expr()->andX(
                    $q->expr()->eq('c.isPublished', 1)
                );

                if ($isScheduled) {
                    // Only scheduled
                    $scheduledQb->add(
                        $q->expr()->isNotNull('c.publishUp')
                    );
                } else {
                    // Only non-scheduled or published
                    $scheduledQb->add(
                        $q->expr()->orX(
                            $q->expr()->isNull('c.publishUp'),
                            $q->expr()->lte('c.publishUp', ':now')
                        )
                    );

                    $q->setParameter('now', new \DateTime(), \Doctrine\DBAL\Types\Type::DATETIME_MUTABLE);
                }
                $expr->add($scheduledQb);
            }

            $q->andWhere($expr);
        }

        if (!empty($alphabetFilter)) {
            $strexpr = $q->expr()->orX();
            foreach ($alphabetFilter as $letter) {
                $strexpr->add(
                    $q->expr()->like('c.name', $q->expr()->literal("$letter%"))
                );
            }
            $q->andWhere($strexpr);
        }

        $results = $q->getQuery()->getResult();

        if (empty($results)) {
            return [];
        }

        return $results;
    }

    /**
     * @param int $contactId
     *
     * @return array
     */
    public function getContactPendingEvents($contactId, array $campaignIds)
    {
        if (!$contactId || !count($campaignIds)) {
            return [];
        }

        // Get a list of events that are part of a campaign that has not been removed from
        $q = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $q->select(
            'e.id as event_id, e.campaign_id, e.parent_id as parent_event_id, e.name, e.type, e.event_type, e.channel, e.channel_id, e.properties, e.trigger_mode, e.trigger_date, e.trigger_interval_unit, e.trigger_interval_count'
        )
            ->from(MAUTIC_TABLE_PREFIX.'campaign_events', 'e')
            ->innerJoin('e', MAUTIC_TABLE_PREFIX.'campaign_leads', 'cl', 'cl.campaign_id = e.campaign_id')
            ->where(
                $q->expr()->andX(
                    $q->expr()->eq('cl.lead_id', ':contactId'),
                    $q->expr()->eq('cl.manually_removed', ':false'),
                    $q->expr()->in('cl.campaign_id', ':campaigns')
                )
            )
            ->setParameter('contactId', $contactId)
            ->setParameter('false', false, 'boolean')
            ->setParameter('campaigns', $campaignIds, \Doctrine\DBAL\Connection::PARAM_INT_ARRAY);

        return $q->executeQuery()->fetchAllAssociative();
    }

    /**
     * @param int $contactId
     * @param int $campaignId
     *
     * @return array
     */
    public function getContactPendingEventsForCampaign($contactId, $campaignId)
    {
        if (!$contactId || !$campaignId) {
            return [];
        }

        // Get a list of events from a specific campaign that a specific contact has not executed that is not scheduled
        // Scheduled is handled by another method
        // There should only be one campaign for a specific contact
        $q = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $q->select('e.id, e.parent_id, e.name, e.description, e.type, e.event_type, e.channel, e.channel_id, cl.lead_id, c.name as campaign_name, c.description as campaign_description, e.properties')
            ->from(MAUTIC_TABLE_PREFIX.'campaign_events', 'e')
            ->join('e', MAUTIC_TABLE_PREFIX.'campaigns', 'c', 'c.id = e.campaign_id')
            ->leftJoin('e', MAUTIC_TABLE_PREFIX.'campaign_leads', 'cl', 'cl.campaign_id = c.id')
            ->leftJoin(
                'e',
                MAUTIC_TABLE_PREFIX.'campaign_lead_event_log',
                'el',
                'el.event_id = e.id and el.lead_id = cl.lead_id AND el.rotation = (select MAX(rotation) FROM '.MAUTIC_TABLE_PREFIX.'campaign_lead_event_log ell where ell.event_id = e.id AND ell.lead_id = cl.lead_id)'
            )
            ->where(
                $q->expr()->andX(
                    $q->expr()->eq('cl.lead_id', ':contactId'),
                    $q->expr()->eq('e.campaign_id', ':campaignId'),
                    // ignore decisions
                    $q->expr()->neq('e.event_type', ':eventType'),
                    // ignore scheduled events
                    $q->expr()->orX(
                        $q->expr()->isNull('e.trigger_mode'),
                        $q->expr()->eq('e.trigger_mode', ':immediateMode')
                    ),
                    // only events that haven't been executed or weren't a scheduled execution that was acted upon
                    $q->expr()->orX(
                        $q->expr()->isNull('el.id'),
                        $q->expr()->andX(
                            $q->expr()->isNotNull('el.trigger_date'),
                            $q->expr()->eq('el.is_scheduled', 1)
                        )
                    )
                )
            )
            ->setParameter('contactId', $contactId)
            ->setParameter('campaignId', $campaignId)
            ->setParameter('eventType', 'decision')
            ->setParameter('immediateMode', 'immediate');

        $results = $q->executeQuery()->fetchAllAssociative();

        return $results;
    }

    /**
     * Get array of published campaign which contains scheduled events.
     *
     * @return array
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function getPublishedCampaignsWithScheduledEvents()
    {
        $q = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $q->select('DISTINCT(c.id), c.name')
            ->from(MAUTIC_TABLE_PREFIX.'campaigns', 'c')
            ->join('c', MAUTIC_TABLE_PREFIX.'campaign_events', 'e', 'e.campaign_id = c.id')
            ->where(
                $q->expr()->andX(
                    $q->expr()->eq('c.is_published', 1),
                    $q->expr()->eq('e.trigger_mode', $q->expr()->literal('schedule'))
                )
            );

        return $q->executeQuery()->fetchAllAssociative();
    }

    /**
     * Get a list of lead Ids that belong to a campaign and lead sources.
     *
     * @param array $params
     */
    public function getCampaignLeadIds($params)
    {
        // Only include leads that are part of the campaigns
        $q = $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->select('cl.lead_id')
            ->from(MAUTIC_TABLE_PREFIX.'campaign_leads', 'cl')
            ->where('cl.manually_removed = 0');

        if (!empty($params['campaigns'])) {
            $q->andWhere(
                $q->expr()->in('cl.campaign_id', $params['campaigns'])
            );
        }

        if (isset($params['leadIds'])) {
            $q->andWhere(
                $q->expr()->in('cl.lead_id', $params['leadIds'])
            );
        }

        return $q->executeQuery()->fetchFirstColumn();
    }

    /**
     * Returns leads that are part of a lead source and part of a campaign.
     *
     * @return array
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function getLeadsInCampaigns($params)
    {
        $q = $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->select('cl.lead_id')
            ->from(MAUTIC_TABLE_PREFIX.'campaign_leads', 'cl');

        if (!empty($params['campaigns'])) {
            $q->where(
                $q->expr()->in('cl.campaign_id', $params['campaigns'])
            );
        }

        return $q->executeQuery()->fetchFirstColumn();
    }

    /**
     * Get contact IDs of those who belong to the campaign.
     *
     * @return array
     */
    public function getContactIdsByChannel($campaignId, $channel, $channelId)
    {
        $q = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $q->select('cl.lead_id as id')
            ->from(MAUTIC_TABLE_PREFIX.'campaign_leads', 'cl')
            ->where(
                $q->expr()->andX(
                    $q->expr()->eq('cl.campaign_id', (int) $campaignId),
                    $q->expr()->eq('cl.manually_removed', ':false')
                )
            )
            ->setParameter('false', false, 'boolean');

        // Get leads that has had successful email hits
        $q->join(
            'cl',
            MAUTIC_TABLE_PREFIX.'campaign_lead_event_log',
            'el',
            'cl.campaign_id = el.campaign_id AND cl.lead_id = el.lead_id'
        );

        // Get the channel event
        $q->join('el', MAUTIC_TABLE_PREFIX.'campaign_events', 'e', 'el.event_id = e.id')
            ->andWhere(
                $q->expr()->andX(
                    $q->expr()->eq('e.channel', ':channel'),
                    $q->expr()->eq('e.channel_id', (int) $channelId),
                    $q->expr()->eq('el.is_scheduled', 0)
                )
            )
            ->setParameter('channel', $channel);

        return $q->executeQuery()->fetchFirstColumn();
    }

    /**
     * Get contact IDs of those who have not received or been sent the email.
     *
     * @return array
     */
    public function getContactsWithNoEventsByChannel($campaignId, $channel, $channelId)
    {
        // Get the leads that are part of the campaign
        $q = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $q->select('cl.lead_id as id')
            ->from(MAUTIC_TABLE_PREFIX.'campaign_leads', 'cl')
            ->where(
                $q->expr()->andX(
                    $q->expr()->eq('cl.campaign_id', (int) $campaignId),
                    $q->expr()->eq('cl.manually_removed', ':false')
                )
            )
            ->setParameter('false', false, 'boolean');

        // Left join on events that have been triggered
        $q->leftJoin(
            'cl',
            MAUTIC_TABLE_PREFIX.'campaign_lead_event_log',
            'el',
            'cl.campaign_id = el.campaign_id AND cl.lead_id = el.lead_id'
        );

        // Left join on events
        $q->leftJoin('el', MAUTIC_TABLE_PREFIX.'campaign_events', 'e', 'el.event_id = e.id');

        // Where the event has not been fired or is null
        $q->andWhere(
            $q->expr()->orX(
                $q->expr()->isNull('e.channel'),
                $q->expr()->andX(
                    $q->expr()->eq('e.channel', ':channel'),
                    $q->expr()->eq('e.channel_id', (int) $channelId)
                )
            )
        )
            ->setParameter('channel', $channel);

        return $q->executeQuery()->fetchFirstColumn();
    }

    /**
     * Get a count of leads in the campaign.
     *
     * @param int   $campaignId
     * @param int   $leadId        Optional lead ID to check if lead is part of campaign
     * @param array $pendingEvents List of specific events to rule out
     */
    public function getCampaignLeadCount($campaignId, $leadId = null, $pendingEvents = [], \DateTimeInterface $dateFrom = null, \DateTimeInterface $dateTo = null): int
    {
        $q = $this->getReplicaConnection()->createQueryBuilder();

        $q->select('count(cl.lead_id) as lead_count')
            ->from(MAUTIC_TABLE_PREFIX.'campaign_leads', 'cl')
            ->where(
                $q->expr()->andX(
                    $q->expr()->eq('cl.campaign_id', (int) $campaignId),
                    $q->expr()->eq('cl.manually_removed', ':false')
                )
            )
            ->setParameter('false', false, Types::BOOLEAN);

        if ($leadId) {
            $q->andWhere(
                $q->expr()->eq('cl.lead_id', (int) $leadId)
            );
        }

        if ($dateFrom && $dateTo) {
            $q->andWhere('cl.date_added BETWEEN FROM_UNIXTIME(:dateFrom) AND FROM_UNIXTIME(:dateTo)')
                ->setParameter('dateFrom', $dateFrom->getTimestamp(), \PDO::PARAM_INT)
                ->setParameter('dateTo', $dateTo->getTimestamp(), \PDO::PARAM_INT);
        }

        if (count($pendingEvents) > 0) {
            $sq = $this->getReplicaConnection()->createQueryBuilder();
            $sq->select('null')
                ->from(MAUTIC_TABLE_PREFIX.'campaign_lead_event_log', 'e')
                ->where(
                    $sq->expr()->andX(
                        $sq->expr()->eq('cl.lead_id', 'e.lead_id'),
                        $sq->expr()->in('e.event_id', $pendingEvents)
                    )
                );

            if ($dateFrom && $dateTo) {
                $sq->andWhere('cl.date_triggered BETWEEN FROM_UNIXTIME(:dateFrom) AND FROM_UNIXTIME(:dateTo)')
                    ->setParameter('dateFrom', $dateFrom->getTimestamp(), \PDO::PARAM_INT)
                    ->setParameter('dateTo', $dateTo->getTimestamp(), \PDO::PARAM_INT);
            }

            $q->andWhere(
                sprintf('NOT EXISTS (%s)', $sq->getSQL())
            );
        }

        if ($this->getReplicaConnection()->getConfiguration()->getResultCache()) {
            $results = $this->getReplicaConnection()->executeCacheQuery(
                $q->getSQL(),
                $q->getParameters(),
                $q->getParameterTypes(),
                new QueryCacheProfile(600)
            )->fetchAllAssociative();
        } else {
            $results = $q->executeQuery()->fetchAllAssociative();
        }

        return (int) $results[0]['lead_count'];
    }

    /**
     * Returns true if the campaign has at least one lead.
     *
     * @throws \Doctrine\DBAL\Cache\CacheException
     */
    public function hasCampaignLeads(int $campaignId): bool
    {
        $q = $this->getReplicaConnection()->createQueryBuilder();

        $q->select('1')
            ->from(MAUTIC_TABLE_PREFIX.'campaign_leads', 'cl')
            ->where(
                $q->expr()->andX(
                    $q->expr()->eq('cl.campaign_id', ':campaignId'),
                    $q->expr()->eq('cl.manually_removed', '0')
                )
            )
            ->setParameter('campaignId', $campaignId)
            ->setMaxResults(1);

        if ($this->getReplicaConnection()->getConfiguration()->getResultCache()) {
            $results = $this->getReplicaConnection()->executeCacheQuery(
                $q->getSQL(),
                $q->getParameters(),
                $q->getParameterTypes(),
                new QueryCacheProfile(600)
            )->fetchAllAssociative();
        } else {
            $results = $q->executeQuery()->fetchAllAssociative();
        }

        return (bool) $results;
    }

    /**
     * Get lead data of a campaign.
     *
     * @param int        $start
     * @param bool|false $limit
     * @param array      $select
     *
     * @return mixed
     */
    public function getCampaignLeads($campaignId, $start = 0, $limit = false, $select = ['cl.lead_id'])
    {
        $q = $this->getEntityManager()->getConnection()->createQueryBuilder();

        $q->select($select)
            ->from(MAUTIC_TABLE_PREFIX.'campaign_leads', 'cl')
            ->where(
                $q->expr()->andX(
                    $q->expr()->eq('cl.campaign_id', (int) $campaignId),
                    $q->expr()->eq('cl.manually_removed', ':false')
                )
            )
            ->setParameter('false', false, 'boolean')
            ->orderBy('cl.lead_id', 'ASC');

        if (!empty($limit)) {
            $q->setFirstResult($start)
                ->setMaxResults($limit);
        }

        return $q->executeQuery()->fetchAllAssociative();
    }

    /**
     * @param int $campaignId
     *
     * @return array
     */
    public function getEvents($type, $campaignId = null)
    {
        $q = $this->getEntityManager()->getConnection()->createQueryBuilder();

        $q->select('e.*')
            ->from(MAUTIC_TABLE_PREFIX.'campaign_events', 'e')
            ->where($q->expr()->eq('e.event_type', ':eventType'))
            ->setParameter('eventType', $type);

        if ($campaignId) {
            $q->andWhere($q->expr()->eq('e.campaign_id', (int) $campaignId));
        }

        return $q->executeQuery()->fetchAllAssociative();
    }

    /**
     * Get an array of form choices for existing campaigns.
     *
     * @param string|null $search
     * @param int         $limit
     * @param bool        $canEditOthers
     * @param bool        $viewOther
     *
     * @return array
     */
    public function getPublishedChoices($search = null, $limit = 0, $canEditOthers = true, $viewOther = true)
    {
        $choices = [];
        $qb      = $this->getEntityManager()->createQueryBuilder();
        $expr    = $qb->expr();
        $qb->select('partial c.{id, name}')->from('MauticCampaignBundle:Campaign', 'c', 'c.id');

        if (!$canEditOthers) {
            $qb->andWhere($expr->eq('c.createdBy', ':id'))
                ->setParameter('id', $this->getCreatedByUserId());
        }

        if (!$viewOther) {
            $qb->andWhere($expr->eq('c.createdBy', ':id'))
                ->setParameter('id', $this->getCreatedByUserId());
        }

        $qb->andWhere($expr->eq('c.isPublished', true))
            ->orderBy('c.name');

        if (!empty($search)) {
            $qb->andWhere(
                $expr->orX(
                    $expr->like('c.name', ':search'),
                    $expr->like('c.description', ':search')
                )
            )
                ->setParameter('search', $search.'%');
        }

        if ($limit > 0) {
            $qb->setFirstResult(0)
                ->setMaxResults($limit);
        }

        $campaigns = $qb->getQuery()->getArrayResult();

        foreach ($campaigns as $campaign) {
            $choices[$campaign['id']] = $campaign['name'];
        }

        return $choices;
    }

    /**
     * Get a list of dates in which campaigns were created.
     *
     * @return array
     */
    public function getCountByDate(\DateTime $dateFrom, \DateTime $dateTo, $unit)
    {
        $qb = $this->getEntityManager()->getConnection()->createQueryBuilder();

        $qb->select('COUNT(c.id) as count, CONCAT(c.created_by, ":", c.date_added) as datekey, c.date_added as date_added')
            ->from(MAUTIC_TABLE_PREFIX.'campaigns', 'c')
            ->leftJoin('c', MAUTIC_TABLE_PREFIX.'users', 'u', 'u.id = c.created_by')
            ->where($qb->expr()->gte('c.date_added', ':dateFrom'))
            ->andWhere($qb->expr()->lte('c.date_added', ':dateTo'))
            ->setParameter('dateFrom', $dateFrom->format('Y-m-d H:i:s'))
            ->setParameter('dateTo', $dateTo->format('Y-m-d H:i:s'))
            ->groupBy('datekey')
            ->orderBy('c.date_added', 'ASC');

        $results = $qb->executeQuery()->fetchAllAssociative();
        $chart   = new ChartQuery($qb->getConnection(), $dateFrom, $dateTo, $unit);

        return $chart->completeTimeData($results);
    }

    /**
     * Get a list of upcoming or published campaigns with basic stats.
     *
     * @return array
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getPublishedCampaigns($limit = 10, \DateTimeInterface $dateFrom = null, \DateTimeInterface $dateTo = null, $dateOnly = false)
    {
        $now     = new \DateTime();
        $q       = $this->getEntityManager()->createQueryBuilder();
        $results = null;

        $expr = $q->expr()->andX(
            $q->expr()->eq('c.isPublished', ':true'),
            $q->expr()->orX(
                $q->expr()->gte('c.publishUp', $q->expr()->literal($now->format('Y-m-d H:i:s'))),
                $q->expr()->andX(
                    $q->expr()->isNotNull('c.publishUp'),
                    $q->expr()->isNull('c.publishDown')
                ),
                $q->expr()->andX(
                    $q->expr()->isNull('c.publishUp'),
                    $q->expr()->gte('c.publishDown', $q->expr()->literal($now->format('Y-m-d H:i:s')))
                ),
                $q->expr()->andX(
                    $q->expr()->isNull('c.publishUp'),
                    $q->expr()->isNull('c.publishDown')
                )
            )
        );

        $q->select('partial c.{id, name, publishUp, publishDown}, partial ss.{id, leads, sent, scheduled, pending}')
            ->from('MauticCampaignBundle:Campaign', 'c')
            ->leftJoin('MauticCampaignBundle:Summary', 'ss', 'WITH', 'c.id = ss.campaign');

        $q->where($expr)
            ->setParameter('true', true, 'boolean')
            ->orderBy('c.publishUp', 'ASC')
            ->setMaxResults($limit);

        $campaigns = $q->getQuery()->getArrayResult();

        if (!empty($campaigns)) {
            if (true === $dateOnly) {
                foreach ($campaigns as &$campaign) {
                    if ($campaign['publishUp']) {
                        $campaign['publishUp'] = $campaign['publishUp']->format('Y-m-d H:i:s');
                    }
                }
            }

            $results = [
                'campaigns' => $campaigns,
            ];
        }

        return $results;
    }

    /**
     * Returns the total number of contacts not in campaign.
     */
    public function getCountsForPendingContacts(int $campaignId, ContactLimiter $limiter): CountResult
    {
        $selectCountOnly = false;
        $start           = null;
        $limit           = null;
        $canProcessMax   = null;
        $maxContactId    = null;
        $batchIds        = null;

        // Only get first batch
        if ($limiter->getBatchLimit()) {
            $limit = $limiter->getBatchLimit();
        }

        if ($limiter->getContactId()) {
            $batchIds = [$limiter->getContactId()];
        } else {
            $countSubQuery = $this->generateCountsSelectForPendingContacts(
                $campaignId,
                $limiter,
                false,
                $selectCountOnly,
                $limiter->hasCampaignLimit(),
                $maxContactId,
                $start,
                $limit
            );

            $count = $countSubQuery->executeQuery()->fetchOne();
        }

        return new CountResult(isset($count) ? (int) $count : 1, $canProcessMax);
    }

    /**
     * Returns the command list of batch limits.
     */
    public function getPendingContactIds(int $campaignId, ContactLimiter $limiter): array
    {
        $pendingContactids = [];
        $start             = $limiter->getMinContactId();
        $limit             = $limiter->getBatchLimit();

        if ($limiter->getContactId()) {
            return [$limiter->getContactId()];
        }

        $selectCountOnly = false;
        $maxContactId    = null;

        $batchQb = $this->generateCountsSelectForPendingContacts(
            $campaignId,
            $limiter,
            false,
            $selectCountOnly,
            false,
            $maxContactId,
            $start,
            $limit
        );

        $pendingContactids = array_column($batchQb->executeQuery()->fetchAllAssociative(), 'id');

        return $pendingContactids;
    }

    /**
     * Generate a query for getting either a count of what needs to be processed or a limited list of IDs.
     */
    private function generateCountsSelectForPendingContacts(
        int $campaignId,
        ContactLimiter $limiter,
        bool $includePrimary = false,
        bool $countOnly = false,
        bool $campaignLimitOnly = false,
        int $maxId = null,
        int $start = null,
        int $limit = null,
    ) {
        // Main query
        $q = $this->getEntityManager()->getConnection()->createQueryBuilder();
        if ($countOnly) {
            $q->select('count(*) as count');
        } else {
            $select = 'l.id';
            if ($includePrimary) {
                $select .= ', l.stage_id, l.email as primary_email';
            }
            $q->select($select);
        }
        $q->from(MAUTIC_TABLE_PREFIX.'leads', 'l')
            ->leftJoin('l', MAUTIC_TABLE_PREFIX.'campaign_leads', 'cl', "cl.lead_id = l.id and cl.campaign_id = {$campaignId}");

        // Limit to campaign ID
        $expr = $q->expr()->isNull('cl.campaign_id');
        $q->where($expr)
            ->andWhere($q->expr()->eq('l.is_deleted', 0));
        // Filter segments
        $this->addLeadLimiterWhereClause($q, $limiter);

        if ($campaignLimitOnly) {
            return $q;
        }

        if ($maxId) {
            $q->andWhere(
                $q->expr()->lte('l.id', $maxId)
            );
        }

        if ($start) {
            $q->andWhere(
                $q->expr()->gte('l.id', $start)
            );
        }

        if ($limit) {
            $q->setMaxResults($limit);
        }

        return $q;
    }

    /**
     * @throws \Exception
     */
    private function addLeadLimiterWhereClause(\Doctrine\DBAL\Query\QueryBuilder $q, ContactLimiter $limiter)
    {
        // Add segment filter
        if ($segments = $limiter->getSegmentContactIds()) {
            $segmentQueryBuilder = $this->getEntityManager()->getConnection()->createQueryBuilder();
            $segmentQueryBuilder->select('fl.lead_id')
                ->from(MAUTIC_TABLE_PREFIX.'leads', 'fl')
                ->join(
                    'fl',
                    MAUTIC_TABLE_PREFIX.'lead_lists_leads',
                    'fll',
                    'fl.id = fll.lead_id'
                );

            $where = $segmentQueryBuilder->expr()->andX();

            $inListIds = $notInListIds = [];
            foreach ($segments as $segmentId => $segmentContacts) {
                if ($segmentContacts->include) {
                    $inListIds[] = $segmentId;
                } else {
                    $notInListIds[] = $segmentId;
                }
            }

            if ($inListIds) {
                $where->add(
                    $segmentQueryBuilder->expr()->andX(
                        $segmentQueryBuilder->expr()->in(
                            'fll.leadlist_id',
                            $inListIds
                        ),
                        $segmentQueryBuilder->expr()->eq('fll.manually_removed', 0)
                    )
                );
            }

            if ($notInListIds) {
                $subListQb = $this->getEntityManager()->getConnection()->createQueryBuilder();
                $subListQb->select('null')
                    ->from(MAUTIC_TABLE_PREFIX.'lead_lists_leads', 'subll')
                    ->where(
                        $segmentQueryBuilder->expr()->andX(
                            $segmentQueryBuilder->expr()->eq('subll.lead_id', 'fl.id'),
                            $segmentQueryBuilder->expr()->in(
                                'subll.leadlist_id',
                                $notInListIds
                            ),
                            $segmentQueryBuilder->expr()->eq('subll.manually_removed', 0)
                        )
                    );

                $where->add(
                    sprintf('NOT EXISTS (%s)', $subListQb->getSQL())
                );
            }

            if ($where->count()) {
                $segmentQueryBuilder->where($where);
                $segmentQueryBuilder->groupBy('fl.id');

                $q->andWhere(
                    $q->expr()->in('l.id', sprintf('(%s)', $segmentQueryBuilder->getSQL()))
                );

                // Now add add any parameters from the sub-queries
                $params      = array_merge($segmentQueryBuilder->getParameters(), $q->getParameters());
                $paramTypes  = array_merge($segmentQueryBuilder->getParameterTypes(), $q->getParameterTypes());
                $parameters  = [];
                $parameterNb = 0;
                for ($i = 0; $i < count($params); ++$i) {
                    if (isset($paramTypes[$i])) {
                        ++$parameterNb;
                        $parameterName              = 'param_'.$parameterNb;
                        $parameters[$parameterName] = $params[$i];
                        $q->setParameter($parameterName, $params[$i], $paramTypes[$i]);
                    } else {
                        $q->setParameter($params[$i], $params[++$i]);
                    }
                }
            }
        }

        // Add ID limits
        if ($contactIds = $limiter->getContactIds()) {
            $q->andWhere(
                $q->expr()->in('l.id', $contactIds)
            );
        }

        if ($excludeContactIds = $limiter->getExcludedContactIds()) {
            $q->andWhere(
                $q->expr()->notIn('l.id', $excludeContactIds)
            );
        }

        if ($includedOutOfOwners = $limiter->getOutOfContactLists()) {
            $ownerQueryBuilder = $this->getEntityManager()->getConnection()->createQueryBuilder();
            $ownerQueryBuilder->select('ol.lead_id')
                ->from(MAUTIC_TABLE_PREFIX.'leads', 'ol')
                ->join(
                    'ol',
                    MAUTIC_TABLE_PREFIX.'lead_lists_leads',
                    'oll',
                    'ol.id = oll.lead_id'
                );

            $ownerQueryBuilder->where(
                $ownerQueryBuilder->expr()->andX(
                    $ownerQueryBuilder->expr()->in(
                        'oll.leadlist_id',
                        $includedOutOfOwners
                    )
                )
            );

            $q->andWhere(
                $q->expr()->notIn('l.id', sprintf('(%s)', $ownerQueryBuilder->getSQL()))
            );
        }
    }

    private function getCreatedByUserId()
    {
        /** @var User $user */
        if ($this->user->isGuest()) {
            /** @phpstan-ignore-next-line */
            return 0;
        }

        /** @phpstan-ignore-next-line */
        return $this->user->getId();
    }

    /**
     * Get a list of sources for a campaign.
     *
     * @param null $source
     * @param null $sourceId
     *
     * @return array
     */
    public function getCampaignSources($campaignId, $source = null, $sourceId = null)
    {
        $q = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $q->select('s.source as source, s.source_id as sourceId, s.date_added as dateAdded, s.source as name')
            ->from(MAUTIC_TABLE_PREFIX.'campaign_sources', 's')
            ->where('s.campaign_id = :campaignId')
            ->setParameter('campaignId', $campaignId);
        if ($source) {
            $q->andWhere('s.source = :sourceType')
                ->setParameter('sourceType', $source);
        }

        if ($sourceId) {
            $q->andWhere('s.source_id = :sourceId')
                ->setParameter('sourceId', $sourceId);
        }

        return $q->executeQuery()->fetchAllAssociative();
    }
}
