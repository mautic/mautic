<?php

namespace Mautic\EmailBundle\Entity;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Mautic\ChannelBundle\Entity\MessageQueue;
use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\LeadBundle\Entity\DoNotContact;

/**
 * @extends CommonRepository<Email>
 */
class EmailRepository extends CommonRepository
{
    public const EMAILS_PREFIX        = 'e';

    public const DNC_PREFIX           = 'dnc';

    public const TRACKABLE_PREFIX     = 'tr';

    public const REDIRECT_PREFIX      = 'pr';

    /**
     * Get an array of do not email.
     *
     * @param array $leadIds
     */
    public function getDoNotEmailList($leadIds = []): array
    {
        $q = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $q->select('l.id, l.email')
            ->from(MAUTIC_TABLE_PREFIX.'lead_donotcontact', 'dnc')
            ->leftJoin('dnc', MAUTIC_TABLE_PREFIX.'leads', 'l', 'l.id = dnc.lead_id')
            ->where($q->expr()->eq('dnc.channel', $q->expr()->literal('email')))
            ->andWhere($q->expr()->neq('l.email', $q->expr()->literal('')));

        if ($leadIds) {
            $q->andWhere(
                $q->expr()->in('l.id', $leadIds)
            );
        }

        $results = $q->executeQuery()->fetchAllAssociative();

        $dnc = [];
        foreach ($results as $r) {
            $dnc[$r['id']] = strtolower($r['email']);
        }

        return $dnc;
    }

    /**
     * Check to see if an email is set as do not contact.
     *
     * @param string $email
     *
     * @return false|array{id: numeric-string, unsubscribed: bool, bounced: bool, manual: bool, comments: string}
     */
    public function checkDoNotEmail($email)
    {
        $q = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $q->select('dnc.*')
            ->from(MAUTIC_TABLE_PREFIX.'lead_donotcontact', 'dnc')
            ->leftJoin('dnc', MAUTIC_TABLE_PREFIX.'leads', 'l', 'l.id = dnc.lead_id')
            ->where($q->expr()->eq('dnc.channel', $q->expr()->literal('email')))
            ->andWhere('l.email = :email')
            ->setParameter('email', $email);

        $results = $q->executeQuery()->fetchAllAssociative();

        $dnc     = count($results) ? $results[0] : null;

        if (null === $dnc) {
            return false;
        }

        $dnc['reason'] = (int) $dnc['reason'];

        return [
            'id'           => $dnc['id'],
            'unsubscribed' => (DoNotContact::UNSUBSCRIBED === $dnc['reason']),
            'bounced'      => (DoNotContact::BOUNCED === $dnc['reason']),
            'manual'       => (DoNotContact::MANUAL === $dnc['reason']),
            'comments'     => $dnc['comments'],
        ];
    }

    /**
     * Delete DNC row.
     *
     * @param int $id
     */
    public function deleteDoNotEmailEntry($id): void
    {
        $this->getEntityManager()->getConnection()->delete(MAUTIC_TABLE_PREFIX.'lead_donotcontact', ['id' => (int) $id]);
    }

    /**
     * Get a list of entities.
     *
     * @return Paginator
     */
    public function getEntities(array $args = [])
    {
        $q = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('e')
            ->from(Email::class, 'e', 'e.id');
        if (empty($args['iterator_mode']) && empty($args['iterable_mode'])) {
            $q->leftJoin('e.category', 'c');

            if (empty($args['ignoreListJoin']) && (!isset($args['email_type']) || 'list' == $args['email_type'])) {
                $q->leftJoin('e.lists', 'l');
            }
        }

        $args['qb'] = $q;

        return parent::getEntities($args);
    }

