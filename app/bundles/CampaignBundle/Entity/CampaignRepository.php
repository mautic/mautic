<?php

namespace Mautic\CampaignBundle\Entity;

use Doctrine\DBAL\Cache\QueryCacheProfile;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Query\Expr;
use Mautic\CampaignBundle\Entity\Result\CountResult;
use Mautic\CampaignBundle\Executioner\ContactFinder\Limiter\ContactLimiter;
use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * @extends CommonRepository<Campaign>
 */
class CampaignRepository extends CommonRepository
{
    use ContactLimiterTrait;
    use ReplicaConnectionTrait;

    public function getEntities(array $args = [])
    {
        $q = $this->getEntityManager()->createQueryBuilder();
        $q->select($this->getTableAlias().', cat')
            ->from(Campaign::class, $this->getTableAlias(), $this->getTableAlias().'.id')
            ->leftJoin($this->getTableAlias().'.category', 'cat');

        if (!empty($args['joinLists'])) {
            $q->leftJoin($this->getTableAlias().'.lists', 'l');
        }

        if (!empty($args['joinForms'])) {
            $q->leftJoin($this->getTableAlias().'.forms', 'f');
        }
        $q->where($q->expr()->isNull($this->getTableAlias().'.deleted'));
        $args['qb'] = $q;

        return parent::getEntities($args);
    }

    public function setCampaignAsDeleted(int $campaignId): void
    {
        $dateTime = (new \DateTime())->format('Y-m-d H:i:s');

        $this->getEntityManager()->getConnection()->update(
            MAUTIC_TABLE_PREFIX.Event::TABLE_NAME,
            ['deleted'     => $dateTime],
            ['campaign_id' => $campaignId]
        );

        $this->getEntityManager()->getConnection()->update(
            MAUTIC_TABLE_PREFIX.Campaign::TABLE_NAME,
            ['deleted'   => $dateTime, 'is_published' => 0],
            ['id'        => $campaignId]
        );
    }

    /**
     * Returns a list of all published (and active) campaigns (optionally for a specific lead).
     *
     * @param bool $forList   If true, returns ID and name only
     * @param bool $viewOther If true, returns all the campaigns
     *
     * @return array
     */
    public function getPublishedCampaigns($specificId = null, ?int $leadId = null, $forList = false, $viewOther = false)
    {
        $q = $this->getEntityManager()->createQueryBuilder()
            ->from(Campaign::class, 'c', 'c.id');

        if ($forList && $leadId) {
            $q->select('partial c.{id, name}, partial l.{campaign, lead, dateAdded, manuallyAdded, manuallyRemoved}, partial ll.{id}');
        } elseif ($forList) {
            $q->select('partial c.{id, name}, partial ll.{id}');
        } else {
            $q->select('c, l, partial ll.{id}')
                ->leftJoin('c.events', 'e')
                ->leftJoin('e.log', 'o');
        }

        if ($leadId || !$forList) {
            $q->leftJoin('c.leads', 'l');
        }

        $q->leftJoin('c.lists', 'll')
            ->where($this->getPublishedByDateExpression($q));

        if (!$viewOther) {
            $q->andWhere($q->expr()->eq('c.createdBy', ':id'))
                ->setParameter('id', $this->currentUser->getId());
        }

        if (!empty($specificId)) {
            $q->andWhere(
                $q->expr()->eq('c.id', (int) $specificId)
            );
        }

        if (!empty($leadId)) {
            $q->andWhere(
                $q->expr()->eq('IDENTITY(l.lead)', (int) $leadId)
            );
            $q->andWhere(
                $q->expr()->eq('l.manuallyRemoved', ':manuallyRemoved')
            )->setParameter('manuallyRemoved', false);
        }

        return $q->getQuery()->getArrayResult();
    }

    /**
     * Returns a list of all published (and active) campaigns that specific lead lists are part of.
     *
     * @param int|array $leadLists
     */
    public function getPublishedCampaignsByLeadLists($leadLists): array
    {
        if (!is_array($leadLists)) {
            $leadLists = [(int) $leadLists];
        } else {
            foreach ($leadLists as &$id) {
                $id = (int) $id;
            }
        }

        $q = $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->select('c.id, c.name, ll.leadlist_id as list_id')
            ->from(MAUTIC_TABLE_PREFIX.'campaigns', 'c');

        $q->join('c', MAUTIC_TABLE_PREFIX.'campaign_leadlist_xref', 'll', 'c.id = ll.campaign_id')
            ->where($this->getPublishedByDateExpression($q));

        $q->andWhere(
            $q->expr()->in('ll.leadlist_id', $leadLists)
        );

        $results = $q->executeQuery()->fetchAllAssociative();

        $campaigns = [];
        foreach ($results as $result) {
            if (!isset($campaigns[$result['id']])) {
                $campaigns[$result['id']] = [
                    'id'    => $result['id'],
                    'name'  => $result['name'],
                    'lists' => [],
                ];
            }

            $campaigns[$result['id']]['lists'][$result['list_id']] = [
                'id' => $result['list_id'],
            ];
        }

        return $campaigns;
    }

