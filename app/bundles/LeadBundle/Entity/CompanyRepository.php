<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributorcomp. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Entity;

use Doctrine\ORM\Query;
use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * Class CompanyRepository
 */
class CompanyRepository extends CommonRepository
{

    /**
     * Get a list of leads
     *
     * @param array $args
     *
     * @return array
     */
    public function getEntities($args = array())
    {
        //Get the list of custom fields
        $fq = $this->_em->getConnection()->createQueryBuilder();
        $fq->select('f.id, f.label, f.alias, f.type, f.field_group as "group"')
            ->from(MAUTIC_TABLE_PREFIX . 'lead_fields', 'f')
            ->where('f.is_published = :published')
            ->andWhere($fq->expr()->eq('object',':company'))
            ->setParameter('company', 'Company')
            ->setParameter('published', true, 'boolean');
        $results = $fq->execute()->fetchAll();

        $fields = array();
        foreach ($results as $r) {
            $fields[$r['alias']] = $r;
        }

        unset($results);

        //Fix arguments if necessary
        $args = $this->convertOrmProperties('Mautic\\LeadBundle\\Entity\\Company', $args);

        //DBAL
        $dq = $this->getEntityManager()->getConnection()->createQueryBuilder();

        $dq->select('COUNT(comp.id) as count')
            ->from(MAUTIC_TABLE_PREFIX.'companies', 'comp')
            ->leftJoin('comp', MAUTIC_TABLE_PREFIX.'users', 'u', 'u.id = comp.owner_id');

        // Filter by an entity query
        if (isset($args['entity_query'])) {
            $dq->andWhere(
                sprintf('EXISTS (%s)', $args['entity_query']->getSQL())
            );

            if (isset($args['entity_parameters'])) {
                foreach ($args['entity_parameters'] as $name => $value) {
                    $dq->setParameter($name, $value);
                }
            }
        }

        $this->buildWhereClause($dq, $args);

        //get a total count
        $result = $dq->execute()->fetchAll();
        $total  = $result[0]['count'];

        //now get the actual paginated results
        $this->buildOrderByClause($dq, $args);
        $this->buildLimiterClauses($dq, $args);

        $dq->resetQueryPart('select')
            ->select('comp.*');
        $results = $dq->execute()->fetchAll();

        //loop over results to put fields in something that can be assigned to the entities
        $fieldValues = array();
        $groups      = array('core', 'other');

        foreach ($results as $result) {
            $companyId = $result['id'];
            //unset all the columns that are not fields
            $this->removeNonFieldColumns($result);

            foreach ($result as $k => $r) {
                if (isset($fields[$k])) {
                    $fieldValues[$companyId][$fields[$k]['group']][$fields[$k]['alias']] = $fields[$k];
                    $fieldValues[$companyId][$fields[$k]['group']][$fields[$k]['alias']]['value'] = $r;
                }
            }

            //make sure each group key is present
            foreach ($groups as $g) {
                if (!isset($fieldValues[$companyId][$g])) {
                    $fieldValues[$companyId][$g] = array();
                }
            }
        }

        unset($results, $fields);

        //get an array of IDs for ORM query
        $ids = array_keys($fieldValues);

        if (count($ids)) {
            //ORM

            //build the order by id since the order was applied above
            //unfortunately, doctrine does not have a way to natively support this and can't use MySQL's FIELD function
            //since we have to be cross-platform; it's way ugly

            //We should probably totally ditch orm for leads
            $order = '(CASE';
            foreach ($ids as $count => $id) {
                $order .= ' WHEN comp.id = ' . $id . ' THEN ' . $count;
                $count++;
            }
            $order .= ' ELSE ' . $count . ' END) AS HIDDEN ORD';

            //ORM - generates lead entities
            $q = $this->_em->createQueryBuilder();
            $q->select('comp, u, i,' . $order)
                ->from('MauticLeadBundle:Company', 'comp', 'comp.id')
                ->leftJoin('comp.owner', 'u');

            //only pull the leads as filtered via DBAL
            $q->where(
                $q->expr()->in('comp.id', ':companyIds')
            )->setParameter('companyIds', $ids);

            $q->orderBy('ORD', 'ASC');

            $results = $q->getQuery()
                ->useQueryCache(false)
                ->useResultCache(false)
                ->getResult();

            //assign fields
            foreach ($results as $r) {
                $companyId = $r->getId();
                $r->setFields($fieldValues[$companyId]);

            }
        } else {
            $results = array();
        }

        return (!empty($args['withTotalCount'])) ?
            array(
                'count' => $total,
                'results' => $results
            ) : $results;
    }

    /**
     * Function to remove non custom field columns from an arrayed lead row
     *
     * @param array $r
     */
    protected function removeNonFieldColumns(&$r)
    {
        $baseCols = $this->getBaseColumns('Mautic\\LeadBundle\\Entity\\Company', true);
        foreach ($baseCols as $c) {
            unset($r[$c]);
        }
        unset($r['owner_id']);
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
        return $this->addStandardCatchAllWhereClause($q, $filter, array(
            'comp.name',
            'comp.description'
        ));
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
     * Get a list of lists
     *
     * @param bool   $user
     * @param string $alias
     * @param string $id
     *
     * @return array
     */
    public function getCompanies($user = false, $id = '')
    {
        static $companys = array();

        if (is_object($user)) {
            $user = $user->getId();
        }

        $key = (int) $user.$id;
        if (isset($companys[$key])) {
            return $companys[$key];
        }

        $q = $this->_em->createQueryBuilder()
            ->from('LeadBundle:Company', 'comp', 'comp.id');

        $q->select('partial comp.{id, name}');

        if (!empty($user)) {
            $q->orWhere('comp.createdBy = :user');
            $q->setParameter('user', $user);
        }

        if (!empty($id)) {
            $q->andWhere(
                $q->expr()->neq('comp.id', $id)
            );
        }

        $q->orderBy('comp.name');

        $results = $q->getQuery()->getArrayResult();

        $companys[$key] = $results;

        return $results;
    }

    /**
     * Get a list of lists
     *
     * @param string $name
     *
     * @return array
     */
    public function getCompanyByName($companyName)
    {
        static $companies = array();

        if (!$companyName) {
            return false;
        }

        $q = $this->_em->createQueryBuilder()
            ->from('LeadBundle:Company', 'comp', 'comp.id');

        $q->select('partial comp.{id, name}');
        $q->andWhere(
            $q->expr()->like('comp.name', $companyName)
        );

        $results = $q->getQuery()->getArrayResult();

        return $results;
    }
}
