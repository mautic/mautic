<?php

namespace Mautic\LeadBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Order;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Doctrine\DBAL\Query\QueryBuilder;
use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\CoreBundle\Helper\SearchStringHelper;
use Mautic\LeadBundle\Controller\ListController;
use Mautic\LeadBundle\Event\LeadBuildSearchEvent;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Segment\OperatorOptions;
use Mautic\LeadBundle\Segment\Query\QueryBuilder as SegmentQueryBuilder;
use Mautic\PointBundle\Model\TriggerModel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @extends CommonRepository<Lead>
 */
class LeadRepository extends CommonRepository implements CustomFieldRepositoryInterface
{
    use CustomFieldRepositoryTrait {
        prepareDbalFieldsForSave as defaultPrepareDbalFieldsForSave;
    }
    use ExpressionHelperTrait;
    use OperatorListTrait;

    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * @var array
     */
    private $availableSocialFields = [];

    /**
     * @var array
     */
    private $availableSearchFields = [];

    /**
     * Required to get the color based on a lead's points.
     *
     * @var TriggerModel
     */
    private $triggerModel;

    /**
     * @var ListLeadRepository
     */
    private $listLeadRepository;

    /**
     * Used by search functions to search social profiles.
     */
    public function setAvailableSocialFields(array $fields): void
    {
        $this->availableSocialFields = $fields;
    }

    /**
     * Used by search functions to search using aliases as commands.
     */
    public function setAvailableSearchFields(array $fields): void
    {
        $this->availableSearchFields = $fields;
    }

    /**
     * Sets trigger model.
     */
    public function setTriggerModel(TriggerModel $triggerModel): void
    {
        $this->triggerModel = $triggerModel;
    }

    public function setDispatcher(EventDispatcherInterface $dispatcher): void
    {
        $this->dispatcher = $dispatcher;
    }

    public function setListLeadRepository(ListLeadRepository $listLeadRepository): void
    {
        $this->listLeadRepository = $listLeadRepository;
    }

    /**
     * Get a list of leads based on field value.
     *
     * @param string          $field
     * @param string[]|string $value
     * @param ?int            $ignoreId
     * @param bool            $indexByColumn
     *
     * @return array
     */
    public function getLeadsByFieldValue($field, $value, $ignoreId = null, $indexByColumn = false)
    {
        $results = $this->getEntities([
            'qb'               => $this->buildQueryForGetLeadsByFieldValue($field, $value, $ignoreId),
            'ignore_paginator' => true,
        ]);

        if (!$indexByColumn) {
            return $results;
        }

        return array_combine(array_map(fn (Lead $lead) => $lead->getFieldValue($field), $results), $results);
    }

    /**
     * Builds the query for the getLeadsByFieldValue method.
     *
     * @internal
     *
     * @param string          $field
     * @param string[]|string $value
     * @param ?int            $ignoreId
     *
     * @return QueryBuilder
     */
    protected function buildQueryForGetLeadsByFieldValue($field, $value, $ignoreId = null)
    {
        $col = 'l.'.$field;

        $q = $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->select('l.id')
            ->from(MAUTIC_TABLE_PREFIX.'leads', 'l');

        if ($ignoreId) {
            $q->where('l.id != :ignoreId')
                ->setParameter('ignoreId', $ignoreId);
        }

        if (is_array($value)) {
            /**
             * Bind each value to specific named parameters.
             *
             * @see https://www.doctrine-project.org/projects/doctrine-orm/en/2.6/reference/query-builder.html#line-number-0a267d5a2c69797a7656aae33fcc140d16b0a566-72
             */
            $valueParams = [];
            for ($i = 0; $i < count($value); ++$i) {
                $valueParams[':'.$this->generateRandomParameterName()] = $value[$i];
            }

            $q->andWhere(
                $q->expr()->in($col, array_keys($valueParams))
            );

            foreach ($valueParams as $param => $value) {
                $q->setParameter(ltrim($param, ':'), $value);
            }

            return $q;
        }

        $q->andWhere("$col = :search")->setParameter('search', $value);

        return $q;
    }

    /**
     * @return Lead[]
     */
    public function getContactsByEmail($email)
    {
        $contacts = $this->getLeadsByFieldValue('email', $email);

        // Attempt to search for contacts without a + suffix
        if (empty($contacts) && preg_match('#^(.*?)\+(.*?)@(.*?)$#', $email, $parts)) {
            $email    = $parts[1].'@'.$parts[3];
            $contacts = $this->getLeadsByFieldValue('email', $email);
        }

        return $contacts;
    }