    /**
     * Get amounts of sent and read emails.
     *
     * @return array
     */
    public function getSentReadCount()
    {
        // Get entities
        $q = $this->getEntityManager()->createQueryBuilder();
        $q->select('SUM(e.sentCount) as sent_count, SUM(e.readCount) as read_count')
            ->from(Email::class, 'e');
        $results = $q->getQuery()->getSingleResult(Query::HYDRATE_ARRAY);

        if (!isset($results['sent_count'])) {
            $results['sent_count'] = 0;
        }
        if (!isset($results['read_count'])) {
            $results['read_count'] = 0;
        }

        return $results;
    }

    /**
     * @param int            $emailId
     * @param int[]|null     $variantIds
     * @param int[]|null     $listIds
     * @param bool           $countOnly
     * @param int|null       $limit
     * @param int|null       $minContactId
     * @param int|null       $maxContactId
     * @param bool           $countWithMaxMin
     * @param \DateTime|null $maxDate
     *
     * @return QueryBuilder|int|array
     */
    public function getEmailPendingQuery(
        $emailId,
        $variantIds = null,
        $listIds = null,
        $countOnly = false,
        $limit = null,
        $minContactId = null,
        $maxContactId = null,
        $countWithMaxMin = false,
        $maxDate = null,
        int $maxThreads = null,
        int $threadId = null,
    ) {
        // Do not include leads in the do not contact table
        $dncQb = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $dncQb->select('dnc.lead_id')
            ->from(MAUTIC_TABLE_PREFIX.'lead_donotcontact', 'dnc')
            ->where($dncQb->expr()->eq('dnc.channel', $dncQb->expr()->literal('email')));

        // Do not include contacts where the message is pending in the message queue
        $mqQb = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $mqQb->select('mq.lead_id')
            ->from(MAUTIC_TABLE_PREFIX.'message_queue', 'mq')
            ->where(
                $mqQb->expr()->and(
                    $mqQb->expr()->neq('mq.status', $mqQb->expr()->literal(MessageQueue::STATUS_SENT)),
                    $mqQb->expr()->eq('mq.channel', $mqQb->expr()->literal('email'))
                )
            );

        // Do not include leads that have already been emailed
        $statQb = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $statQb->select('stat.lead_id')
            ->from(MAUTIC_TABLE_PREFIX.'email_stats', 'stat');

        $statQb->andWhere($statQb->expr()->isNotNull('stat.lead_id'));

        if ($variantIds) {
            if (!in_array($emailId, $variantIds)) {
                $variantIds[] = (string) $emailId;
            }
            $statQb->andWhere($statQb->expr()->in('stat.email_id', $variantIds));
            $mqQb->andWhere($mqQb->expr()->in('mq.channel_id', $variantIds));
        } else {
            $statQb->andWhere($statQb->expr()->eq('stat.email_id', (int) $emailId));
            $mqQb->andWhere($mqQb->expr()->eq('mq.channel_id', (int) $emailId));
        }

        // Only include those who belong to the associated lead lists
        if (is_null($listIds)) {
            // Get a list of lists associated with this email
            $lists = $this->getEntityManager()->getConnection()->createQueryBuilder()
                ->select('el.leadlist_id')
                ->from(MAUTIC_TABLE_PREFIX.'email_list_xref', 'el')
                ->where('el.email_id = '.(int) $emailId)
                ->executeQuery()
                ->fetchAllAssociative();

            $listIds = array_column($lists, 'leadlist_id');

            if (empty($listIds)) {
                // Prevent fatal error
                return ($countOnly) ? 0 : [];
            }
        } elseif (!is_array($listIds)) {
            $listIds = [$listIds];
        }

        // Only include those in associated segments
        $segmentQb = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $segmentQb->select('ll.lead_id')
            ->from(MAUTIC_TABLE_PREFIX.'lead_lists_leads', 'll')
            ->where(
                $segmentQb->expr()->and(
                    $segmentQb->expr()->in('ll.leadlist_id', $listIds),
                    $segmentQb->expr()->eq('ll.manually_removed', ':false')
                )
            );

        if (null !== $maxDate) {
            $segmentQb->andWhere($segmentQb->expr()->lte('ll.date_added', ':max_date'));
            $segmentQb->setParameter('max_date', $maxDate, \Doctrine\DBAL\Types\Types::DATETIME_MUTABLE);
        }

        // Main query
        $q = $this->getEntityManager()->getConnection()->createQueryBuilder();
        if ($countOnly) {
            $q->select('count(*) as count');
            if ($countWithMaxMin) {
                $q->addSelect('MIN(l.id) as min_id, MAX(l.id) as max_id');
            }
        } else {
            $q->select('l.*');
        }

        $q->from(MAUTIC_TABLE_PREFIX.'leads', 'l')
            ->andWhere(sprintf('l.id IN (%s)', $segmentQb->getSQL()))
            ->andWhere(sprintf('l.id NOT IN (%s)', $dncQb->getSQL()))
            ->andWhere(sprintf('l.id NOT IN (%s)', $statQb->getSQL()))
            ->andWhere(sprintf('l.id NOT IN (%s)', $mqQb->getSQL()))
            ->setParameter('false', false, 'boolean');

        $excludedListQb = $this->getExcludedListQuery((int) $emailId);

        if ($excludedListQb) {
            $q->andWhere(sprintf('l.id NOT IN (%s)', $excludedListQb->getSQL()));
        }

        // Do not include leads which are not subscribed to the category set for email.
        $unsubscribeLeadsQb = $this->getCategoryUnsubscribedLeadsQuery((int) $emailId);
        $q->andWhere(sprintf('l.id NOT IN (%s)', $unsubscribeLeadsQb->getSQL()));

        $q = $this->setMinMaxIds($q, 'l.id', $minContactId, $maxContactId);

        // Has an email
        $q->andWhere(
            $q->expr()->and(
                $q->expr()->isNotNull('l.email'),
                $q->expr()->neq('l.email', $q->expr()->literal(''))
            )
        );

        if ($threadId && $maxThreads) {
            if ($threadId <= $maxThreads) {
                $q->andWhere('MOD((l.id + :threadShift), :maxThreads) = 0')
                        ->setParameter('threadShift', $threadId - 1, \Doctrine\DBAL\ParameterType::INTEGER)
                        ->setParameter('maxThreads', $maxThreads, \Doctrine\DBAL\ParameterType::INTEGER);
            }
        }

        if (!empty($limit)) {
            $q->setFirstResult(0)
                ->setMaxResults($limit);
        }

        return $q;
    }

