<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Entity;

use Doctrine\DBAL\Query\QueryBuilder;
use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class LeadListRepository extends CommonRepository
{
    use OperatorListTrait;
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
        } catch (\Exception $e) {
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

        return  (bool) $q->execute()->fetchColumn();
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
     */
    public function getLeadCount($listIds)
    {
        $q = $this->getEntityManager()->getConnection()->createQueryBuilder();

        $q->select('count(l.lead_id) as thecount, l.leadlist_id')
            ->from(MAUTIC_TABLE_PREFIX.'lead_lists_leads', 'l');

        $returnArray = (is_array($listIds));

        if (!$returnArray) {
            $listIds = [$listIds];
        }

        $q->where(
            $q->expr()->in('l.leadlist_id', $listIds),
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

        return ($returnArray) ? $return : $return[$listIds[0]];
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
        list($expr, $parameters) = parent::addSearchCommandWhereClause($q, $filter);
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
            case $this->translator->trans('mautic.core.searchcommand.ispublished'):
            case $this->translator->trans('mautic.core.searchcommand.ispublished', [], null, 'en_US'):
                $expr            = $q->expr()->eq('l.isPublished', ":$unique");
                $forceParameters = [$unique => true];
                break;
            case $this->translator->trans('mautic.core.searchcommand.isunpublished'):
            case $this->translator->trans('mautic.core.searchcommand.isunpublished', [], null, 'en_US'):
                $expr            = $q->expr()->eq('l.isPublished', ":$unique");
                $forceParameters = [$unique => false];
                break;
            case $this->translator->trans('mautic.core.searchcommand.name'):
            case $this->translator->trans('mautic.core.searchcommand.name', [], null, 'en_US'):
                $expr            = $q->expr()->like('l.name', ':'.$unique);
                $returnParameter = true;
                break;
            case $this->translator->trans('mautic.core.searchcommand.ismine'):
            case $this->translator->trans('mautic.core.searchcommand.ismine', [], null, 'en_US'):
                $expr            = $q->expr()->eq('l.createdBy', ":$unique");
                $forceParameters = [$unique => $this->currentUser->getId()];
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

    /**
     * @throws DBALException,
     */
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
                [PDO::PARAM_INT]
            )
            ->fetch(FetchMode::COLUMN);

        return !empty($segmentIds);
    }

    /**
     * @throws DBALException,
     */
    public function isNotContactInAnySegment(int $contactId): bool
    {
        return !$this->isContactInAnySegment($contactId);
    }

    /**
     * @throws DBALException,
     */
    public function isContactInSegments(int $contactId, array $expectedSegmentIds): bool
    {
        $segmentIds = $this->fetchContactToSegmentIdsRelationships($contactId, $expectedSegmentIds);

        if (empty($segmentIds)) {
            return false; // Contact is not associated wit any segment
        }

        foreach ($expectedSegmentIds as $expectedSegmentId) {
            if (in_array($expectedSegmentId, $segmentIds)) {
                continue;
            }

            return false;
        }

        return true;
    }

    /**
     * @throws DBALException,
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
     * @return string[] Segment IDs as string in array
     *
     * @throws DBALException
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
                    PDO::PARAM_INT,
                    Connection::PARAM_INT_ARRAY,
                ]
            )
            ->fetchAll(FetchMode::COLUMN);
    }

    /**
     * @throws DBALException
     */
    public function leadListExists(int $id): bool
    {
        $tableName = MAUTIC_TABLE_PREFIX.'lead_lists';
        $result    = (int) $this->getEntityManager()->getConnection()
            ->executeQuery("SELECT EXISTS(SELECT 1 FROM {$tableName} WHERE id = {$id})")
            ->fetchColumn();

        return 1 === $result;
    }

    public function getExistingLeadListIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $qb = $this->createQueryBuilder('l');
        $qb->select('l.id')
            ->where(
                $qb->expr()->in('l.id', ':ids')
            )
            ->setParameter('ids', $ids, Connection::PARAM_INT_ARRAY);
        $result = $qb->getQuery()->getArrayResult();

        return array_column($result, 'id');
    }

    public function getSegmentsByFilter(string $filterKey, string $filterValue): array
    {
        $filteredSegment = [];
        $like            = '%;s:5:"field";s:'.mb_strlen($filterKey).":\"$filterKey\";%";
        $q               = $this->_em->getConnection()->createQueryBuilder();
        $q->select('l.id, l.name, l.filters')
            ->from(MAUTIC_TABLE_PREFIX.LeadList::TABLE_NAME, 'l')
            ->where(
                $q->expr()->like('l.filters', $q->expr()->literal($like))
            );
        $leadList = $q->execute()->fetchAll();
        foreach ($leadList as $segment) {
            $filters = unserialize($segment['filters']);
            foreach ($filters as $filter) {
                if (isset($filter['properties']['filter']) &&
                    $filter['field'] === $filterKey &&
                    in_array($filterValue, $filter['properties']['filter'])
                ) {
                    $filteredSegment[$segment['id']] = $segment['name'];
                    break;
                }
            }
        }

        return $filteredSegment;
    }
}
