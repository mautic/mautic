<?php

/*
 * @copyright   2014 Mautic Contributorcomp. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Entity;

use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * Class CompanyRepository.
 */
class CompanyRepository extends CommonRepository implements CustomFieldRepositoryInterface
{
    use CustomFieldRepositoryTrait;

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
            $q = $this->createQueryBuilder($this->getTableAlias());
            if (is_array($id)) {
                $this->buildSelectClause($q, $id);
                $companyId = (int) $id['id'];
            } else {
                $companyId = $id;
            }
            $q->andWhere($this->getTableAlias().'.id = '.(int) $companyId);
            $entity = $q->getQuery()->getSingleResult();
        } catch (\Exception $e) {
            $entity = null;
        }

        if ($entity != null) {
            $fieldValues = $this->getFieldValues($id, true, 'company');
            $entity->setFields($fieldValues);
        }

        return $entity;
    }

    /**
     * Get a list of leads.
     *
     * @param array $args
     *
     * @return array
     */
    public function getEntities($args = [])
    {
        return $this->getEntitiesWithCustomFields('company', $args);
    }

    /**
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    public function getEntitiesDbalQueryBuilder()
    {
        $dq = $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->from(MAUTIC_TABLE_PREFIX.'companies', $this->getTableAlias());

        return $dq;
    }

    /**
     * @param $order
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getEntitiesOrmQueryBuilder($order)
    {
        $q = $this->getEntityManager()->createQueryBuilder();
        $q->select($this->getTableAlias().','.$order)
            ->from('MauticLeadBundle:Company', $this->getTableAlias(), $this->getTableAlias().'.id');

        return $q;
    }

    /**
     * Get the groups available for fields.
     *
     * @return array
     */
    public function getFieldGroups()
    {
        return ['core', 'professional', 'other'];
    }

    /**
     * Get companies by lead.
     *
     * @param   $leadId
     *
     * @return array
     */
    public function getCompaniesByLeadId($leadId, $companyId = null)
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
        $results = $q->execute()->fetchAll();

        return $results;
    }

    /**
     * {@inheritdoc}
     */
    public function getTableAlias()
    {
        return 'comp';
    }

    /**
     * {@inheritdoc}
     */
    protected function addCatchAllWhereClause(&$q, $filter)
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

    /**
     * {@inheritdoc}
     */
    protected function addSearchCommandWhereClause(&$q, $filter)
    {
        return $this->addStandardSearchCommandWhereClause($q, $filter);
    }

    /**
     * {@inheritdoc}
     */
    public function getSearchCommands()
    {
        return $this->getStandardSearchCommands();
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

        $q->select('comp.*')
            ->from(MAUTIC_TABLE_PREFIX.'companies', 'comp');

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

        $results = $q->execute()->fetchAll();

        $companies[$key] = $results;

        return $results;
    }

    /**
     * Get a count of leads that belong to the company.
     *
     * @param $companyIds
     *
     * @return array
     */
    public function getLeadCount($companyIds)
    {
        $q = $this->_em->getConnection()->createQueryBuilder();

        $q->select('count(cl.lead_id) as thecount, cl.company_id')
            ->from(MAUTIC_TABLE_PREFIX.'companies_leads', 'cl');

        $returnArray = (is_array($companyIds));

        if (!$returnArray) {
            $companyIds = [$companyIds];
        }

        $q->where(
            $q->expr()->in('cl.company_id', $companyIds),
            $q->expr()->eq('cl.manually_removed', ':false')
        )
            ->setParameter('false', false, 'boolean')
            ->groupBy('cl.company_id');

        $result = $q->execute()->fetchAll();

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
     * @param      $companyName
     * @param      $city
     * @param      $country
     * @param null $state
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

        $results = $q->execute()->fetchAll();

        return ($results) ? $results[0] : null;
    }

    /**
     * @param array $contacts
     *
     * @return array
     */
    public function getCompaniesForContacts(array $contacts)
    {
        $qb = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $qb->select('c.*, l.lead_id, l.is_primary')
            ->from(MAUTIC_TABLE_PREFIX.'companies', 'c')
            ->join('c', MAUTIC_TABLE_PREFIX.'companies_leads', 'l', 'l.company_id = c.id')
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->eq('l.manually_removed', 0),
                    $qb->expr()->in('l.lead_id', $contacts)
                )
            )
            ->orderBy('l.date_added, l.company_id', 'DESC'); // primary should be [0]
        $companies = $qb->execute()->fetchAll();

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

        $results = $query->execute()->fetchAll();

        return $results;
    }

    /**
     * @param     $query
     * @param int $limit
     * @param int $offset
     *
     * @return mixed
     */
    public function getMostCompanies($query, $limit = 10, $offset = 0)
    {
        $query->setMaxResults($limit)
            ->setFirstResult($offset);

        $results = $query->execute()->fetchAll();

        return $results;
    }

    /**
     * @param CompositeExpression|null $expr
     * @param array                    $parameters
     * @param null                     $labelColumn
     * @param string                   $valueColumn
     *
     * @return array
     */
    public function getAjaxSimpleList(CompositeExpression $expr = null, array $parameters = [], $labelColumn = null, $valueColumn = 'id')
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
        if ($labelColumn == null) {
            if ($reflection->hasMethod('getTitle')) {
                $labelColumn = 'title';
            } else {
                $labelColumn = 'name';
            }
        }

        $q->select($prefix.$valueColumn.' as value, 
        case 
        when (comp.companycountry is not null and comp.companycity is not null) then concat(comp.companyname, " <small>", companycity,", ", companycountry, "</small>")
        when (comp.companycountry is not null) then concat(comp.companyname, " <small>", comp.companycountry, "</small>")
        when (comp.companycity is not null) then concat(comp.companycity, " <small>", comp.companycity, "</small>")
        else comp.companyname
        end
        as label')
            ->from($tableName, $alias)
            ->orderBy($prefix.$labelColumn);

        if ($expr !== null && $expr->count()) {
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

        return $q->execute()->fetchAll();
    }
}