    /**
     * @param int        $emailId
     * @param int[]|null $variantIds
     * @param int[]|null $listIds
     * @param bool       $countOnly
     * @param int|null   $limit
     * @param int|null   $minContactId
     * @param int|null   $maxContactId
     * @param bool       $countWithMaxMin
     *
     * @return array|int
     */
    public function getEmailPendingLeads(
        $emailId,
        $variantIds = null,
        $listIds = null,
        $countOnly = false,
        $limit = null,
        $minContactId = null,
        $maxContactId = null,
        $countWithMaxMin = false,
        int $maxThreads = null,
        int $threadId = null,
    ) {
        $q = $this->getEmailPendingQuery(
            $emailId,
            $variantIds,
            $listIds,
            $countOnly,
            $limit,
            $minContactId,
            $maxContactId,
            $countWithMaxMin,
            null,
            $maxThreads,
            $threadId
        );

        if (!($q instanceof QueryBuilder)) {
            return $q;
        }

        $results = $q->executeQuery()->fetchAllAssociative();

        if ($countOnly && $countWithMaxMin) {
            // returns array in format ['count' => #, ['min_id' => #, 'max_id' => #]]
            return $results[0];
        } elseif ($countOnly) {
            return (isset($results[0])) ? $results[0]['count'] : 0;
        } else {
            $leads = [];
            foreach ($results as $r) {
                $leads[$r['id']] = $r;
            }

            return $leads;
        }
    }

