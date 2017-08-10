<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Entity;

use Doctrine\DBAL\Query\QueryBuilder;

/**
 * Class CustomFieldRepositoryTrait.
 */
trait CustomFieldRepositoryTrait
{
    protected $useDistinctCount = false;

    /**
     * @var array
     */
    protected $customFieldList = [];

    /**
     * @param      $object
     * @param      $args
     * @param null $resultsCallback
     *
     * @return array
     */
    public function getEntitiesWithCustomFields($object, $args, $resultsCallback = null)
    {
        list($fields, $fixedFields) = $this->getCustomFieldList($object);

        //Fix arguments if necessary
        $args = $this->convertOrmProperties($this->getClassName(), $args);

        //DBAL
        /** @var QueryBuilder $dq */
        $dq = isset($args['qb']) ? $args['qb'] : $this->getEntitiesDbalQueryBuilder();

        // Generate where clause first to know if we need to use distinct on primary ID or not
        $this->useDistinctCount = false;
        $this->buildWhereClause($dq, $args);

        // Distinct is required here to get the correct count when group by is used due to applied filters
        $countSelect = ($this->useDistinctCount) ? 'COUNT(DISTINCT('.$this->getTableAlias().'.id))' : 'COUNT('.$this->getTableAlias().'.id)';
        $dq->select($countSelect.' as count');

        // Advanced search filters may have set a group by and if so, let's remove it for the count.
        if ($groupBy = $dq->getQueryPart('groupBy')) {
            $dq->resetQueryPart('groupBy');
        }

        //get a total count
        $result = $dq->execute()->fetchAll();
        $total  = ($result) ? $result[0]['count'] : 0;

        if (!$total) {
            $results = [];
        } else {
            if ($groupBy) {
                $dq->groupBy($groupBy);
            }
            //now get the actual paginated results

            $this->buildOrderByClause($dq, $args);
            $this->buildLimiterClauses($dq, $args);

            $dq->resetQueryPart('select');
            $this->buildSelectClause($dq, $args);

            $results = $dq->execute()->fetchAll();

            //loop over results to put fields in something that can be assigned to the entities
            $fieldValues = [];
            $groups      = $this->getFieldGroups();

            foreach ($results as $result) {
                $id = $result['id'];
                //unset all the columns that are not fields
                $this->removeNonFieldColumns($result, $fixedFields);

                foreach ($result as $k => $r) {
                    if (isset($fields[$k])) {
                        $fieldValues[$id][$fields[$k]['group']][$fields[$k]['alias']]          = $fields[$k];
                        $fieldValues[$id][$fields[$k]['group']][$fields[$k]['alias']]['value'] = $r;
                    }
                }

                //make sure each group key is present
                foreach ($groups as $g) {
                    if (!isset($fieldValues[$id][$g])) {
                        $fieldValues[$id][$g] = [];
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
                    $order .= ' WHEN '.$this->getTableAlias().'.id = '.$id.' THEN '.$count;
                    ++$count;
                }
                $order .= ' ELSE '.$count.' END) AS HIDDEN ORD';

                //ORM - generates lead entities
                $q = $this->getEntitiesOrmQueryBuilder($order);
                $this->buildSelectClause($dq, $args);

                //only pull the leads as filtered via DBAL
                $q->where(
                    $q->expr()->in($this->getTableAlias().'.id', ':entityIds')
                )->setParameter('entityIds', $ids);

                $q->orderBy('ORD', 'ASC');

                $results = $q->getQuery()
                    ->getResult();

                //assign fields
                foreach ($results as $r) {
                    $id = $r->getId();
                    $r->setFields($fieldValues[$id]);

                    if (is_callable($resultsCallback)) {
                        $resultsCallback($r);
                    }
                }
            } else {
                $results = [];
            }
        }

        return (!empty($args['withTotalCount'])) ?
            [
                'count'   => $total,
                'results' => $results,
            ] : $results;
    }

    /**
     * @param        $id
     * @param bool   $byGroup
     * @param string $object
     *
     * @return array
     */
    public function getFieldValues($id, $byGroup = true, $object = 'lead')
    {
        //use DBAL to get entity fields
        $q = $this->getEntitiesDbalQueryBuilder();

        if (is_array($id)) {
            $this->buildSelectClause($q, $id);
            $id = $id['id'];
        } else {
            $q->select($this->getTableAlias().'.*');
        }

        $q->where($this->getTableAlias().'.id = '.(int) $id);
        $values = $q->execute()->fetch();

        return $this->formatFieldValues($values, $byGroup, $object);
    }

    /**
     * Gets a list of unique values from fields for autocompletes.
     *
     * @param        $field
     * @param string $search
     * @param int    $limit
     * @param int    $start
     *
     * @return array
     */
    public function getValueList($field, $search = '', $limit = 10, $start = 0)
    {
        // Includes prefix
        $table = $this->getEntityManager()->getClassMetadata($this->getClassName())->getTableName();
        $col   = $this->getTableAlias().'.'.$field;
        $q     = $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->select("DISTINCT $col")
            ->from($table, 'l');

        $q->where(
            $q->expr()->andX(
                $q->expr()->neq($col, $q->expr()->literal('')),
                $q->expr()->isNotNull($col)
            )
        );

        if (!empty($search)) {
            $q->andWhere("$col LIKE :search")
                ->setParameter('search', "{$search}%");
        }

        $q->orderBy($col);

        if (!empty($limit)) {
            $q->setFirstResult($start)
                ->setMaxResults($limit);
        }

        $results = $q->execute()->fetchAll();

        return $results;
    }

    /**
     * Persist an array of entities.
     *
     * @param array $entities
     */
    public function saveEntities($entities)
    {
        foreach ($entities as $k => $entity) {
            // Leads cannot be batched due to requiring the ID to update the fields
            $this->saveEntity($entity);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @param $entity
     * @param $flush
     */
    public function saveEntity($entity, $flush = true)
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush($entity);
        }

        // Includes prefix
        $table  = $this->getEntityManager()->getClassMetadata($this->getClassName())->getTableName();
        $fields = $entity->getUpdatedFields();
        if (method_exists($entity, 'getChanges')) {
            $changes = $entity->getChanges();

            // remove the fields that are part of changes as they were already saved via a setter
            $fields = array_diff_key($fields, $changes);
        }

        if (!empty($fields)) {
            $this->prepareDbalFieldsForSave($fields);
            $this->getEntityManager()->getConnection()->update($table, $fields, ['id' => $entity->getId()]);
        }
    }

    /**
     * Function to remove non custom field columns from an arrayed lead row.
     *
     * @param       $r
     * @param array $fixedFields
     */
    protected function removeNonFieldColumns(&$r, $fixedFields = [])
    {
        $baseCols = $this->getBaseColumns($this->getClassName(), true);
        foreach ($baseCols as $c) {
            if (!isset($fixedFields[$c])) {
                unset($r[$c]);
            }
        }
        unset($r['owner_id']);
    }

    /**
     * @return array
     */
    protected function formatFieldValues($values, $byGroup = true, $object = 'lead')
    {
        list($fields, $fixedFields) = $this->getCustomFieldList($object);

        $this->removeNonFieldColumns($values, $fixedFields);

        // Reorder leadValues based on field order
        $values = array_merge(array_flip(array_keys($fields)), $values);

        $fieldValues = [];

        //loop over results to put fields in something that can be assigned to the entities
        foreach ($values as $k => $r) {
            if (!is_null($r)) {
                switch ($fields[$k]['type']) {
                    case 'number':
                        $r = (float) $r;
                        break;
                    case 'boolean':
                        $r = (bool) $r;
                        break;
                }
            }

            if (isset($fields[$k])) {
                if ($byGroup) {
                    $fieldValues[$fields[$k]['group']][$fields[$k]['alias']]          = $fields[$k];
                    $fieldValues[$fields[$k]['group']][$fields[$k]['alias']]['value'] = $r;
                } else {
                    $fieldValues[$fields[$k]['alias']]          = $fields[$k];
                    $fieldValues[$fields[$k]['alias']]['value'] = $r;
                }

                unset($fields[$k]);
            }
        }

        if ($byGroup) {
            //make sure each group key is present
            $groups = $this->getFieldGroups();
            foreach ($groups as $g) {
                if (!isset($fieldValues[$g])) {
                    $fieldValues[$g] = [];
                }
            }
        }

        return $fieldValues;
    }

    /**
     * @param $object
     *
     * @return array [$fields, $fixedFields]
     */
    private function getCustomFieldList($object)
    {
        if (empty($this->customFieldList)) {
            //Get the list of custom fields
            $fq = $this->getEntityManager()->getConnection()->createQueryBuilder();
            $fq->select('f.id, f.label, f.alias, f.type, f.field_group as "group", f.object, f.is_fixed')
                ->from(MAUTIC_TABLE_PREFIX.'lead_fields', 'f')
                ->where('f.is_published = :published')
                ->andWhere($fq->expr()->eq('object', ':object'))
                ->setParameter('published', true, 'boolean')
                ->setParameter('object', $object);
            $results = $fq->execute()->fetchAll();

            $fields      = [];
            $fixedFields = [];
            foreach ($results as $r) {
                $fields[$r['alias']] = $r;
                if ($r['is_fixed']) {
                    $fixedFields[$r['alias']] = $r['alias'];
                }
            }

            unset($results);

            $this->customFieldList = [$fields, $fixedFields];
        }

        return $this->customFieldList;
    }

    /**
     * @param $fields
     */
    private function prepareDbalFieldsForSave(&$fields)
    {
        // Ensure booleans are integers
        foreach ($fields as $field => &$value) {
            if (is_bool($value)) {
                $fields[$field] = (int) $value;
            }
        }
    }
}
