<?php

namespace Mautic\LeadBundle\Entity;

use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Doctrine\ORM\QueryBuilder;
use Mautic\CoreBundle\Entity\CommonRepository;
use Mautic\LeadBundle\Event\CompanyBuildSearchEvent;
use Mautic\LeadBundle\LeadEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @extends CommonRepository<Company>
 */
class CompanyRepository extends CommonRepository implements CustomFieldRepositoryInterface
{
    use CustomFieldRepositoryTrait;

    /**
     * @var array
     */
    private $availableSearchFields = [];

    /**
     * @var EventDispatcherInterface|null
     */
    private $dispatcher;

    /**
     * Used by search functions to search using aliases as commands.
     */
    public function setAvailableSearchFields(array $fields): void
    {
        $this->availableSearchFields = $fields;
    }

    public function setDispatcher(EventDispatcherInterface $dispatcher): void
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param int $id
     */
    public function getEntity($id = 0): ?Company
    {
        try {
            $q = $this->createQueryBuilder($this->getTableAlias());
            if (is_array($id)) {
                $this->buildSelectClause($q, $id);
                $companyId = (int) $id['id'];
            } else {
                $companyId = $id;
            }
            $q->andWhere($this->getTableAlias().'.id = '.(int) $companyId);
            $entity = $q->getQuery()->getSingleResult();
        } catch (\Exception) {
            $entity = null;
        }

        if (null === $entity) {
            return null;
        }

        if ($entity->getFields()) {
            // Pulled from Doctrine memory so don't make unnecessary queries as this has already happened
            return $entity;
        }

        $fieldValues = $this->getFieldValues($id, true, 'company');
        $entity->setFields($fieldValues);

        return $entity;
    }

    /**
     * Get a list of leads.
     *
     * @return array
     */
    public function getEntities(array $args = [])
    {
        return $this->getEntitiesWithCustomFields('company', $args);
    }

    /**
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    public function getEntitiesDbalQueryBuilder()
    {
        return $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->from(MAUTIC_TABLE_PREFIX.'companies', $this->getTableAlias());
    }

    /**
     * @param mixed[] $args
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getEntitiesOrmQueryBuilder($order, array $args=[])
    {
        $q = $this->getEntityManager()->createQueryBuilder();
        $q->select($this->getTableAlias().','.$order)
            ->from(\Mautic\LeadBundle\Entity\Company::class, $this->getTableAlias(), $this->getTableAlias().'.id');

        return $q;
    }

    /**
     * Get the groups available for fields.
     */
    public function getFieldGroups(): array
    {
        return ['core', 'professional', 'other'];
    }

    /**
     * Get companies by lead.
     */
    public function getCompaniesByLeadId($leadId, $companyId = null): array
    {
        $q = $this->getEntityManager()->getConnection()->createQueryBuilder();

        $q->select('comp.id, comp.companyname, comp.companycity, comp.companycountry, cl.is_primary')
            ->from(MAUTIC_TABLE_PREFIX.'companies', 'comp')
            ->leftJoin('comp', MAUTIC_TABLE_PREFIX.'companies_leads', 'cl', 'cl.company_id = comp.id')
            ->where('cl.lead_id = :leadId')
            ->setParameter('leadId', $leadId)
            ->orderBy('cl.is_primary', 'DESC');

        if ($companyId) {
            $q->andWhere('comp.id = :companyId')->setParameter('companyId', $companyId);
        }

        return $q->executeQuery()->fetchAllAssociative();
    }

    public function getTableAlias(): string
    {
        return 'comp';
    }

    protected function addCatchAllWhereClause($q, $filter): array
    {
        return $this->addStandardCatchAllWhereClause(
            $q,
            $filter,
            [
                'comp.companyname',
                'comp.companyemail',
            ]
        );
    }