    /**
     * @param string|array<int|string> $search
     * @param int                      $limit
     * @param int                      $start
     * @param bool                     $viewOther
     * @param bool                     $topLevel
     * @param string|null              $emailType
     * @param int|null                 $variantParentId
     *
     * @return array
     */
    public function getEmailList($search = '', $limit = 10, $start = 0, $viewOther = false, $topLevel = false, $emailType = null, array $ignoreIds = [], $variantParentId = null)
    {
        $q = $this->createQueryBuilder('e');
        $q->select('partial e.{id, subject, name, language}');

        if (!empty($search)) {
            if (is_array($search)) {
                $search = array_map('intval', $search);
                $q->andWhere($q->expr()->in('e.id', ':search'))
                    ->setParameter('search', $search);
            } else {
                $q->andWhere($q->expr()->like('e.name', ':search'))
                    ->setParameter('search', "%{$search}%");
            }
        }

        if (!$viewOther) {
            $q->andWhere($q->expr()->eq('e.createdBy', ':id'))
                ->setParameter('id', $this->currentUser->getId());
        }

        if ($topLevel) {
            if (true === $topLevel || 'variant' == $topLevel) {
                $q->andWhere($q->expr()->isNull('e.variantParent'));
            } elseif ('translation' == $topLevel) {
                $q->andWhere($q->expr()->isNull('e.translationParent'));
            }
        }

        if ($variantParentId) {
            $q->andWhere(
                $q->expr()->andX(
                    $q->expr()->eq('IDENTITY(e.variantParent)', (int) $variantParentId),
                    $q->expr()->eq('e.id', (int) $variantParentId)
                )
            );
        }

        if (!empty($ignoreIds)) {
            $q->andWhere($q->expr()->notIn('e.id', ':emailIds'))
                ->setParameter('emailIds', $ignoreIds);
        }

        if (!empty($emailType)) {
            $q->andWhere(
                $q->expr()->eq('e.emailType', $q->expr()->literal($emailType))
            );
        }

        $q->orderBy('e.name');

        if (!empty($limit)) {
            $q->setFirstResult($start)
                ->setMaxResults($limit);
        }

        return $q->getQuery()->getArrayResult();
    }

    /**
     * @return array<string, int>
     */
    public function getSentReadNotReadCount(QueryBuilder $queryBuilder): array
    {
        $queryBuilder->resetQueryPart('groupBy');
        $queryBuilder->resetQueryParts(['join']);

        $queryBuilder->select('SUM( e.sent_count) as sent_count, SUM( e.read_count) as read_count');
        $results = $queryBuilder->executeQuery()->fetchAssociative();

        if ($results) {
            $results['sent_count'] = (int) $results['sent_count'];
            $results['read_count'] = (int) $results['read_count'];
            $results['not_read']   = $results['sent_count'] - $results['read_count'];
        } else {
            $results             = [];
            $results['not_read'] = $results['sent_count'] = $results['read_count'] = 0;
        }

        return $results;
    }

    public function getUnsubscribedCount(QueryBuilder $queryBuilder): int
    {
        $queryBuilder->resetQueryParts(['join']);
        $this->addDNCTableForEmails($queryBuilder);
        $queryBuilder->select('e.id as email_id, dnc.lead_id');
        $queryBuilder->andWhere('dnc.reason='.DoNotContact::UNSUBSCRIBED);

        return $queryBuilder->executeQuery()->rowCount();
    }

    public function getUniqueClicks(QueryBuilder $queryBuilder): int
    {
        $this->addTrackableTablesForEmailStats($queryBuilder);
        $queryBuilder->select('SUM( tr.unique_hits) as `unique_clicks`');

        return (int) $queryBuilder->executeQuery()->fetchOne();
    }

