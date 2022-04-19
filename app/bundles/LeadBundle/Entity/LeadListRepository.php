<?php

namespace Mautic\LeadBundle\Entity;

use Doctrine\DBAL\Query\QueryBuilder;
use Exception;
use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

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
     * {@inheritdoc}
     *
     * @param int $id
     *
     * @return mixed|null
     */
    public function getEntity($id = 0)
    {
        try {
            $entity = $this
                ->createQueryBuilder('l')
                ->where('l.id = :listId')
                ->setParameter('listId', $id)
                ->getQuery()
                ->getSingleResult();
        } catch (Exception $e) {
            $entity = null;
        }

        return $entity;
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
     *
     * @param $ids
     *
     * @return bool
     */
    public function checkLeadSegmentsByIds(Lead $lead, $ids)
    {
        if (empty($ids)) {
            return false;
        }

        $q = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $q->select('l.id')
            ->from(MAUTIC_TABLE_PREFIX.'leads', 'l');
        $q->join('l', MAUTIC_TABLE_PREFIX.'lead_lists_leads', 'x', 'l.id = x.lead_id')
            ->where(
                $q->expr()->andX(
                    $q->expr()->in('x.leadlist_id', $ids),
                    $q->expr()->eq('l.id', ':leadId')
                )
            )
            ->setParameter('leadId', $lead->getId());

        return (bool) $q->execute()->fetchColumn();
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
            ->setParameter(':true', true, 'boolean')
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
            ->setParameter(':true', true, 'boolean')
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
     * @throws Exception
     */
    public function getLeadCount($listIds)
    {
        if (!(is_array($listIds))) {
            $listIds = [$listIds];
        }

        $q = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $q->select('count(l.lead_id) as thecount, l.leadlist_id')
            ->from(MAUTIC_TABLE_PREFIX.'lead_lists_leads', 'l');

        $expression   = null;
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

        $result = $q->execute()->fetchAll();

        $return = [];
        foreach ($result as $r) {
            $return[$r['leadlist_id']] = $r['thecount'];
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

    /**
     * @param $filters
     *
     * @return array
     */
    public function arrangeFilters($filters)
    {
        $objectFilters = [];
        if (empty($filters)) {
            $objectFilters['lead'][] = $filters;
        }
        foreach ($filters as $filter) {
            $object = (isset($filter['object'])) ? $filter['object'] : 'lead';
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

    public function setDispatcher(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param      $table
     * @param      $alias
     * @param      $column
     * @param      $value
     * @param null $leadId
     *
     * @return QueryBuilder
     */
    protected function createFilterExpressionSubQuery($table, $alias, $column, $value, array &$parameters, $leadId = null, array $subQueryFilters = [])
    {
        $subQb   = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $subExpr = $subQb->expr()->andX();

        if ('leads' !== $table) {
            $subExpr->add(
                $subQb->expr()->eq($alias.'.lead_id', 'l.id')
            );
        }

        // Specific lead
        if (!empty($leadId)) {
            $columnName = ('leads' === $table) ? 'id' : 'lead_id';
            $subExpr->add(
                $subQb->expr()->eq($alias.'.'.$columnName, $leadId)
            );
        }

        foreach ($subQueryFilters as $subColumn => $subParameter) {
            $subExpr->add(
                $subQb->expr()->eq($subColumn, ":$subParameter")
            );
        }

        if (null !== $value && !empty($column)) {
            $subFilterParamter = $this->generateRandomParameterName();
            $subFunc           = 'eq';
            if (is_array($value)) {
                $subFunc = 'in';
                $subExpr->add(
                    $subQb->expr()->in(sprintf('%s.%s', $alias, $column), ":$subFilterParamter")
                );
                $parameters[$subFilterParamter] = ['value' => $value, 'type' => \Doctrine\DBAL\Connection::PARAM_STR_ARRAY];
            } else {
                $parameters[$subFilterParamter] = $value;
            }

            $subExpr->add(
                $subQb->expr()->$subFunc(sprintf('%s.%s', $alias, $column), ":$subFilterParamter")
            );
        }

        $subQb->select('null')
            ->from(MAUTIC_TABLE_PREFIX.$table, $alias)
            ->where($subExpr);

        return $subQb;
    }

    /**
     * @param \Doctrine\ORM\QueryBuilder|\Doctrine\DBAL\Query\QueryBuilder $q
     * @param                                                              $filter
     *
     * @return array
     */
    protected function addCatchAllWhereClause($q, $filter)
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
     * @param \Doctrine\ORM\QueryBuilder|\Doctrine\DBAL\Query\QueryBuilder $q
     * @param                                                              $filter
     *
     * @return array
     */
    protected function addSearchCommandWhereClause($q, $filter)
    {
        [$expr, $parameters] = parent::addStandardSearchCommandWhereClause($q, $filter);
        if ($expr) {
            return [$expr, $parameters];
        }

        $command         = $filter->command;
        $unique          = $this->generateRandomParameterName();
        $returnParameter = false; //returning a parameter that is not used will lead to a Doctrine error

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
     * @return array
     */
    public function getSearchCommands()
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

    /**
     * @return array
     */
    public function getRelativeDateStrings()
    {
        $keys = self::getRelativeDateTranslationKeys();

        $strings = [];
        foreach ($keys as $key) {
            $strings[$key] = $this->translator->trans($key);
        }

        return $strings;
    }

    /**
     * @return array
     */
    public static function getRelativeDateTranslationKeys()
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
     * @return string
     */
    protected function getDefaultOrder()
    {
        return [
            ['l.name', 'ASC'],
        ];
    }

    /**
     * @return string
     */
    public function getTableAlias()
    {
        return 'l';
    }

    public function leadListExists(int $id): bool
    {
        $tableName = MAUTIC_TABLE_PREFIX.'lead_lists';
        $result    = (int) $this->getEntityManager()->getConnection()
            ->executeQuery("SELECT EXISTS(SELECT 1 FROM {$tableName} WHERE id = {$id})")
            ->fetchColumn();

        return 1 === $result;
    }
}