    /**
     * Get array of list IDs assigned to this campaign.
     *
     * @param int|null $id
     */
    public function getCampaignListIds($id = null): array
    {
        $q = $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->from(MAUTIC_TABLE_PREFIX.'campaign_leadlist_xref', 'cl');

        if ($id) {
            $q->select('cl.leadlist_id')
                ->where(
                    $q->expr()->eq('cl.campaign_id', $id)
                );
        } else {
            // Retrieve a list of unique IDs that are assigned to a campaign
            $q->select('DISTINCT cl.leadlist_id');
        }

        $lists   = [];
        $results = $q->executeQuery()->fetchAllAssociative();

        foreach ($results as $r) {
            $lists[] = $r['leadlist_id'];
        }

        return $lists;
    }

    /**
     * Get array of list IDs => name assigned to this campaign.
     */
    public function getCampaignListSources($id): array
    {
        $q = $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->select('cl.leadlist_id, l.name')
            ->from(MAUTIC_TABLE_PREFIX.'campaign_leadlist_xref', 'cl')
            ->join('cl', MAUTIC_TABLE_PREFIX.'lead_lists', 'l', 'l.id = cl.leadlist_id');
        $q->where(
            $q->expr()->eq('cl.campaign_id', $id)
        );

        $lists   = [];
        $results = $q->executeQuery()->fetchAllAssociative();

        foreach ($results as $r) {
            $lists[$r['leadlist_id']] = $r['name'];
        }

        return $lists;
    }

    /**
     * Get array of form IDs => name assigned to this campaign.
     */
    public function getCampaignFormSources($id): array
    {
        $q = $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->select('cf.form_id, f.name')
            ->from(MAUTIC_TABLE_PREFIX.'campaign_form_xref', 'cf')
            ->join('cf', MAUTIC_TABLE_PREFIX.'forms', 'f', 'f.id = cf.form_id');
        $q->where(
            $q->expr()->eq('cf.campaign_id', $id)
        );

        $forms   = [];
        $results = $q->executeQuery()->fetchAllAssociative();

        foreach ($results as $r) {
            $forms[$r['form_id']] = $r['name'];
        }

        return $forms;
    }

    /**
     * @return array
     */
    public function findByFormId($formId)
    {
        $q = $this->createQueryBuilder('c')
            ->join('c.forms', 'f');
        $q->where(
            $q->expr()->eq('f.id', $formId)
        );

        return $q->getQuery()->getResult();
    }

    public function getTableAlias(): string
    {
        return 'c';
    }

    /**
     * @param \Doctrine\ORM\QueryBuilder|\Doctrine\DBAL\Query\QueryBuilder $q
     */
    protected function addCatchAllWhereClause($q, $filter): array
    {
        return $this->addStandardCatchAllWhereClause($q, $filter, [
            'c.name',
            'c.description',
        ]);
    }

    /**
     * @param \Doctrine\ORM\QueryBuilder|\Doctrine\DBAL\Query\QueryBuilder $q
     */
    protected function addSearchCommandWhereClause($q, $filter): array
    {
        return $this->addStandardSearchCommandWhereClause($q, $filter);
    }

    /**
     * @return string[]
     */
    public function getSearchCommands(): array
    {
        return $this->getStandardSearchCommands();
    }

    /**
     * Get a list of popular (by logs) campaigns.
     *
     * @param int $limit
     */
    public function getPopularCampaigns($limit = 10): array
    {
        $q = $this->getEntityManager()->getConnection()->createQueryBuilder();

        $q->select('count(cl.ip_id) as hits, c.id AS campaign_id, c.name')
            ->from(MAUTIC_TABLE_PREFIX.'campaign_lead_event_log', 'cl')
            ->leftJoin('cl', MAUTIC_TABLE_PREFIX.'campaigns', 'c', 'cl.campaign_id = c.id')
            ->orderBy('hits', 'DESC')
            ->groupBy('c.id, c.name')
            ->setMaxResults($limit);

        $expr = $this->getPublishedByDateExpression($q, 'c');
        $q->where($expr);

        return $q->executeQuery()->fetchAllAssociative();
    }