    private function addTrackableTablesForEmailStats(QueryBuilder $qb): void
    {
        $trTable = MAUTIC_TABLE_PREFIX.'channel_url_trackables';
        $prTable = MAUTIC_TABLE_PREFIX.'page_redirects';

        if (!$this->isJoined($qb, $trTable, self::EMAILS_PREFIX, self::TRACKABLE_PREFIX)) {
            $qb->leftJoin(
                self::EMAILS_PREFIX,
                $trTable,
                self::TRACKABLE_PREFIX,
                'e.id = tr.channel_id AND tr.channel = \'email\''
            );
        }
        if (!$this->isJoined($qb, $prTable, self::TRACKABLE_PREFIX, self::REDIRECT_PREFIX)) {
            $qb->leftJoin(
                self::TRACKABLE_PREFIX,
                $prTable,
                self::REDIRECT_PREFIX,
                'tr.redirect_id = pr.id'
            );
        }
    }

    /**
     * Add the Do Not Contact table to the query builder.
     */
    private function addDNCTableForEmails(QueryBuilder $qb): void
    {
        $table = MAUTIC_TABLE_PREFIX.'lead_donotcontact';

        if (!$this->isJoined($qb, $table, self::EMAILS_PREFIX, self::DNC_PREFIX)) {
            $qb->leftJoin(
                self::EMAILS_PREFIX,
                $table,
                self::DNC_PREFIX,
                'e.id = dnc.channel_id AND dnc.channel=\'email\''
            );
        }
    }