    protected function addSearchCommandWhereClause($q, $filter): array
    {
        [$expr, $parameters]     = $this->addStandardSearchCommandWhereClause($q, $filter);
        $unique                  = $this->generateRandomParameterName();
        $returnParameter         = true;
        $command                 = $filter->command;

        if (in_array($command, $this->availableSearchFields)) {
            $expr = $q->expr()->like($this->getTableAlias().".$command", ":$unique");
        }

        if ($this->dispatcher) {
            $event = new CompanyBuildSearchEvent($filter->string, $filter->command, $unique, $filter->not, $q);
            $this->dispatcher->dispatch($event, LeadEvents::COMPANY_BUILD_SEARCH_COMMANDS);
            if ($event->isSearchDone()) {
                $returnParameter = $event->getReturnParameters();
                $filter->strict  = $event->getStrict();
                $expr            = $event->getSubQuery();
                $parameters      = array_merge($parameters, $event->getParameters());
            }
        }

        if ($returnParameter) {
            $string              = ($filter->strict) ? $filter->string : "%{$filter->string}%";
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
        $commands = $this->getStandardSearchCommands();
        if (!empty($this->availableSearchFields)) {
            $commands = array_merge($commands, $this->availableSearchFields);
        }

        return $commands;
    }

    /**
     * @param bool   $user
     * @param string $id
     *
     * @return array|mixed
     */
    public function getCompanies($user = false, $id = '')
    {
        $q                = $this->_em->getConnection()->createQueryBuilder();
        static $companies = [];

        if ($user) {
            $user = $this->currentUser;
        }

        $key = (int) $id;
        if (isset($companies[$key])) {
            return $companies[$key];
        }

        $q->select('comp.*, cl.is_primary')
            ->from(MAUTIC_TABLE_PREFIX.'companies', 'comp')
            ->leftJoin('comp', MAUTIC_TABLE_PREFIX.'companies_leads', 'cl', 'cl.company_id = comp.id');

        if (!empty($id)) {
            $q->where(
                $q->expr()->eq('comp.id', $id)
            );
        }

        if ($user) {
            $q->andWhere('comp.created_by = :user');
            $q->setParameter('user', $user->getId());
        }

        $q->orderBy('comp.companyname', 'ASC');

        $results = $q->executeQuery()->fetchAllAssociative();

        $companies[$key] = $results;

        return $results;
    }

    /**
     * Get a count of leads that belong to the company.
     *
     * @return array
     */
    public function getLeadCount($companyIds)
    {
        $q = $this->_em->getConnection()->createQueryBuilder();

        $q->select('count(cl.lead_id) as thecount, cl.company_id')
            ->from(MAUTIC_TABLE_PREFIX.'companies_leads', 'cl');

        $returnArray = is_array($companyIds);

        if (!$returnArray) {
            $companyIds = [$companyIds];
        }

        $q->where(
            $q->expr()->in('cl.company_id', $companyIds)
        )
            ->groupBy('cl.company_id');

        $result = $q->executeQuery()->fetchAllAssociative();

        $return = [];
        foreach ($result as $r) {
            $return[$r['company_id']] = $r['thecount'];
        }

        // Ensure lists without leads have a value
        foreach ($companyIds as $l) {
            if (!isset($return[$l])) {
                $return[$l] = 0;
            }
        }

        return ($returnArray) ? $return : $return[$companyIds[0]];
    }

    /**
     * Get a list of lists.
     *
     * @return array
     */
    public function identifyCompany($companyName, $city = null, $country = null, $state = null)
    {
        $q = $this->_em->getConnection()->createQueryBuilder();
        if (empty($companyName)) {
            return [];
        }
        $q->select('comp.id, comp.companyname, comp.companycity, comp.companycountry, comp.companystate')
            ->from(MAUTIC_TABLE_PREFIX.'companies', 'comp');

        $q->where(
            $q->expr()->eq('comp.companyname', ':companyName')
        )->setParameter('companyName', $companyName);

        if ($city) {
            $q->andWhere(
                $q->expr()->eq('comp.companycity', ':city')
            )->setParameter('city', $city);
        }
        if ($country) {
            $q->andWhere(
                $q->expr()->eq('comp.companycountry', ':country')
            )->setParameter('country', $country);
        }
        if ($state) {
            $q->andWhere(
                $q->expr()->eq('comp.companystate', ':state')
            )->setParameter('state', $state);
        }

        $results = $q->executeQuery()->fetchAllAssociative();

        return ($results) ? $results[0] : null;
    }

    public function getCompaniesForContacts(array $contacts): array
    {
        if (!$contacts) {
            return [];
        }

        $qb = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $qb->select('c.*, l.lead_id, l.is_primary')
            ->from(MAUTIC_TABLE_PREFIX.'companies', 'c')
            ->join('c', MAUTIC_TABLE_PREFIX.'companies_leads', 'l', 'l.company_id = c.id')
            ->where(
                $qb->expr()->and(
                    $qb->expr()->in('l.lead_id', $contacts)
                )
            )
            ->orderBy('l.date_added, l.company_id', 'DESC'); // primary should be [0]

        $companies = $qb->executeQuery()->fetchAllAssociative();

        // Group companies per contact
        $contactCompanies = [];
        foreach ($companies as $company) {
            if (!isset($contactCompanies[$company['lead_id']])) {
                $contactCompanies[$company['lead_id']] = [];
            }

            $contactCompanies[$company['lead_id']][] = $company;
        }

        return $contactCompanies;
    }

    /**
     * Get companies grouped by column.
     *
     * @param QueryBuilder $query
     *
     * @return array
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getCompaniesByGroup($query, $column)
    {
        $query->select('count(comp.id) as companies, '.$column)
            ->addGroupBy($column)
            ->andWhere(
                $query->expr()->andX(
                    $query->expr()->isNotNull($column),
                    $query->expr()->neq($column, $query->expr()->literal(''))
                )
            );

        return $query->execute()->fetchAllAssociative();
    }

    /**
     * @param int $limit
     * @param int $offset
     *
     * @return mixed
     */
    public function getMostCompanies($query, $limit = 10, $offset = 0)
    {
        $query->setMaxResults($limit)
            ->setFirstResult($offset);

        return $query->executeQuery()->fetchAllAssociative();
    }

    /**
     * @param string $valueColumn
     */
    public function getAjaxSimpleList(CompositeExpression $expr = null, array $parameters = [], $labelColumn = null, $valueColumn = 'id'): array
    {
        $q = $this->_em->getConnection()->createQueryBuilder();

        $alias = $prefix = $this->getTableAlias();
        if (!empty($prefix)) {
            $prefix .= '.';
        }

        $tableName = $this->_em->getClassMetadata($this->getEntityName())->getTableName();

        $class      = '\\'.$this->getClassName();
        $reflection = new \ReflectionClass(new $class());

        // Get the label column if necessary
        if (null == $labelColumn) {
            if ($reflection->hasMethod('getTitle')) {
                $labelColumn = 'title';
            } else {
                $labelColumn = 'name';
            }
        }

        $q->select($prefix.$valueColumn.' as value,
        case
        when (comp.companycountry is not null and comp.companycity is not null) then concat(comp.companyname, \' <small>\', companycity,\', \', companycountry, \'</small>\')
        when (comp.companycountry is not null) then concat(comp.companyname, \' <small>\', comp.companycountry, \'</small>\')
        when (comp.companycity is not null) then concat(comp.companyname, \' <small>\', comp.companycity, \'</small>\')
        else comp.companyname
        end
        as label')
            ->from($tableName, $alias)
            ->orderBy($prefix.$labelColumn);

        if (null !== $expr && $expr->count()) {
            $q->where($expr);
        }

        if (!empty($parameters)) {
            $q->setParameters($parameters);
        }

        // Published only
        if ($reflection->hasMethod('getIsPublished')) {
            $q->andWhere(
                $q->expr()->eq($prefix.'is_published', ':true')
            )
                ->setParameter('true', true, 'boolean');
        }

        return $q->executeQuery()->fetchAllAssociative();
    }

    /**
     * Get list of company Ids by unique field data.
     *
     * @param iterable<mixed> $uniqueFieldsWithData An array of columns & values to filter by
     * @param int|null        $companyId            The current company id. Added to query to skip and find other companies
     * @param int|null        $limit                Limit count of results to return
     *
     * @return array<array{id: string}>
     */
    public function getCompanyIdsByUniqueFields($uniqueFieldsWithData, ?int $companyId = null, ?int $limit = null): array
    {
        return $this->getCompanyFieldsByUniqueFields($uniqueFieldsWithData, 'c.id', $companyId, $limit);
    }

    /**
     * Get list of company Ids by unique field data.
     *
     * @param iterable<mixed> $uniqueFieldsWithData An array of columns & values to filter by
     * @param int|null        $companyId            The current company id. Added to query to skip and find other companies
     * @param int|null        $limit                Limit count of results to return
     *
     * @return array<array{id: string}>
     */
    public function getCompanyFieldsByUniqueFields($uniqueFieldsWithData, string $select, ?int $companyId = null, ?int $limit = null): array
    {
        $q = $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->select($select)
            ->from(MAUTIC_TABLE_PREFIX.'companies', 'c');

        // loop through the fields and
        foreach ($uniqueFieldsWithData as $col => $val) {
            $q->{$this->getUniqueIdentifiersWherePart()}("c.$col = :".$col)
                ->setParameter($col, $val);
        }

        // if we have a company ID lets use it
        if ($companyId > 0) {
            // make sure that it's not the id we already have
            $q->andWhere('c.id != :companyId')
                ->setParameter('companyId', $companyId);
        }

        if ($limit > 0) {
            $q->setMaxResults($limit);
        }

        return $q->executeQuery()->fetchAllAssociative();
    }

    /**
     * @return Company[]
     */
    public function getCompaniesByUniqueFields(array $uniqueFieldsWithData, int $companyId = null, int $limit = null): array
    {
        $results = $this->getCompanyFieldsByUniqueFields($uniqueFieldsWithData, 'c.*', $companyId, $limit);

        // Collect the IDs
        $companies = [];
        foreach ($results as $r) {
            $companies[(int) $r['id']] = $r;
        }

        $q = $this->getEntityManager()->createQueryBuilder()
            ->select('c')
            ->from(Company::class, 'c');

        $q->where(
            $q->expr()->in('c.id', ':ids')
        )
            ->setParameter('ids', array_keys($companies))
            ->orderBy('c.dateAdded', \Doctrine\Common\Collections\Criteria::DESC)
            ->addOrderBy('c.id', \Doctrine\Common\Collections\Criteria::DESC);

        $entities = $q->getQuery()
            ->getResult();

        /** @var Company $company */
        foreach ($entities as $company) {
            $company->setFields(
                $this->formatFieldValues($companies[$company->getId()], true, 'company')
            );
        }

        return $entities;
    }
}