    public function getCountsForPendingContacts($campaignId, array $pendingEvents, ContactLimiter $limiter): CountResult
    {
        $q = $this->getReplicaConnection($limiter)->createQueryBuilder();

        $q->select('min(cl.lead_id) as min_id, max(cl.lead_id) as max_id, count(cl.lead_id) as the_count')
            ->from(MAUTIC_TABLE_PREFIX.'campaign_leads', 'cl')
            ->where(
                $q->expr()->and(
                    $q->expr()->eq('cl.campaign_id', (int) $campaignId),
                    $q->expr()->eq('cl.manually_removed', ':false')
                )
            )
            ->setParameter('false', false, 'boolean');

        $this->updateQueryFromContactLimiter('cl', $q, $limiter, true);

        if (count($pendingEvents) > 0) {
            $sq = $this->getEntityManager()->getConnection()->createQueryBuilder();
            $sq->select('null')
                ->from(MAUTIC_TABLE_PREFIX.'campaign_lead_event_log', 'e')
                ->where(
                    $sq->expr()->and(
                        $sq->expr()->eq('cl.lead_id', 'e.lead_id'),
                        $sq->expr()->eq('e.rotation', 'cl.rotation'),
                        $sq->expr()->in('e.event_id', $pendingEvents)
                    )
                );

            $q->andWhere(
                sprintf('NOT EXISTS (%s)', $sq->getSQL())
            );
        }

        $result = $q->executeQuery()->fetchAssociative();

        return new CountResult($result['the_count'], $result['min_id'], $result['max_id']);
    }

    /**
     * Get pending contact IDs for a campaign.
     */
    public function getPendingContactIds($campaignId, ContactLimiter $limiter): array
    {
        if ($limiter->hasCampaignLimit() && 0 === $limiter->getCampaignLimitRemaining()) {
            return [];
        }

        $q = $this->getReplicaConnection($limiter)->createQueryBuilder();

        $q->select('cl.lead_id')
            ->from(MAUTIC_TABLE_PREFIX.'campaign_leads', 'cl')
            ->where(
                $q->expr()->and(
                    $q->expr()->eq('cl.campaign_id', (int) $campaignId),
                    $q->expr()->eq('cl.manually_removed', ':false')
                )
            )
            ->setParameter('false', false, 'boolean')
            ->orderBy('cl.lead_id', 'ASC');

        $this->updateQueryFromContactLimiter('cl', $q, $limiter);

        // Only leads that have not started the campaign
        $sq = $this->getReplicaConnection($limiter)->createQueryBuilder();
        $sq->select('null')
            ->from(MAUTIC_TABLE_PREFIX.'campaign_lead_event_log', 'e')
            ->where(
                $sq->expr()->and(
                    $sq->expr()->eq('e.lead_id', 'cl.lead_id'),
                    $sq->expr()->eq('e.campaign_id', (int) $campaignId),
                    $sq->expr()->eq('e.rotation', 'cl.rotation')
                )
            );

        $q->andWhere(
            sprintf('NOT EXISTS (%s)', $sq->getSQL())
        );

        if ($limiter->hasCampaignLimit() && $limiter->getCampaignLimitRemaining() < $limiter->getBatchLimit()) {
            $q->setMaxResults($limiter->getCampaignLimitRemaining());
        }

        $results = $q->executeQuery()->fetchAllAssociative();

        $leads   = [];
        foreach ($results as $r) {
            $leads[] = $r['lead_id'];
        }
        unset($results);

        if ($limiter->hasCampaignLimit()) {
            $limiter->reduceCampaignLimitRemaining(count($leads));
        }

        return $leads;
    }