    private function isJoined(QueryBuilder $query, string $table, string $fromAlias, string $alias): bool
    {
        $joins = $query->getQueryParts()['join'][$fromAlias] ?? null;

        if (empty($joins)) {
            return false;
        }

        foreach ($joins[$fromAlias] as $join) {
            if ($join['joinTable'] == $table && $join['joinAlias'] == $alias) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param \Doctrine\ORM\QueryBuilder|QueryBuilder $q
     * @param object                                  $filter
     */
    protected function addCatchAllWhereClause($q, $filter): array
    {
        return $this->addStandardCatchAllWhereClause($q, $filter, [
            'e.name',
            'e.subject',
        ]);
    }

    /**
     * @param \Doctrine\ORM\QueryBuilder|QueryBuilder $q
     * @param object                                  $filter
     */
    protected function addSearchCommandWhereClause($q, $filter): array
    {
        [$expr, $parameters] = $this->addStandardSearchCommandWhereClause($q, $filter);
        if ($expr) {
            return [$expr, $parameters];
        }

        $command         = $filter->command;
        $unique          = $this->generateRandomParameterName();
        $returnParameter = false; // returning a parameter that is not used will lead to a Doctrine error

        switch ($command) {
            case $this->translator->trans('mautic.core.searchcommand.lang'):
                $langUnique      = $this->generateRandomParameterName();
                $langValue       = $filter->string.'_%';
                $forceParameters = [
                    $langUnique => $langValue,
                    $unique     => $filter->string,
                ];
                $expr = $q->expr()->or(
                    $q->expr()->eq('e.language', ":$unique"),
                    $q->expr()->like('e.language', ":$langUnique")
                );
                $returnParameter = true;
                break;
        }

        if ($expr && $filter->not) {
            $expr = $q->expr()->not($expr);
        }

        if (!empty($forceParameters)) {
            $parameters = $forceParameters;
        } elseif ($returnParameter) {
            $string     = ($filter->strict) ? $filter->string : "%{$filter->string}%";
            $parameters = ["$unique" => $string];
        }

        return [$expr, $parameters];
    }

    /**
     * @return string[]
     */
    public function getSearchCommands(): array
    {
        $commands = [
            'mautic.core.searchcommand.ispublished',
            'mautic.core.searchcommand.isunpublished',
            'mautic.core.searchcommand.isuncategorized',
            'mautic.core.searchcommand.ismine',
            'mautic.core.searchcommand.category',
            'mautic.core.searchcommand.lang',
        ];

        return array_merge($commands, parent::getSearchCommands());
    }

    /**
     * @return array<array<string>>
     */
    protected function getDefaultOrder(): array
    {
        return [
            ['e.name', 'ASC'],
        ];
    }

    public function getTableAlias(): string
    {
        return 'e';
    }

    /**
     * Resets variant_start_date, variant_read_count, variant_sent_count.
     *
     * @param string[]|string|int $relatedIds
     * @param string              $date
     */
    public function resetVariants($relatedIds, $date): void
    {
        if (!is_array($relatedIds)) {
            $relatedIds = [(string) $relatedIds];
        }

        $qb = $this->getEntityManager()->getConnection()->createQueryBuilder();

        $qb->update(MAUTIC_TABLE_PREFIX.'emails')
            ->set('variant_read_count', 0)
            ->set('variant_sent_count', 0)
            ->set('variant_start_date', ':date')
            ->setParameter('date', $date)
            ->where(
                $qb->expr()->in('id', $relatedIds)
            )
            ->executeStatement();
    }

    /**
     * Up the read/sent counts.
     *
     * @deprecated use upCountSent or incrementRead method
     *
     * @param int        $id
     * @param string     $type
     * @param int        $increaseBy
     * @param bool|false $variant
     */
    public function upCount($id, $type = 'sent', $increaseBy = 1, $variant = false): void
    {
        if (!$increaseBy) {
            return;
        }

        $q = $this->getEntityManager()->getConnection()->createQueryBuilder();

        $q->update(MAUTIC_TABLE_PREFIX.'emails');
        $q->set($type.'_count', $type.'_count + '.(int) $increaseBy);
        $q->where('id = '.(int) $id);

        if ($variant) {
            $q->set('variant_'.$type.'_count', 'variant_'.$type.'_count + '.(int) $increaseBy);
        }

        // Try to execute 3 times before throwing the exception
        // to increase the chance the update will do its stuff.
        $retrialLimit = 3;
        while ($retrialLimit >= 0) {
            try {
                $q->executeStatement();

                return;
            } catch (Exception $e) {
                --$retrialLimit;
                if (0 === $retrialLimit) {
                    throw $e;
                }
            }
        }
    }

    public function upCountSent(int $id, int $increaseBy = 1, bool $variant = false): void
    {
        if ($increaseBy <= 0) {
            return;
        }

        $connection  = $this->getEntityManager()->getConnection();
        $updateQuery = $connection->createQueryBuilder()
            ->update(MAUTIC_TABLE_PREFIX.'emails')
            ->set('sent_count', 'sent_count + :increaseBy')
            ->where('id = :id');

        if ($variant) {
            $updateQuery->set('variant_sent_count', 'variant_sent_count + :increaseBy');
        }

        $updateQuery
            ->setParameter('increaseBy', $increaseBy)
            ->setParameter('id', $id);

        // Try to execute 3 times before throwing the exception
        $retrialLimit = 3;
        while ($retrialLimit >= 0) {
            try {
                $updateQuery->executeStatement();

                return;
            } catch (Exception $e) {
                --$retrialLimit;
                if (0 === $retrialLimit) {
                    throw $e;
                }
            }
        }
    }

    public function incrementRead(int $emailId, string $statId, bool $isVariant = false): void
    {
        $q = $this->getEntityManager()->getConnection()->createQueryBuilder();

        $subQuery = $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->select('es.email_id')
            ->from(MAUTIC_TABLE_PREFIX.'email_stats', 'es')
            ->where('es.id = :statId')
            ->andWhere('es.is_read = 1');

        $q->update(MAUTIC_TABLE_PREFIX.'emails', 'e')
            ->set('read_count', 'read_count + 1')
            ->where(
                $q->expr()->and(
                    $q->expr()->eq('e.id', ':emailId'),
                    $q->expr()->notIn('e.id', $subQuery->getSQL())
                )
            )
            ->setParameter('emailId', $emailId)
            ->setParameter('statId', $statId);

        if ($isVariant) {
            $q->set('variant_read_count', 'variant_read_count + 1');
        }

        // Try to execute 3 times before throwing the exception
        $retrialLimit = 3;
        while ($retrialLimit >= 0) {
            try {
                $q->executeStatement();

                return;
            } catch (Exception $e) {
                --$retrialLimit;
                if (0 === $retrialLimit) {
                    throw $e;
                }
            }
        }
    }

    /**
     * @depreacated The method is replaced by getPublishedBroadcastsIterable
     *
     * @param int|null $id
     */
    public function getPublishedBroadcasts($id = null): \Doctrine\ORM\Internal\Hydration\IterableResult
    {
        return $this->getPublishedBroadcastsQuery($id)->iterate();
    }

    /**
     * @return iterable<Email>
     */
    public function getPublishedBroadcastsIterable(?int $id = null): iterable
    {
        return $this->getPublishedBroadcastsQuery($id)->toIterable();
    }

    private function getPublishedBroadcastsQuery(?int $id = null): Query
    {
        $qb   = $this->createQueryBuilder($this->getTableAlias());
        $expr = $this->getPublishedByDateExpression($qb, null, true, true, false);

        $expr->add(
            $qb->expr()->eq($this->getTableAlias().'.emailType', $qb->expr()->literal('list'))
        );

        if (null !== $id && 0 !== $id) {
            $expr->add(
                $qb->expr()->eq($this->getTableAlias().'.id', (int) $id)
            );
        }
        $qb->where($expr);

        return $qb->getQuery();
    }

    /**
     * Set Max and/or Min ID where conditions to the query builder.
     *
     * @param string $column
     * @param int    $minContactId
     * @param int    $maxContactId
     */
    private function setMinMaxIds(QueryBuilder $q, $column, $minContactId, $maxContactId): QueryBuilder
    {
        if ($minContactId && is_numeric($minContactId)) {
            $q->andWhere($column.' >= :minContactId');
            $q->setParameter('minContactId', $minContactId);
        }

        if ($maxContactId && is_numeric($maxContactId)) {
            $q->andWhere($column.' <= :maxContactId');
            $q->setParameter('maxContactId', $maxContactId);
        }

        return $q;
    }

    /**
     * Is one of emails unpublished?
     *
     * @deprecated to be removed in 6.0 with no replacement
     */
    public function isOneUnpublished(array $ids): bool
    {
        $result = $this->getEntityManager()
            ->createQueryBuilder()
            ->select($this->getTableAlias().'.id')
            ->from(Email::class, $this->getTableAlias(), $this->getTableAlias().'.id')
            ->where($this->getTableAlias().'.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->andWhere('e.isPublished = 0')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return (bool) $result;
    }

    private function getCategoryUnsubscribedLeadsQuery(int $emailId): QueryBuilder
    {
        $qb = $this->getEntityManager()->getConnection()
            ->createQueryBuilder();

        return $qb->select('lc.lead_id')
            ->from(MAUTIC_TABLE_PREFIX.'lead_categories', 'lc')
            ->innerJoin('lc', MAUTIC_TABLE_PREFIX.'emails', 'e', 'e.category_id = lc.category_id')
            ->where($qb->expr()->eq('e.id', $emailId))
            ->andWhere('lc.manually_removed = 1');
    }

    private function getExcludedListQuery(int $emailId): ?QueryBuilder
    {
        $connection = $this->getEntityManager()
            ->getConnection();
        $excludedListIds = $connection->createQueryBuilder()
            ->select('eel.leadlist_id')
            ->from(MAUTIC_TABLE_PREFIX.'email_list_excluded', 'eel')
            ->where('eel.email_id = :emailId')
            ->setParameter('emailId', $emailId)
            ->executeQuery()
            ->fetchFirstColumn();

        if (!$excludedListIds) {
            return null;
        }

        $queryBuilder = $connection->createQueryBuilder();
        $queryBuilder->select('ll.lead_id')
            ->from(MAUTIC_TABLE_PREFIX.'lead_lists_leads', 'll')
            ->where($queryBuilder->expr()->in('ll.leadlist_id', $excludedListIds));

        return $queryBuilder;
    }
}
