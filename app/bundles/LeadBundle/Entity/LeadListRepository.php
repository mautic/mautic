<?php

namespace Mautic\LeadBundle\Entity;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\Query\ResultSetMapping;
use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @extends CommonRepository<LeadList>
 */
class LeadListRepository extends CommonRepository
{
    use OperatorListTrait; // @deprecated to be removed in Mautic 3. Not used inside this class.

    use ExpressionHelperTrait;
    use RegexTrait;

    /**
     * @var bool
     */
    protected $listFiltersInnerJoinCompany = false;

    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * Flag to check if some segment filter on a company field exists.
     *
     * @var bool
     */
    protected $hasCompanyFilter = false;

    /**
     * @var \Doctrine\DBAL\Schema\Column[]
     */
    protected $leadTableSchema;

    /**
     * @var \Doctrine\DBAL\Schema\Column[]
     */
    protected $companyTableSchema;

    /**
     * @param int $id
     */
    public function getEntity($id = 0): ?LeadList
    {
        try {
            return $this
                ->createQueryBuilder('l')
                ->where('l.id = :listId')
                ->setParameter('listId', $id)
                ->getQuery()
                ->getSingleResult();
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * Get a list of lists.
     *
     * @param bool   $user
     * @param string $alias
     * @param string $id
     *
     * @return array
     */
    public function getLists(?User $user = null, $alias = '', $id = '')
    {
        $q = $this->getEntityManager()->createQueryBuilder()
            ->from(LeadList::class, 'l', 'l.id');

        $q->select('partial l.{id, name, alias}')
            ->andWhere($q->expr()->eq('l.isPublished', ':true'))
            ->setParameter('true', true, 'boolean');

        if ($user) {
            $q->andWhere($q->expr()->eq('l.isGlobal', ':true'));
            $q->orWhere('l.createdBy = :user');
            $q->setParameter('user', $user->getId());
        }

        if (!empty($alias)) {
            $q->andWhere('l.alias = :alias');
            $q->setParameter('alias', $alias);
        }

        if (!empty($id)) {
            $q->andWhere(
                $q->expr()->neq('l.id', $id)
            );
        }

        $q->orderBy('l.name');

        return $q->getQuery()->getArrayResult();
    }

    /**
     * Get lists for a specific lead.
     *
     * @param int|Lead[] $lead                 Lead ID or array of Leads
     * @param bool       $forList
     * @param bool       $singleArrayHydration
     * @param bool       $isPublic
     *
     * @return mixed
     */
    public function getLeadLists($lead, $forList = false, $singleArrayHydration = false, $isPublic = false, $isPreferenceCenter = false)
    {
        if (is_array($lead)) {
            $q = $this->getEntityManager()->createQueryBuilder()
                ->from(LeadList::class, 'l', 'l.id');

            if ($forList) {
                $q->select('partial l.{id, alias, name}, partial il.{lead, list, dateAdded, manuallyAdded, manuallyRemoved}');
            } else {
                $q->select('l, partial lead.{id}');
            }

            $q->leftJoin('l.leads', 'il')
                ->leftJoin('il.lead', 'lead');

            $q->where(
                $q->expr()->andX(
                    $q->expr()->in('lead.id', ':leads'),
                    $q->expr()->in('il.manuallyRemoved', ':false')
                )
            )
                ->setParameter('leads', $lead)
                ->setParameter('false', false, 'boolean');

            if ($isPublic) {
                $q->andWhere($q->expr()->eq('l.isGlobal', ':isPublic'))
                    ->setParameter('isPublic', true, 'boolean');
            }
            if ($isPreferenceCenter) {
                $q->andWhere($q->expr()->eq('l.isPreferenceCenter', ':isPreferenceCenter'))
                    ->setParameter('isPreferenceCenter', true, 'boolean');
            }
            $result = $q->getQuery()->getArrayResult();
            $return = [];
            foreach ($result as $r) {
                foreach ($r['leads'] as $l) {
                    $return[$l['lead_id']][$r['id']] = $r;
                }
            }

            return $return;
        } else {
            $q = $this->getEntityManager()->createQueryBuilder()
                ->from(LeadList::class, 'l', 'l.id');

            if ($forList) {
                $q->select('partial l.{id, alias, name}, partial il.{lead, list, dateAdded, manuallyAdded, manuallyRemoved}');
            } else {
                $q->select('l');
            }

            $q->leftJoin('l.leads', 'il');

            $q->where(
                $q->expr()->andX(
                    $q->expr()->eq('IDENTITY(il.lead)', (int) $lead),
                    $q->expr()->in('il.manuallyRemoved', ':false')
                )
            )
                ->setParameter('false', false, 'boolean');

            if ($isPublic) {
                $q->andWhere($q->expr()->eq('l.isGlobal', ':isPublic'))
                    ->setParameter('isPublic', true, 'boolean');
            }

            if ($isPreferenceCenter) {
                $q->andWhere($q->expr()->eq('l.isPreferenceCenter', ':isPreferenceCenter'))
                    ->setParameter('isPreferenceCenter', true, 'boolean');
            }

            return ($singleArrayHydration) ? $q->getQuery()->getArrayResult() : $q->getQuery()->getResult();
        }
    }

    /**
     * Check Lead segments by ids.
     */
    public function checkLeadSegmentsByIds(Lead $lead, $ids): bool
    {
        if (empty($ids)) {
            return false;
        }

        $qb = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $qb->select('ll.leadlist_id')
            ->from(MAUTIC_TABLE_PREFIX.'lead_lists_leads', 'll')
            ->where(
                $qb->expr()->and(
                    $qb->expr()->in('ll.leadlist_id', ':ids'),
                    $qb->expr()->eq('ll.lead_id', ':leadId'),
                    $qb->expr()->eq('ll.manually_removed', 0)
                )
            )
            ->setParameter('leadId', $lead->getId())
            ->setParameter('ids', $ids, ArrayParameterType::INTEGER);

        return (bool) $qb->executeQuery()->fetchOne();
    }

    /**
     * Return a list of global lists.
     *
     * @return array
     */
    public function getGlobalLists()
    {
        $q = $this->getEntityManager()->createQueryBuilder()
            ->from(LeadList::class, 'l', 'l.id');

        $q->select('partial l.{id, name, alias}')
            ->where($q->expr()->eq('l.isPublished', 'true'))
            ->setParameter('true', true, 'boolean')
            ->andWhere($q->expr()->eq('l.isGlobal', ':true'))
            ->orderBy('l.name');

        return $q->getQuery()->getArrayResult();
    }

    /**
     * Return a list of global lists.
     *
     * @return array
     */
    public function getPreferenceCenterList()
    {
        $q = $this->getEntityManager()->createQueryBuilder()
            ->from(LeadList::class, 'l', 'l.id');

        $q->select('partial l.{id, name, publicName, alias}')
            ->where($q->expr()->eq('l.isPublished', 'true'))
            ->setParameter('true', true, 'boolean')
            ->andWhere($q->expr()->eq('l.isPreferenceCenter', ':true'))
            ->orderBy('l.name');

        return $q->getQuery()->getArrayResult();
    }

    /**
     * Get a count of leads that belong to the list.
     *
     * @param int|int[] $listIds
     *
     * @return array|int
     *
     * @throws \Exception
     */
    public function getLeadCount($listIds)
    {
        if (!is_array($listIds)) {
            $listIds = [$listIds];
        }

        $q = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $q->select('count(l.lead_id) as thecount, l.leadlist_id')
            ->from(MAUTIC_TABLE_PREFIX.'lead_lists_leads', 'l');

        $countListIds = count($listIds);

        if (1 === $countListIds) {
            $q          = $this->forceUseIndex($q, MAUTIC_TABLE_PREFIX.'manually_removed');
            $expression = $q->expr()->eq('l.leadlist_id', $listIds[0]);
        } else {
            $expression = $q->expr()->in('l.leadlist_id', $listIds);
        }

        $q->where(
            $expression,
            $q->expr()->eq('l.manually_removed', ':false')
        )
            ->setParameter('false', false, 'boolean')
            ->groupBy('l.leadlist_id');

        $result = $q->executeQuery()->fetchAllAssociative();

        $return = [];
        foreach ($result as $r) {
            $return[$r['leadlist_id']] = (int) $r['thecount'];
        }

        // Ensure lists without leads have a value
        foreach ($listIds as $l) {
            if (!isset($return[$l])) {
                $return[$l] = 0;
            }
        }

        return (1 === $countListIds) ? $return[$listIds[0]] : $return;
    }

    private function forceUseIndex(QueryBuilder $qb, string $indexName): QueryBuilder
    {
        $fromPart             = $qb->getQueryPart('from');
        $fromPart[0]['alias'] = sprintf('%s USE INDEX (%s)', $fromPart[0]['alias'], $indexName);
        $qb->resetQueryPart('from');
        $qb->from($fromPart[0]['table'], $fromPart[0]['alias']);

        return $qb;
    }

    public function arrangeFilters($filters): array
    {
        $objectFilters = [];
        if (empty($filters)) {
            $objectFilters['lead'][] = $filters;
        }
        foreach ($filters as $filter) {
            $object = $filter['object'] ?? 'lead';
            switch ($object) {
                case 'company':
                    $objectFilters['company'][] = $filter;
                    break;
                default:
                    $objectFilters['lead'][] = $filter;
                    break;
            }
        }

        return $objectFilters;
    }

    public function setDispatcher(EventDispatcherInterface $dispatcher): void
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * @return QueryBuilder
     */
    protected function createFilterExpressionSubQuery($table, $alias, $column, $value, array &$parameters, $leadId = null, array $subQueryFilters = [])
    {
        $subQb   = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $subExpr = [];

        foreach ($subQueryFilters as $subColumn => $subParameter) {
            $subExpr[] = $subQb->expr()->eq($subColumn, ":$subParameter");
        }

        if ('leads' !== $table) {
            $subExpr[] = $subQb->expr()->eq($alias.'.lead_id', 'l.id');
        }

        // Specific lead
        if (!empty($leadId)) {
            $columnName = ('leads' === $table) ? 'id' : 'lead_id';
            $subExpr[]  = $subQb->expr()->eq($alias.'.'.$columnName, $leadId);
        }

        if (null !== $value && !empty($column)) {
            $subFilterParamter = $this->generateRandomParameterName();
            $subFunc           = 'eq';
            if (is_array($value)) {
                $subFunc                        = 'in';
                $subExpr[]                      = $subQb->expr()->in(sprintf('%s.%s', $alias, $column), ":$subFilterParamter");
                $parameters[$subFilterParamter] = ['value' => $value, 'type' => ArrayParameterType::STRING];
            } else {
                $parameters[$subFilterParamter] = $value;
            }

            $subExpr = $subQb->expr()->$subFunc(sprintf('%s.%s', $alias, $column), ":$subFilterParamter");
        }

        $subQb->expr()->and(...$subExpr);

        $subQb->select('null')
            ->from(MAUTIC_TABLE_PREFIX.$table, $alias)
            ->where($subExpr);

        return $subQb;
    }

    /**
     * @param \Doctrine\ORM\QueryBuilder|QueryBuilder $q
     */
    protected function addCatchAllWhereClause($q, $filter): array
    {
        return $this->addStandardCatchAllWhereClause(
            $q,
            $filter,
            [
                'l.name',
                'l.alias',
            ]
        );
    }

    /**
     * @param \Doctrine\ORM\QueryBuilder|QueryBuilder $q
     */
    protected function addSearchCommandWhereClause($q, $filter): array
    {
        [$expr, $parameters] = parent::addStandardSearchCommandWhereClause($q, $filter);
        if ($expr) {
            return [$expr, $parameters];
        }

        $command         = $filter->command;
        $unique          = $this->generateRandomParameterName();
        $returnParameter = false; // returning a parameter that is not used will lead to a Doctrine error

        switch ($command) {
            case $this->translator->trans('mautic.lead.list.searchcommand.isglobal'):
            case $this->translator->trans('mautic.lead.list.searchcommand.isglobal', [], null, 'en_US'):
                $expr            = $q->expr()->eq('l.isGlobal', ":$unique");
                $forceParameters = [$unique => true];
                break;
            case $this->translator->trans('mautic.core.searchcommand.name'):
            case $this->translator->trans('mautic.core.searchcommand.name', [], null, 'en_US'):
                $expr            = $q->expr()->like('l.name', ':'.$unique);
                $returnParameter = true;
                break;
        }

        if (!empty($forceParameters)) {
            $parameters = $forceParameters;
        } elseif ($returnParameter) {
            $string     = ($filter->strict) ? $filter->string : "%{$filter->string}%";
            $parameters = ["$unique" => $string];
        }

        return [
            $expr,
            $parameters,
        ];
    }

    /**
     * @return string[]
     */
    public function getSearchCommands(): array
    {
        $commands = [
            'mautic.lead.list.searchcommand.isglobal',
            'mautic.core.searchcommand.ispublished',
            'mautic.core.searchcommand.isunpublished',
            'mautic.core.searchcommand.name',
            'mautic.core.searchcommand.ismine',
            'mautic.core.searchcommand.category',
        ];

        return array_merge($commands, parent::getSearchCommands());
    }

    public function getRelativeDateStrings(): array
    {
        $keys = self::getRelativeDateTranslationKeys();

        $strings = [];
        foreach ($keys as $key) {
            $strings[$key] = $this->translator->trans($key);
        }

        return $strings;
    }

    public static function getRelativeDateTranslationKeys(): array
    {
        return [
            'mautic.lead.list.month_last',
            'mautic.lead.list.month_next',
            'mautic.lead.list.month_this',
            'mautic.lead.list.today',
            'mautic.lead.list.tomorrow',
            'mautic.lead.list.yesterday',
            'mautic.lead.list.week_last',
            'mautic.lead.list.week_next',
            'mautic.lead.list.week_this',
            'mautic.lead.list.year_last',
            'mautic.lead.list.year_next',
            'mautic.lead.list.year_this',
            'mautic.lead.list.anniversary',
        ];
    }

    /**
     * @return array<array<string>>
     */
    protected function getDefaultOrder(): array
    {
        return [
            ['l.name', 'ASC'],
        ];
    }

    public function getTableAlias(): string
    {
        return 'l';
    }

    public function leadListExists(int $id): bool
    {
        $tableName = MAUTIC_TABLE_PREFIX.'lead_lists';
        $result    = (int) $this->getEntityManager()->getConnection()
            ->executeQuery("SELECT EXISTS(SELECT 1 FROM {$tableName} WHERE id = {$id})")
            ->fetchOne();

        return 1 === $result;
    }

    /**
     * Returns array of campaigns related to this segment.
     *
     * @return array<int, string>
     */
    public function getSegmentCampaigns(int $segmentId): array
    {
        $q = $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->select('clx.campaign_id, c.name')
            ->distinct()
            ->from(MAUTIC_TABLE_PREFIX.'campaign_leadlist_xref', 'clx')
            ->join('clx', MAUTIC_TABLE_PREFIX.'campaigns', 'c', 'c.id = clx.campaign_id');
        $q->where(
            $q->expr()->eq('clx.leadlist_id', $segmentId)
        );

        $lists   = [];
        $results = $q->executeQuery()->fetchAllAssociative();

        foreach ($results as $row) {
            $lists[$row['campaign_id']] = $row['name'];
        }

        return $lists;
    }

    public function isContactInAnySegment(int $contactId): bool
    {
        $tableName = MAUTIC_TABLE_PREFIX.'lead_lists_leads';

        $sql = <<<SQL
            SELECT leadlist_id 
            FROM $tableName
            WHERE lead_id = ?
                AND manually_removed = 0
            LIMIT 1
SQL;

        $segmentIds = $this->getEntityManager()->getConnection()
            ->executeQuery(
                $sql,
                [$contactId],
                [\PDO::PARAM_INT]
            )
            ->fetchFirstColumn();

        return !empty($segmentIds);
    }

    public function isNotContactInAnySegment(int $contactId): bool
    {
        return !$this->isContactInAnySegment($contactId);
    }

    /**
     * @param int[] $expectedSegmentIds
     */
    public function isContactInSegments(int $contactId, array $expectedSegmentIds): bool
    {
        $segmentIds = $this->fetchContactToSegmentIdsRelationships($contactId, $expectedSegmentIds);

        return !empty($segmentIds);
    }

    /**
     * @param int[] $expectedSegmentIds
     */
    public function isNotContactInSegments(int $contactId, array $expectedSegmentIds): bool
    {
        $segmentIds = $this->fetchContactToSegmentIdsRelationships($contactId, $expectedSegmentIds);

        if (empty($segmentIds)) {
            return true; // Contact is not associated wit any segment
        }

        foreach ($expectedSegmentIds as $expectedSegmentId) {
            if (in_array($expectedSegmentId, $segmentIds)) { // No exact type comparison used!
                return false;
            }
        }

        return true;
    }

    /**
     * @param int[] $expectedSegmentIds
     *
     * @return int[]
     */
    private function fetchContactToSegmentIdsRelationships(int $contactId, array $expectedSegmentIds): array
    {
        $tableName = MAUTIC_TABLE_PREFIX.'lead_lists_leads';

        $sql = <<<SQL
            SELECT leadlist_id 
            FROM $tableName
            WHERE lead_id = ?
                AND leadlist_id IN (?)
                AND manually_removed = 0
SQL;

        return $this->getEntityManager()->getConnection()
            ->executeQuery(
                $sql,
                [$contactId, $expectedSegmentIds],
                [
                    \PDO::PARAM_INT,
                    ArrayParameterType::INTEGER,
                ]
            )
            ->fetchFirstColumn();
    }

    /**
     * @return mixed[]
     */
    public function getAllSegments(): array
    {
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('title', 'title');
        $rsm->addScalarResult('item_id', 'item_id');
        $rsm->addScalarResult('is_published', 'is_published');
        $query = $this->getEntityManager()->createNativeQuery('SELECT 
            ll.name as title, 
            ll.id as item_id,
            ll.is_published as is_published
            FROM '.MAUTIC_TABLE_PREFIX.'lead_lists ll', $rsm);

        return $query->getResult();
    }

    /**
     * @return mixed[]
     */
    public function getCampaignEntryPoints(): array
    {
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('item_id', 'item_id');

        $query = $this->getEntityManager()->createNativeQuery('SELECT 
            leadlist_id as item_id
        FROM '.MAUTIC_TABLE_PREFIX.'campaign_leadlist_xref
            GROUP BY leadlist_id', $rsm);

        return $query->getResult();
    }

    /**
     * @return mixed[]
     */
    public function getEmailIncludeExcludeList(): array
    {
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('item_id', 'item_id');

        $query = $this->getEntityManager()->createNativeQuery('SELECT 
            leadlist_id as item_id
        FROM '.MAUTIC_TABLE_PREFIX.'email_list_xref 
            GROUP BY leadlist_id', $rsm);

        $included = $query->getResult();

        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('item_id', 'item_id');

        $query = $this->getEntityManager()->createNativeQuery('SELECT 
            leadlist_id as item_id
        FROM '.MAUTIC_TABLE_PREFIX.'email_list_excluded 
            GROUP BY leadlist_id', $rsm);

        $excluded = $query->getResult();

        return array_merge($included, $excluded);
    }

    /**
     * @return mixed[]
     */
    public function getCampaignChangeSegmentAction(): array
    {
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('properties', 'properties');

        $query = $this->getEntityManager()->createNativeQuery('SELECT 
            properties 
        FROM '.MAUTIC_TABLE_PREFIX.'campaign_events ce 
        WHERE ce.type = \'lead.changelist\'', $rsm);

        $segmentIds = [];
        foreach ($query->getResult() as $property) {
            $property       = unserialize($property['properties']);
            $segmentIds     = array_merge($property['addToLists'], $property['removeFromLists'], $segmentIds);
        }

        return array_map(fn ($segment) => ['item_id' => (string) $segment], $segmentIds);
    }

    /**
     * @return mixed[]
     */
    public function getFilterSegmentsAction(): array
    {
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('filters', 'filters');

        $query = $this->getEntityManager()->createNativeQuery('SELECT 
            filters 
        FROM '.MAUTIC_TABLE_PREFIX.'lead_lists', $rsm);

        $childSegmentIds = [];

        foreach ($query->getResult() as $rowFilters) {
            $segmentMembershipFilters = array_filter(
                unserialize($rowFilters['filters']),
                fn (array $filter) => 'leadlist' === $filter['type']
            );

            foreach ($segmentMembershipFilters as $filter) {
                if (is_array($filter['properties']['filter'])) {
                    foreach ($filter['properties']['filter'] as $childSegmentId) {
                        $childSegmentIds[] = ['item_id' => (string) $childSegmentId];
                    }
                }
            }
        }

        return $childSegmentIds;
    }

    /**
     * @return mixed[]
     */
    public function getLeadListLeads(): array
    {
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('item_id', 'item_id');

        $query = $this->getEntityManager()->createNativeQuery('SELECT 
            leadlist_id as item_id
        FROM '.MAUTIC_TABLE_PREFIX.'lead_lists_leads
            GROUP BY leadlist_id', $rsm);

        return $query->getResult();
    }

    /**
     * @return mixed[]
     */
    public function getNotificationIncludedList(): array
    {
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('item_id', 'item_id');

        $query = $this->getEntityManager()->createNativeQuery('SELECT 
            leadlist_id as item_id
        FROM '.MAUTIC_TABLE_PREFIX.'push_notification_list_xref
            GROUP BY leadlist_id', $rsm);

        return $query->getResult();
    }

    /**
     * @return mixed[]
     */
    public function getSMSIncludedList(): array
    {
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('item_id', 'item_id');

        $query = $this->getEntityManager()->createNativeQuery('SELECT 
            leadlist_id as item_id
        FROM '.MAUTIC_TABLE_PREFIX.'sms_message_list_xref
            GROUP BY leadlist_id', $rsm);

        return $query->getResult();
    }

    /**
     * @return mixed[]
     */
    public function getFormAction(): array
    {
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('properties', 'properties');

        $query = $this->getEntityManager()->createNativeQuery('SELECT 
            properties 
        FROM '.MAUTIC_TABLE_PREFIX.'form_actions fa 
        WHERE fa.type = \'lead.changelist\'', $rsm);

        $segmentIds = [];
        foreach ($query->getResult() as $property) {
            $property       = unserialize($property['properties']);
            $segmentIds     = array_merge($property['addToLists'], $property['removeFromLists'], $segmentIds);
        }

        return array_map(fn ($segment) => ['item_id' => (string) $segment], $segmentIds);
    }
}