    /**
     * @param string[] $emails
     *
     * @return int[]|array
     */
    public function getContactIdsByEmails(array $emails): array
    {
        $result = $this->getEntityManager()
            ->createQuery("
                SELECT c.id
                FROM Mautic\LeadBundle\Entity\Lead c
                WHERE c.email IN (:emails)
            ")
            ->setParameter('emails', $emails, ArrayParameterType::STRING)
            ->getArrayResult();

        return array_map(
            fn ($row): int => (int) $row['id'],
            $result
        );
    }

    /**
     * Get a list of lead entities.
     *
     * @param int $leadId
     * @param int $limit
     *
     * @return array
     */
    public function getLeadsByUniqueFields($uniqueFieldsWithData, $leadId = null, $limit = null)
    {
        $results = $this->getLeadFieldsByUniqueFields($uniqueFieldsWithData, 'l.*', $leadId, $limit);

        // Collect the IDs
        $leads = [];
        foreach ($results as $r) {
            $leads[$r['id']] = $r;
        }

        // Get entities
        $q = $this->getEntityManager()->createQueryBuilder()
            ->select('l')
            ->from(Lead::class, 'l');

        $q->where(
            $q->expr()->in('l.id', ':ids')
        )
            ->setParameter('ids', array_keys($leads))
            ->orderBy('l.dateAdded', Order::Descending->value)
            ->addOrderBy('l.id', Order::Descending->value);
        $entities = $q->getQuery()
            ->getResult();

        /** @var Lead $lead */
        foreach ($entities as $lead) {
            $lead->setAvailableSocialFields($this->availableSocialFields);
            if (!empty($this->triggerModel)) {
                $lead->setColor($this->triggerModel->getColorForLeadPoints($lead->getPoints()));
            }

            $lead->setFields(
                $this->formatFieldValues($leads[$lead->getId()])
            );
        }

        return $entities;
    }

    /**
     * Get list of lead Ids by unique field data.
     *
     * @param iterable<mixed> $uniqueFieldsWithData is an array of columns & values to filter by
     * @param int|null        $leadId               is the current lead id. Added to query to skip and find other leads
     * @param int|null        $limit                Limit count of results to return
     *
     * @return array<array{id: string}>
     */
    public function getLeadIdsByUniqueFields($uniqueFieldsWithData, ?int $leadId = null, ?int $limit = null): array
    {
        return $this->getLeadFieldsByUniqueFields($uniqueFieldsWithData, 'l.id', $leadId, $limit);
    }

    /**
     * @param iterable<mixed> $uniqueFieldsWithData
     *
     * @return array<array<mixed>>
     */
    private function getLeadFieldsByUniqueFields($uniqueFieldsWithData, string $select, ?int $leadId = null, ?int $limit = null): array
    {
        $q = $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->select($select)
            ->from(MAUTIC_TABLE_PREFIX.'leads', 'l');

        foreach ($uniqueFieldsWithData as $col => $val) {
            $q->{$this->getUniqueIdentifiersWherePart()}("l.$col = :".$col)
                ->setParameter($col, $val);
        }

        // if we have a lead ID lets use it
        if ($leadId > 0) {
            // make sure that its not the id we already have
            $q->andWhere('l.id != '.$leadId);
        }

        if ($limit > 0) {
            $q->setMaxResults($limit);
        }

        return $q->executeQuery()->fetchAllAssociative();
    }

    /**
     * @param string $email
     * @param bool   $all   Set to true to return all matching lead id's
     *
     * @return array|null
     */
    public function getLeadByEmail($email, $all = false)
    {
        $q = $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->select('l.id')
            ->from(MAUTIC_TABLE_PREFIX.'leads', 'l')
            ->where('email = :search')
            ->setParameter('search', $email);

        $result = $q->executeQuery()->fetchAllAssociative();

        if (count($result)) {
            return $all ? $result : $result[0];
        } else {
            return;
        }
    }

    /**
     * Get leads by IP address.
     *
     * @param bool $byId
     *
     * @return array
     */
    public function getLeadsByIp($ip, $byId = false)
    {
        $q = $this->createQueryBuilder('l')
            ->leftJoin('l.ipAddresses', 'i');
        $col = ($byId) ? 'i.id' : 'i.ipAddress';
        $q->where($col.' = :ip')
            ->setParameter('ip', $ip)
            ->orderBy('l.dateAdded', Order::Descending->value);
        $results = $q->getQuery()->getResult();

        /** @var Lead $lead */
        foreach ($results as $lead) {
            $lead->setAvailableSocialFields($this->availableSocialFields);
        }

        return $results;
    }

    /**
     * @return array
     */
    public function getLead($id)
    {
        $fq = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $fq->select('l.*')
            ->from(MAUTIC_TABLE_PREFIX.'leads', 'l')
            ->where('l.id = '.$id);
        $results = $fq->executeQuery()->fetchAllAssociative();

        return $results[0] ?? [];
    }

    /**
     * @param int $id
     */
    public function getEntity($id = 0): ?Lead
    {
        try {
            $q = $this->createQueryBuilder($this->getTableAlias());
            if (is_array($id)) {
                $this->buildSelectClause($q, $id);
                $contactId = (int) $id['id'];
            } else {
                $q->select('l, u, i')
                    ->leftJoin('l.ipAddresses', 'i')
                    ->leftJoin('l.owner', 'u');
                $contactId = $id;
            }
            $q->andWhere($this->getTableAlias().'.id = :id')
                ->setParameter('id', (int) $contactId);
            $entity = $q->getQuery()->getSingleResult();
        } catch (\Exception) {
            $entity = null;
        }

        if (null === $entity) {
            return $entity;
        }

        if ($entity->getFields()) {
            // Pulled from Doctrine memory so don't make unnecessary queries as this has already happened
            return $entity;
        }

        if (!empty($this->triggerModel)) {
            $entity->setColor($this->triggerModel->getColorForLeadPoints($entity->getPoints()));
        }

        $fieldValues = $this->getFieldValues($id);
        $entity->setFields($fieldValues);

        $entity->setAvailableSocialFields($this->availableSocialFields);

        return $entity;
    }

    /**
     * Get a contact entity with the primary company data populated.
     *
     * The primary company data will be a flat array on the entity
     * with a key of `primaryCompany`
     *
     * @param mixed $entity
     *
     * @return mixed|null
     */
    public function getEntityWithPrimaryCompany($entity)
    {
        if (is_int($entity)) {
            $entity = $this->getEntity($entity);
        }

        if ($entity instanceof Lead) {
            $id        = $entity->getId();
            $companies = $this->getEntityManager()->getRepository(Company::class)->getCompaniesForContacts([$id]);

            if (!empty($companies[$id])) {
                $primary = null;

                foreach ($companies as $company) {
                    if (isset($company['is_primary']) && 1 == $company['is_primary']) {
                        $primary = $company;
                    }
                }

                if (empty($primary)) {
                    $primary = $companies[$id][0];
                }

                $entity->setPrimaryCompany($primary);
            }
        }

        return $entity;
    }

    /**
     * Get a list of leads.
     *
     * @return array
     */
    public function getEntities(array $args = [])
    {
        $contacts = $this->getEntitiesWithCustomFields(
            'lead',
            $args,
            function ($r): void {
                if (!empty($this->triggerModel)) {
                    $r->setColor($this->triggerModel->getColorForLeadPoints($r->getPoints()));
                }
                $r->setAvailableSocialFields($this->availableSocialFields);
            }
        );

        $contactCount = isset($contacts['results']) ? count($contacts['results']) : count($contacts);
        if ($contactCount && (!empty($args['withPrimaryCompany']) || !empty($args['withChannelRules']))) {
            $withTotalCount = (array_key_exists('withTotalCount', $args) && $args['withTotalCount']);
            /** @var Lead[] $tmpContacts */
            $tmpContacts = ($withTotalCount) ? $contacts['results'] : $contacts;

            $withCompanies   = !empty($args['withPrimaryCompany']);
            $withPreferences = !empty($args['withChannelRules']);
            $contactIds      = array_keys($tmpContacts);

            if ($withCompanies) {
                $companies = $this->getEntityManager()->getRepository(Company::class)->getCompaniesForContacts($contactIds);
            }

            if ($withPreferences) {
                /** @var FrequencyRuleRepository $frequencyRepo */
                $frequencyRepo  = $this->getEntityManager()->getRepository(FrequencyRule::class);
                $frequencyRules = $frequencyRepo->getFrequencyRules(null, $contactIds);

                /** @var DoNotContactRepository $dncRepository */
                $dncRepository = $this->getEntityManager()->getRepository(DoNotContact::class);
                $dncRules      = $dncRepository->getChannelList(null, $contactIds);
            }

            foreach ($contactIds as $id) {
                if ($withCompanies && isset($companies[$id]) && !empty($companies[$id])) {
                    $primary = null;

                    // Try to find the primary company
                    foreach ($companies[$id] as $company) {
                        if (1 == $company['is_primary']) {
                            $primary = $company;
                        }
                    }

                    // If no primary was found, just grab the first
                    if (empty($primary)) {
                        $primary = $companies[$id][0];
                    }

                    if (is_array($tmpContacts[$id])) {
                        $tmpContacts[$id]['primaryCompany'] = $primary;
                    } elseif ($tmpContacts[$id] instanceof Lead) {
                        $tmpContacts[$id]->setPrimaryCompany($primary);
                    }
                }

                if ($withPreferences) {
                    $contactFrequencyRules = $frequencyRules[$id] ?? [];
                    $contactDncRules       = $dncRules[$id] ?? [];

                    $channelRules = Lead::generateChannelRules($contactFrequencyRules, $contactDncRules);
                    if (is_array($tmpContacts[$id])) {
                        $tmpContacts[$id]['channelRules'] = $channelRules;
                    } elseif ($tmpContacts[$id] instanceof Lead) {
                        $tmpContacts[$id]->setChannelRules($channelRules);
                    }
                }
            }

            if ($withTotalCount) {
                $contacts['results'] = $tmpContacts;
            } else {
                $contacts = $tmpContacts;
            }
        }

        return $contacts;
    }

    public function getFieldGroups(): array
    {
        return ['core', 'social', 'personal', 'professional'];
    }

    /**
     * @return QueryBuilder
     */
    public function getEntitiesDbalQueryBuilder()
    {
        $alias = $this->getTableAlias();

        return (new SegmentQueryBuilder($this->getEntityManager()->getConnection()))
            ->from(MAUTIC_TABLE_PREFIX.'leads', $alias);
    }

    /**
     * @param mixed[] $args
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getEntitiesOrmQueryBuilder($order, array $args=[])
    {
        $alias           = $this->getTableAlias();
        $select          = [$alias, 'u', $order];
        $q               = $this->getEntityManager()->createQueryBuilder();
        $joinIpAddresses = $args['joinIpAddresses'] ?? true;

        if ($joinIpAddresses) {
            $select[] = 'i';
        }

        $q->select($select)
            ->from(Lead::class, $alias, $alias.'.id')
            ->leftJoin($alias.'.owner', 'u')
            ->indexBy($alias, $alias.'.id');

        if ($joinIpAddresses) {
            $q->leftJoin($alias.'.ipAddresses', 'i');
        }

        return $q;
    }

    /**
     * Get contacts for a specific channel entity.
     *
     * @param array      $args             same as getEntity/getEntities
     * @param string     $joinTable
     * @param int        $entityId
     * @param array      $filters
     * @param string     $entityColumnName
     * @param array|null $additionalJoins  [ ['type' => 'join|leftJoin', 'from_alias' => '', 'table' => '', 'condition' => ''], ... ]
     */
    public function getEntityContacts(
        $args,
        $joinTable,
        $entityId,
        $filters = [],
        $entityColumnName = 'id',
        array $additionalJoins = null,
        $contactColumnName = 'lead_id',
        \DateTimeInterface $dateFrom = null,
        \DateTimeInterface $dateTo = null,
    ): array {
        $qb = $this->getEntitiesDbalQueryBuilder();

        if (empty($contactColumnName)) {
            $contactColumnName = 'lead_id';
        }

        $joinCondition = $qb->expr()->and(
            $qb->expr()->eq($this->getTableAlias().'.id', 'entity.'.$contactColumnName)
        );

        if ($entityId && $entityColumnName) {
            $joinCondition = $joinCondition->with(
                $qb->expr()->eq("entity.{$entityColumnName}", (int) $entityId)
            );
        }

        if (!empty($joinTable)) {
            $qb->join(
                $this->getTableAlias(),
                MAUTIC_TABLE_PREFIX.$joinTable,
                'entity',
                $joinCondition
            );
        }

        if (is_array($additionalJoins)) {
            foreach ($additionalJoins as $t) {
                $qb->{$t['type']}(
                    $t['from_alias'],
                    MAUTIC_TABLE_PREFIX.$t['table'],
                    $t['alias'],
                    $t['condition']
                );
            }
        }

        if ($filters) {
            $expr = null;
            foreach ($filters as $column => $value) {
                if (is_array($value)) {
                    $this->buildWhereClauseFromArray($qb, [$value]);
                } else {
                    if (!str_contains($column, '.')) {
                        $column = "entity.$column";
                    }
                    if (null === $expr) {
                        $expr = CompositeExpression::and($qb->expr()->eq($column, $qb->createNamedParameter($value)));
                        $qb->andWhere($expr);
                        continue;
                    }
                    $expr = $expr->with(
                        $qb->expr()->eq($column, $qb->createNamedParameter($value))
                    );
                    $qb->andWhere($expr);
                }
            }
        }

        $args['qb']    = $qb;
        $args['count'] = (ListController::ROUTE_SEGMENT_CONTACTS == $args['route']) ? $this->listLeadRepository->getContactsCountBySegment($entityId, $filters) : null;

        if ($dateFrom && $dateTo) {
            $qb->andWhere('entity.date_added BETWEEN FROM_UNIXTIME(:dateFrom) AND FROM_UNIXTIME(:dateTo)')
                ->setParameter('dateFrom', $dateFrom->getTimestamp(), \PDO::PARAM_INT)
                ->setParameter('dateTo', $dateTo->getTimestamp(), \PDO::PARAM_INT);
        }

        return $this->getEntities($args);
    }

    /**
     * Adds the "catch all" where clause to the QueryBuilder.
     *
     * @param \Doctrine\ORM\QueryBuilder|QueryBuilder $q
     */
    protected function addCatchAllWhereClause($q, $filter): array
    {
        $customFields       = $this->getSearchableFieldAliases($this->getEntityManager()->getRepository(LeadField::class), 'lead');
        $availableForSearch = array_map(fn ($alias) => 'l.'.$alias, $customFields);

        $columns = array_merge(
            [
                'l.firstname',
                'l.lastname',
                'l.email',
                'l.company',
                'l.city',
                'l.state',
                'l.zipcode',
                'l.country',
            ],
            $this->availableSocialFields,
            $availableForSearch,
        );

        return $this->addStandardCatchAllWhereClause($q, $filter, $columns);
    }

    /**
     * Adds the command where clause to the QueryBuilder.
     *
     * @param QueryBuilder $q
     */
    protected function addSearchCommandWhereClause($q, $filter): array
    {
        $command             = $filter->command;
        $string              = $filter->string;
        $unique              = $this->generateRandomParameterName();
        $returnParameter     = false; // returning a parameter that is not used will lead to a Doctrine error
        [$expr, $parameters] = parent::addSearchCommandWhereClause($q, $filter);

        // DBAL QueryBuilder does not have an expr()->not() function; boo!!

        // This will be switched by some commands that use join tables as NOT EXISTS queries will be used
        $exprType = ($filter->not) ? 'negate_expr' : 'expr';

        $operators = OperatorOptions::getFilterExpressionFunctions();
        $operators = array_merge($operators, [
            'null' => [
                'expr'        => 'isNull',
                'negate_expr' => 'isNotNull',
            ],
        ]);

        $innerJoinTables = (isset($this->advancedFilterCommands[$command])
            && SearchStringHelper::COMMAND_NEGATE !== $this->advancedFilterCommands[$command]);
        $likeExpr = $operators['like'][$exprType];
        $eqExpr   = $operators['='][$exprType];
        $nullExpr = $operators['null'][$exprType];
        $inExpr   = $operators['in'][$exprType];
        switch ($command) {
            case $this->translator->trans('mautic.lead.lead.searchcommand.isanonymous'):
            case $this->translator->trans('mautic.lead.lead.searchcommand.isanonymous', [], null, 'en_US'):
                $expr = $q->expr()->$nullExpr('l.date_identified');
                break;
            case $this->translator->trans('mautic.core.searchcommand.ismine'):
            case $this->translator->trans('mautic.core.searchcommand.ismine', [], null, 'en_US'):
                $expr = $q->expr()->$eqExpr('l.owner_id', $this->currentUser->getId());
                break;
            case $this->translator->trans('mautic.lead.lead.searchcommand.isunowned'):
            case $this->translator->trans('mautic.lead.lead.searchcommand.isunowned', [], null, 'en_US'):
                $expr = $q->expr()->or(
                    $q->expr()->$eqExpr('l.owner_id', 0),
                    $q->expr()->$nullExpr('l.owner_id')
                );
                break;
            case $this->translator->trans('mautic.lead.lead.searchcommand.owner'):
            case $this->translator->trans('mautic.lead.lead.searchcommand.owner', [], null, 'en_US'):
                $q->leftJoin($this->getTableAlias(), MAUTIC_TABLE_PREFIX.'users', 'u', "u.id = {$this->getTableAlias()}.owner_id");
                $expr = $q->expr()->or(
                    $q->expr()->$likeExpr('u.first_name', ':'.$unique),
                    $q->expr()->$likeExpr('u.last_name', ':'.$unique)
                );
                $returnParameter = true;
                break;
            case $this->translator->trans('mautic.core.searchcommand.name'):
            case $this->translator->trans('mautic.core.searchcommand.name', [], null, 'en_US'):
                $expr = $q->expr()->or(
                    $q->expr()->$likeExpr('l.firstname', ":$unique"),
                    $q->expr()->$likeExpr('l.lastname', ":$unique")
                );
                $returnParameter = true;
                break;
            case $this->translator->trans('mautic.core.searchcommand.email'):
            case $this->translator->trans('mautic.core.searchcommand.email', [], null, 'en_US'):
                $expr            = $q->expr()->$likeExpr('l.email', ":$unique");
                $returnParameter = true;
                break;
            case $this->translator->trans('mautic.lead.lead.searchcommand.list'):
            case $this->translator->trans('mautic.lead.lead.searchcommand.list', [], null, 'en_US'):
                $sq = $this->getEntityManager()->getConnection()->createQueryBuilder();
                $sq->select('1')
                    ->from(MAUTIC_TABLE_PREFIX.'lead_lists_leads', 'lla')
                    ->where(
                        $q->expr()->and(
                            $q->expr()->eq('l.id', 'lla.lead_id'),
                            $q->expr()->eq('lla.manually_removed', 0),
                            $q->expr()->in('lla.leadlist_id', ":$unique")
                        )
                    );
                $from = $q->getQueryPart('from')[0];
                $q->resetQueryPart('from');
                $q->add('from', ['hint' => 'USE INDEX FOR JOIN ('.MAUTIC_TABLE_PREFIX.'lead_date_added)'] + $from, true);

                $filter->strict  = true;
                $q->andWhere($q->expr()->{$filter->not ? 'notExists' : 'exists'}($sq->getSQL()));
                $q->setParameter($unique, $this->getListIdsByAlias($string) ?: [0], ArrayParameterType::INTEGER);
                break;
            case $this->translator->trans('mautic.lead.lead.searchcommand.company_id'):
            case $this->translator->trans('mautic.lead.lead.searchcommand.company_id', [], null, 'en_US'):
                $this->applySearchQueryRelationship(
                    $q,
                    [
                        [
                            'from_alias' => 'l',
                            'table'      => 'companies_leads',
                            'alias'      => 'comp_lead',
                            'condition'  => 'l.id = comp_lead.lead_id',
                        ],
                    ],
                    $innerJoinTables,
                    $this->generateFilterExpression($q, 'comp_lead.company_id', $eqExpr, $unique, null)
                );
                $filter->strict  = true;
                $returnParameter = true;

                break;
            case $this->translator->trans('mautic.core.searchcommand.ip'):
            case $this->translator->trans('mautic.core.searchcommand.ip', [], null, 'en_US'):
                $this->applySearchQueryRelationship(
                    $q,
                    [
                        [
                            'from_alias' => 'l',
                            'table'      => 'lead_ips_xref',
                            'alias'      => 'ip_lead',
                            'condition'  => 'l.id = ip_lead.lead_id',
                        ],
                        [
                            'from_alias' => 'ip_lead',
                            'table'      => 'ip_addresses',
                            'alias'      => 'ip',
                            'condition'  => 'ip_lead.ip_id = ip.id',
                        ],
                    ],
                    $innerJoinTables,
                    $this->generateFilterExpression($q, 'ip.ip_address', $likeExpr, $unique, null)
                );
                $returnParameter = true;

                break;
            case $this->translator->trans('mautic.lead.lead.searchcommand.duplicate'):
            case $this->translator->trans('mautic.lead.lead.searchcommand.duplicate', [], null, 'en_US'):
                $prateek  = explode('+', $string);
                $imploder = [];

                foreach ($prateek as $value) {
                    $list       = $this->getEntityManager()->getRepository(LeadList::class)->findOneByAlias($value);
                    $imploder[] = ((!empty($list)) ? (int) $list->getId() : 0);
                }

                // logic. In query, Sum(manually_removed) should be less than the current)
                $pluck    = count($imploder);
                $imploder = (string) implode(',', $imploder);

                $sq = $this->getEntityManager()->getConnection()->createQueryBuilder();
                $sq->select('duplicate.lead_id')
                    ->from(MAUTIC_TABLE_PREFIX.'lead_lists_leads', 'duplicate')
                    ->where(
                        $q->expr()->and(
                            $q->expr()->in('duplicate.leadlist_id', $imploder),
                            $q->expr()->eq('duplicate.manually_removed', 0)
                        )
                    )
                    ->groupBy('duplicate.lead_id')
                    ->having("COUNT(duplicate.lead_id) = $pluck");

                $expr            = $q->expr()->$inExpr('l.id', sprintf('(%s)', $sq->getSQL()));
                $returnParameter = true;

                break;
            case $this->translator->trans('mautic.lead.lead.searchcommand.tag'):
            case $this->translator->trans('mautic.lead.lead.searchcommand.tag', [], null, 'en_US'):
                $this->applySearchQueryRelationship(
                    $q,
                    [
                        [
                            'from_alias' => 'l',
                            'table'      => 'lead_tags_xref',
                            'alias'      => 'xtag',
                            'condition'  => 'l.id = xtag.lead_id',
                        ],
                        [
                            'from_alias' => 'xtag',
                            'table'      => 'lead_tags',
                            'alias'      => 'tag',
                            'condition'  => 'xtag.tag_id = tag.id',
                        ],
                    ],
                    $innerJoinTables,
                    $this->generateFilterExpression($q, 'tag.tag', $likeExpr, $unique, null)
                );
                $returnParameter = true;
                break;
            case $this->translator->trans('mautic.lead.lead.searchcommand.company'):
            case $this->translator->trans('mautic.lead.lead.searchcommand.company', [], null, 'en_US'):
                $this->applySearchQueryRelationship(
                    $q,
                    [
                        [
                            'from_alias' => 'l',
                            'table'      => 'companies_leads',
                            'alias'      => 'comp_lead',
                            'condition'  => 'l.id = comp_lead.lead_id',
                        ],
                        [
                            'from_alias' => 'comp_lead',
                            'table'      => 'companies',
                            'alias'      => 'comp',
                            'condition'  => 'comp_lead.company_id = comp.id',
                        ],
                    ],
                    $innerJoinTables,
                    $this->generateFilterExpression($q, 'comp.companyname', $likeExpr, $unique, null)
                );
                $returnParameter = true;
                break;
            case $this->translator->trans('mautic.lead.lead.searchcommand.stage'):
            case $this->translator->trans('mautic.lead.lead.searchcommand.stage', [], null, 'en_US'):
                $this->applySearchQueryRelationship(
                    $q,
                    [
                        [
                            'from_alias' => 'l',
                            'table'      => 'stages',
                            'alias'      => 's',
                            'condition'  => 'l.stage_id = s.id',
                        ],
                    ],
                    $innerJoinTables,
                    $this->generateFilterExpression($q, 's.name', $likeExpr, $unique, null)
                );
                $returnParameter = true;
                break;
            default:
                if (in_array($command, $this->availableSearchFields)) {
                    $expr = $q->expr()->$likeExpr("l.$command", ":$unique");
                }
                $returnParameter = true;
                break;
        }

        if ($this->dispatcher) {
            $event = new LeadBuildSearchEvent($filter->string, $filter->command, $unique, $filter->not, $q);
            $this->dispatcher->dispatch($event, LeadEvents::LEAD_BUILD_SEARCH_COMMANDS);
            if ($event->isSearchDone()) {
                $returnParameter = $event->getReturnParameters();
                $filter->strict  = $event->getStrict();
                $expr            = $event->getSubQuery();
                $parameters      = array_merge($parameters, $event->getParameters());
            }
        }

        if ($returnParameter) {
            $string              = ($filter->strict) ? $filter->string : "{$filter->string}%";
            $parameters[$unique] = $string;
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
            'mautic.lead.lead.searchcommand.isanonymous',
            'mautic.core.searchcommand.ismine',
            'mautic.lead.lead.searchcommand.isunowned',
            'mautic.lead.lead.searchcommand.list',
            'mautic.core.searchcommand.name',
            'mautic.lead.lead.searchcommand.company',
            'mautic.lead.lead.searchcommand.company_id',
            'mautic.core.searchcommand.email',
            'mautic.lead.lead.searchcommand.owner',
            'mautic.core.searchcommand.ip',
            'mautic.lead.lead.searchcommand.tag',
            'mautic.lead.lead.searchcommand.stage',
            'mautic.lead.lead.searchcommand.duplicate',
            'mautic.lead.lead.searchcommand.email_sent',
            'mautic.lead.lead.searchcommand.email_read',
            'mautic.lead.lead.searchcommand.email_queued',
            'mautic.lead.lead.searchcommand.email_pending',
            'mautic.lead.lead.searchcommand.page_source',
            'mautic.lead.lead.searchcommand.page_source_id',
            'mautic.lead.lead.searchcommand.import_id',
            'mautic.lead.lead.searchcommand.import_action',
            'mautic.lead.lead.searchcommand.page_id',
            'mautic.lead.lead.searchcommand.sms_sent',
            'mautic.lead.lead.searchcommand.web_sent',
            'mautic.lead.lead.searchcommand.mobile_sent',
        ];

        if (!empty($this->availableSearchFields)) {
            $commands = array_merge($commands, $this->availableSearchFields);
        }

        return array_merge($commands, parent::getSearchCommands());
    }

    /**
     * Returns the array of columns with the default order.
     */
    protected function getDefaultOrder(): array
    {
        return [
            ['l.last_active', 'DESC'],
        ];
    }

    /**
     * Updates lead's lastActive with now date/time.
     *
     * @param int $leadId
     */
    public function updateLastActive($leadId, ?\DateTimeInterface $lastActiveDate = null): void
    {
        if (!$leadId) {
            // Prevent unnecessary queries like:
            // `UPDATE leads SET last_active = ... WHERE id IS NULL`
            return;
        }

        $dt     = new DateTimeHelper($lastActiveDate ?? '');
        $fields = ['last_active' => $dt->toUtcString()];

        $this->getEntityManager()->getConnection()->update(MAUTIC_TABLE_PREFIX.'leads', $fields, ['id' => $leadId]);
    }

    /**
     * Gets the ID of the latest ID.
     *
     * @return int
     */
    public function getMaxLeadId()
    {
        $result = $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->select('max(id) as max_lead_id')
            ->from(MAUTIC_TABLE_PREFIX.'leads', 'l')
            ->executeQuery()
            ->fetchAllAssociative();

        return $result[0]['max_lead_id'];
    }

    /**
     * Gets names, signature and email of the user(lead owner).
     *
     * @param int $ownerId
     *
     * @return array|false
     */
    public function getLeadOwner($ownerId)
    {
        if (!$ownerId) {
            return false;
        }

        $q = $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->select('u.id, u.first_name, u.last_name, u.email, u.position, u.signature')
            ->from(MAUTIC_TABLE_PREFIX.'users', 'u')
            ->where('u.id = :ownerId')
            ->setParameter('ownerId', (int) $ownerId);

        $result = $q->executeQuery()->fetchAssociative();

        // Fix the HTML markup
        if (is_array($result)) {
            foreach ($result as &$field) {
                $field = is_string($field) ? html_entity_decode($field) : $field;
            }
        }

        return $result;
    }

    /**
     * Check Lead segments by ids.
     *
     * @param array<int> $stages
     */
    public function isContactInOneOfStages(Lead $lead, array $stages = []): bool
    {
        if (empty($stages)) {
            return false;
        }

        $q = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $q->select('s.id')
            ->from(MAUTIC_TABLE_PREFIX.'stages', 's');
        $q->join('s', MAUTIC_TABLE_PREFIX.'leads', 'l', 'l.stage_id = s.id')
            ->where(
                $q->expr()->and(
                    $q->expr()->in('s.id', ':stageIds'),
                    $q->expr()->eq('l.id', ':leadId')
                )
            )
            ->setParameter('stageIds', $stages, ArrayParameterType::INTEGER)
            ->setParameter('leadId', $lead->getId());

        return (bool) $q->executeQuery()->fetchOne();
    }

    /**
     * Check lead owner.
     *
     * @param array $ownerIds
     */
    public function checkLeadOwner(Lead $lead, $ownerIds = []): bool
    {
        if (empty($ownerIds)) {
            return false;
        }

        $q = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $q->select('u.id')
            ->from(MAUTIC_TABLE_PREFIX.'users', 'u')
            ->join('u', MAUTIC_TABLE_PREFIX.'leads', 'l', 'l.owner_id = u.id')
            ->where(
                $q->expr()->and(
                    $q->expr()->in('u.id', ':ownerIds'),
                    $q->expr()->eq('l.id', ':leadId')
                )
            )
            ->setParameter('ownerIds', implode(',', $ownerIds))
            ->setParameter('leadId', $lead->getId());

        return (bool) $q->executeQuery()->fetchOne();
    }

    public function getContacts(array $contactIds): array
    {
        $qb = $this->getEntityManager()->getConnection()->createQueryBuilder();

        $qb->select('l.*')->from(MAUTIC_TABLE_PREFIX.'leads', 'l')
            ->where(
                $qb->expr()->in('l.id', $contactIds)
            );

        $results = $qb->executeQuery()->fetchAllAssociative();

        if ($results) {
            $contacts = [];
            foreach ($results as $result) {
                $contacts[$result['id']] = $result;
            }

            return $contacts;
        }

        return [];
    }

    /**
     * @return ArrayCollection<int, Lead>
     */
    public function getContactCollection(array $ids): ArrayCollection
    {
        if (empty($ids)) {
            return new ArrayCollection();
        }

        $contacts = $this->getEntities(
            [
                'filter'             => [
                    'force' => [
                        [
                            'column' => 'l.id',
                            'expr'   => 'in',
                            'value'  => $ids,
                        ],
                    ],
                ],
                'orderBy'            => 'l.id',
                'orderByDir'         => 'asc',
                'withPrimaryCompany' => true,
                'withChannelRules'   => true,
            ]
        );

        return new ArrayCollection($contacts);
    }

    public function getTableAlias(): string
    {
        return 'l';
    }

    /**
     * Get the count of identified contacts.
     */
    public function getIdentifiedContactCount(): int
    {
        $qb = $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->select('count(*)')
            ->from($this->getTableName(), $this->getTableAlias());

        $qb->where(
            $qb->expr()->isNotNull($this->getTableAlias().'.date_identified')
        );

        return (int) $qb->executeQuery()->fetchOne();
    }

    /**
     * @param string[] $uniqueFields
     */
    public function getContactCountWithDuplicateValues(array $uniqueFields): int
    {
        $sql = $this->buildDuplicateValuesQuery($uniqueFields);
        $qb  = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $qb->select('count(*)')->from(sprintf('(%s)', $sql), 'sub');

        return (int) $qb->executeQuery()->fetchOne();
    }

    /**
     * @param string[] $uniqueFields
     *
     * @return string[]
     */
    public function getDuplicatedContactIds(array $uniqueFields): array
    {
        return $this->getEntityManager()->getConnection()->fetchFirstColumn(
            $this->buildDuplicateValuesQuery($uniqueFields)
        );
    }

    /**
     * Get the next contact after an specific ID; mainly used in deduplication.
     */
    public function getNextIdentifiedContact($lastId): ?Lead
    {
        $alias = $this->getTableAlias();
        $qb    = $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->select("$alias.id")
            ->from($this->getTableName(), $this->getTableAlias());

        $qb->where(
            $qb->expr()->and(
                $qb->expr()->gt("$alias.id", (int) $lastId),
                $qb->expr()->isNotNull("$alias.date_identified")
            )
        )
            ->orderBy("$alias.id")
            ->setMaxResults(1);

        $next = $qb->executeQuery()->fetchOne();

        return ($next) ? $this->getEntity($next) : null;
    }

    /**
     * @param array $tables          $tables[0] should be primary table
     * @param bool  $innerJoinTables
     * @param mixed $whereExpression
     * @param mixed $having
     */
    public function applySearchQueryRelationship(QueryBuilder $q, array $tables, $innerJoinTables, $whereExpression = null, $having = null): void
    {
        $primaryTable = $tables[0];
        unset($tables[0]);

        $joinType = ($innerJoinTables) ? 'join' : 'leftJoin';

        $this->useDistinctCount = true;
        if (!preg_match('/"'.preg_quote($primaryTable['alias'], '/').'"/i', json_encode($q->getQueryPart('join')))) {
            $q->$joinType(
                $primaryTable['from_alias'],
                MAUTIC_TABLE_PREFIX.$primaryTable['table'],
                $primaryTable['alias'],
                $primaryTable['condition']
            );
        }
        foreach ($tables as $table) {
            $exists = false;
            $joins  = $q->getQueryPart('join');

            if (isset($joins[$table['from_alias']])) {
                foreach ($joins[$table['from_alias']] as $standingJoin) {
                    if ($standingJoin['joinAlias'] === $table['alias']) { // There can be just one alias
                        $exists = true;
                        break;
                    }
                }
            }

            if (!$exists) {
                $q->$joinType(
                    $table['from_alias'],
                    MAUTIC_TABLE_PREFIX.$table['table'],
                    $table['alias'],
                    $table['condition']
                );
            }
        }

        if ($whereExpression) {
            $q->andWhere($whereExpression);
        }

        if ($having) {
            $q->andHaving($having);
        }
        $q->groupBy('l.id');
    }

    /**
     * @param int $tries
     */
    protected function updateContactPoints(array $changes, $id, $tries = 1): int
    {
        $qb = $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->update(MAUTIC_TABLE_PREFIX.'leads')
            ->where('id = '.$id);

        $ph = 0;
        // Keep operator in same order as was used in Lead::adjustPoints() in order to be congruent with what was calculated in PHP
        // Again ignoring Aunt Sally here (PEMDAS)
        foreach ($changes as $operator => $points) {
            $qb->set('points', 'points '.$operator.' :points'.$ph)
                ->setParameter('points'.$ph, $points, \PDO::PARAM_INT);

            ++$ph;
        }

        try {
            $qb->executeStatement();
        } catch (DriverException $exception) {
            $message = $exception->getMessage();

            if (str_contains($message, 'Deadlock') && $tries <= 3) {
                ++$tries;

                $this->updateContactPoints($changes, $id, $tries);
            }
        }

        // Query new points
        return (int) $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->select('l.points')
            ->from(MAUTIC_TABLE_PREFIX.'leads', 'l')
            ->where('l.id = '.$id)
            ->executeQuery()
            ->fetchOne();
    }

    /**
     * @param Lead $entity
     */
    protected function postSaveEntity($entity)
    {
        // Check if points need to be appended
        if ($entity->getPointChanges()) {
            $newPoints = $this->updateContactPoints($entity->getPointChanges(), $entity->getId());

            // Set actual points so that code using getPoints knows the true value
            $entity->setActualPoints($newPoints);

            $changes = $entity->getChanges();

            if (isset($changes['points'])) {
                // Let's adjust the points to be more accurate in the change log
                $changes['points'][1] = $newPoints;
                $entity->setChanges($changes);
            }
        }
    }

    protected function prepareDbalFieldsForSave(&$fields)
    {
        // Do not save points as they are handled by postSaveEntity
        unset($fields['points']);

        $this->defaultPrepareDbalFieldsForSave($fields);
    }

    /**
     * @param string[] $uniqueFields
     */
    private function buildDuplicateValuesQuery(array $uniqueFields): string
    {
        $fieldsAliases = array_map(fn ($uniqueField) => $this->getTableAlias().'.'.$uniqueField, $uniqueFields);

        if ($this->uniqueIdentifiersOperatorIs(CompositeExpression::TYPE_AND)) {
            return $this->getDuplicateValuesQuery($fieldsAliases)->getSQL();
        }

        $queries = array_map(
            fn ($fieldAlias) => $this->getDuplicateValuesQuery([$fieldAlias])->getSQL(),
            $fieldsAliases
        );

        $unionQueries = implode(' UNION ', $queries);

        return $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->select('minId')
            ->from("({$unionQueries})", 'duplicate_values')
            ->groupBy('minId');
    }

    /**
     * @param string[] $fieldsAliases
     */
    private function getDuplicateValuesQuery(array $fieldsAliases): QueryBuilder
    {
        $qb = $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->select(array_merge(["MIN({$this->getTableAlias()}.id) as minId"], $fieldsAliases))
            ->from($this->getTableName(), $this->getTableAlias());

        $andWhere = [$qb->expr()->isNotNull($this->getTableAlias().'.date_identified')];

        foreach ($fieldsAliases as $field) {
            $andWhere[] = $qb->expr()->isNotNull($field);
        }

        $qb->where($qb->expr()->and(...$andWhere));
        $qb->groupBy($fieldsAliases);
        $qb->having('count(*) > 1');

        return $qb;
    }

    /**
     * @return string[]
     */
    private function getListIdsByAlias(string $alias): array
    {
        return $this->getEntityManager()
            ->getConnection()
            ->createQueryBuilder()
            ->select('list.id')
            ->from(MAUTIC_TABLE_PREFIX.'lead_lists', 'list')
            ->where('list.alias = :alias')
            ->setParameter('alias', $alias)
            ->executeQuery()
            ->fetchFirstColumn();
    }
}