    /**
     * Get a count of leads that belong to the campaign.
     *
     * @param int   $campaignId
     * @param int   $leadId        Optional lead ID to check if lead is part of campaign
     * @param array $pendingEvents List of specific events to rule out
     *
     * @throws \Doctrine\DBAL\Cache\CacheException
     */
    public function getCampaignLeadCount($campaignId, $leadId = null, $pendingEvents = [], \DateTimeInterface $dateFrom = null, \DateTimeInterface $dateTo = null): int
    {
        $q = $this->getReplicaConnection()->createQueryBuilder();

        $q->select('count(cl.lead_id) as lead_count')
            ->from(MAUTIC_TABLE_PREFIX.'campaign_leads', 'cl')
            ->where(
                $q->expr()->and(
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
                    $sq->expr()->and(
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
     * Get lead data of a campaign.
     *
     * @param int        $start
     * @param bool|false $limit
     * @param array      $select
     *
     * @return mixed[]
     */
    public function getCampaignLeads($campaignId, $start = 0, $limit = false, $select = ['cl.lead_id']): array
    {
        $q = $this->getReplicaConnection()->createQueryBuilder();

        $q->select($select)
            ->from(MAUTIC_TABLE_PREFIX.'campaign_leads', 'cl')
            ->where(
                $q->expr()->and(
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
     * @return mixed
     */
    public function getContactSingleSegmentByCampaign($contactId, $campaignId)
    {
        $q = $this->getEntityManager()->getConnection()->createQueryBuilder();

        return $q->select('ll.id, ll.name')
            ->from(MAUTIC_TABLE_PREFIX.'lead_lists', 'll')
            ->join('ll', MAUTIC_TABLE_PREFIX.'lead_lists_leads', 'lll', 'lll.leadlist_id = ll.id and lll.lead_id = :contactId and lll.manually_removed = 0')
            ->join('ll', MAUTIC_TABLE_PREFIX.'campaign_leadlist_xref', 'clx', 'clx.leadlist_id = ll.id and clx.campaign_id = :campaignId')
            ->setParameter('contactId', (int) $contactId)
            ->setParameter('campaignId', (int) $campaignId)
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();
    }

    /**
     * @param int   $segmentId
     * @param array $campaignIds
     */
    public function getCampaignsSegmentShare($segmentId, $campaignIds = []): array
    {
        $q = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $q->select('c.id, c.name, ROUND(IFNULL(COUNT(DISTINCT t.lead_id)/COUNT(DISTINCT cl.lead_id)*100, 0),1) segmentCampaignShare');
        $q->from(MAUTIC_TABLE_PREFIX.'campaigns', 'c')
            ->leftJoin('c', MAUTIC_TABLE_PREFIX.'campaign_leads', 'cl', 'cl.campaign_id = c.id AND cl.manually_removed = 0')
            ->leftJoin('cl',
                '(SELECT lll.lead_id AS ll, lll.lead_id FROM '.MAUTIC_TABLE_PREFIX.'lead_lists_leads lll WHERE lll.leadlist_id = '.$segmentId
                .' AND lll.manually_removed = 0)',
                't',
                't.lead_id = cl.lead_id'
            );
        $q->groupBy('c.id');

        if (!empty($campaignIds)) {
            $q->where($q->expr()->in('c.id', $campaignIds));
        }

        return $q->executeQuery()->fetchAllAssociative();
    }

    /**
     * Searches for emails assigned to campaign and returns associative array of email ids in format:.
     *
     *  array (size=1)
     *      0 =>
     *          array (size=2)
     *              'channelId' => int 18
     *
     * or empty array if nothing found.
     *
     * @param int $id
     */
    public function fetchEmailIdsById($id): array
    {
        $emails = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('e.channelId')
            ->from(Campaign::class, $this->getTableAlias(), $this->getTableAlias().'.id')
            ->leftJoin(
                $this->getTableAlias().'.events',
                'e',
                Expr\Join::WITH,
                "e.channel = '".Event::CHANNEL_EMAIL."'"
            )
            ->where($this->getTableAlias().'.id = :id')
            ->setParameter('id', $id)
            ->andWhere('e.channelId IS NOT NULL')
            ->getQuery()
            ->setHydrationMode(\Doctrine\ORM\Query::HYDRATE_ARRAY)
            ->getResult();

        $return = [];
        foreach ($emails as $email) {
            // Every channelId represents e-mail ID
            $return[] = $email['channelId']; // mautic_campaign_events.channel_id
        }

        return $return;
    }

    /**
     * @return array<int, int>
     */
    public function getCampaignIdsWithDependenciesOnEmail(int $emailId): array
    {
        $query = $this->getEntityManager()
            ->createQueryBuilder()
            ->select($this->getTableAlias().'.id')
            ->distinct()
            ->from(Campaign::class, $this->getTableAlias(), $this->getTableAlias().'.id')
            ->leftJoin(
                $this->getTableAlias().'.events',
                'e',
                Expr\Join::WITH,
                "e.channel = '".Event::CHANNEL_EMAIL."'"
            )
            ->where('e.channelId = :emailId')
            ->setParameter('emailId', $emailId)
            ->getQuery();

        return array_unique(array_map(fn ($val): int => (int) $val, $query->getSingleColumnResult()));
    }
}
