<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Entity;

use Doctrine\Common\Collections\ExpressionBuilder;
use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Helper\SearchStringHelper;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class CommonRepository.
 */
class CommonRepository extends EntityRepository
{
    /**
     * Stores the parsed columns and their negate status for addAdvancedSearchWhereClause().
     *
     * @var array
     */
    protected $advancedFilterCommands = [];

    /**
     * @var User
     */
    protected $currentUser;

    /**
     * @var MauticFactory
     */
    protected $factory;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param string $alias
     * @param object $entity
     *
     * @return mixed
     */
    public function checkUniqueAlias($alias, $entity = null)
    {
        $q = $this->createQueryBuilder('e')
                  ->select('count(e.id) as aliascount')
                  ->where('e.alias = :alias');
        $q->setParameter('alias', $alias);

        if (!empty($entity) && $entity->getId()) {
            $q->andWhere('e.id != :id');
            $q->setParameter('id', $entity->getId());
        }

        $results = $q->getQuery()->getSingleResult();

        return $results['aliascount'];
    }

    /**
     * Examines the arguments passed to getEntities and converts ORM properties to dBAL column names.
     *
     * @param string $entityClass
     * @param array  $args
     *
     * @return array
     */
    public function convertOrmProperties($entityClass, array $args)
    {
        $properties = $this->getBaseColumns($entityClass);

        //check force filters
        if (isset($args['filter']['force']) && is_array($args['filter']['force'])) {
            $this->convertOrmPropertiesToColumns($args['filter']['force'], $properties);
        }

        if (isset($args['filter']['where']) && is_array($args['filter']['where'])) {
            $this->convertOrmPropertiesToColumns($args['filter']['where'], $properties);
        }

        //check order by
        if (isset($args['order'])) {
            if (is_array($args['order'])) {
                foreach ($args['order'] as &$o) {
                    $alias = '';
                    if (strpos($o, '.') !== false) {
                        list($alias, $o) = explode('.', $o);
                    }

                    if (in_array($o, $properties)) {
                        $o = preg_replace('/(?<=\\w)(?=[A-Z])/', '_$1', $o);
                        $o = strtolower($o);
                    }

                    $o = (!empty($alias)) ? $alias.'.'.$o : $o;
                }
            }
        }

        return $args;
    }

    /**
     * @param $className
     * @param $data
     *
     * @return mixed
     *
     * @throws \Doctrine\ORM\Mapping\MappingException
     * @throws \Exception
     */
    public function createFromArray($className, &$data)
    {
        $entity        = new $className();
        $meta          = $this->_em->getClassMetadata($className);
        $ormProperties = $this->getBaseColumns($className, true);

        foreach ($ormProperties as $property => $dbCol) {
            if (isset($data[$dbCol])) {
                $v = $data[$dbCol];

                if ($v && $meta->hasAssociation($property)) {
                    $map = $meta->getAssociationMapping($property);
                    $v   = $this->_em->getRepository($map['targetEntity'])->find($v);
                    if (empty($v)) {
                        throw new \Exception('Associate data not found');
                    }
                }

                $method = 'set'.ucfirst($property);
                if (method_exists($entity, $method)) {
                    $entity->$method($v);
                }

                unset($data[$dbCol]);
            }
        }

        return $entity;
    }

    /**
     * Delete an array of entities.
     *
     * @param array $entities
     */
    public function deleteEntities($entities)
    {
        //iterate over the results so the events are dispatched on each delete
        $batchSize = 20;
        foreach ($entities as $k => $entity) {
            $this->deleteEntity($entity, false);

            if ((($k + 1) % $batchSize) === 0) {
                $this->_em->flush();
            }
        }
        $this->_em->flush();
    }

    /**
     * Delete an entity through the repository.
     *
     * @param object $entity
     * @param bool   $flush  true by default; use false if persisting in batches
     *
     * @return int
     */
    public function deleteEntity($entity, $flush = true)
    {
        //delete entity
        $this->_em->remove($entity);

        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * @param      $alias
     * @param null $catAlias
     * @param null $lang
     *
     * @return mixed|null
     */
    public function findOneBySlugs($alias, $catAlias = null, $lang = null)
    {
        try {
            $q = $this->createQueryBuilder($this->getTableAlias())
                      ->setParameter(':alias', $alias);

            $expr = $q->expr()->andX(
                $q->expr()->eq($this->getTableAlias().'.alias', ':alias')
            );

            $metadata = $this->getClassMetadata();

            if (null !== $catAlias) {
                if (isset($metadata->associationMappings['category'])) {
                    $q->leftJoin($this->getTableAlias().'.category', 'category')
                      ->setParameter('catAlias', $catAlias);

                    $expr->add(
                        $q->expr()->eq('category.alias', ':catAlias')
                    );
                } else {
                    // This entity does not have a category mapping so return null

                    return null;
                }
            }

            if (isset($metadata->fieldMappings['language'])) {
                if ($lang) {
                    // Find the landing page with the specific requested locale
                    $q->setParameter('lang', $lang);

                    $expr->add(
                        $q->expr()->eq($this->getTableAlias().'.language', ':lang')
                    );
                } elseif (isset($metadata->associationMappings['translationParent'])) {
                    // Find the parent translation
                    $expr->add(
                        $q->expr()->isNull($this->getTableAlias().'.translationParent')
                    );
                }
            }

            // Check for variants and return parent only
            if (isset($metadata->associationMappings['variantParent'])) {
                $expr->add(
                    $q->expr()->isNull($this->getTableAlias().'.variantParent')
                );
            }

            $q->where($expr);

            $entity = $q->getQuery()->getSingleResult();
        } catch (\Exception $exception) {
            $entity = null;
        }

        return $entity;
    }

    /**
     * Gets the properties of an ORM entity.
     *
     * @param string $entityClass
     * @param bool   $returnColumnNames
     *
     * @return array
     */
    public function getBaseColumns($entityClass, $returnColumnNames = false)
    {
        static $baseCols = [true => [], false => []];

        if ($this->getEntityName() === $entityClass) {
            if (empty($baseCols[$returnColumnNames][$entityClass])) {
                // Use metadata
                $metadata                      = $this->getClassMetadata();
                $baseCols[true][$entityClass]  = $metadata->getColumnNames();
                $baseCols[false][$entityClass] = $metadata->getFieldNames();

                foreach ($metadata->getAssociationMappings() as $field => $association) {
                    if (in_array($association['type'], [ClassMetadataInfo::ONE_TO_ONE, ClassMetadataInfo::MANY_TO_ONE])) {
                        $baseCols[true][$entityClass][]  = $association['joinColumns'][0]['name'];
                        $baseCols[false][$entityClass][] = $field;
                    }
                }
            }

            return $baseCols[$returnColumnNames][$entityClass];
        }

        return $this->getEntityManager()->getRepository($entityClass)->getBaseColumns($entityClass, $returnColumnNames);
    }

    /**
     * Get a list of entities.
     *
     * @param array $args
     *
     * @return Paginator
     */
    public function getEntities(array $args = [])
    {
        $alias = $this->getTableAlias();

        if (isset($args['qb'])) {
            $q = $args['qb'];
        } else {
            $q = $this->_em
                ->createQueryBuilder()
                ->select($alias)
                ->from($this->_entityName, $alias, "{$alias}.id");

            if ($this->getClassMetadata()->hasAssociation('category')) {
                $q->leftJoin($this->getTableAlias().'.category', 'cat');
            }
        }

        $this->buildClauses($q, $args);
        $query = $q->getQuery();

        if (isset($args['hydration_mode'])) {
            $hydrationMode = constant('\\Doctrine\\ORM\\Query::'.strtoupper($args['hydration_mode']));
            $query->setHydrationMode($hydrationMode);
        } else {
            $hydrationMode = Query::HYDRATE_OBJECT;
        }

        if (!empty($args['iterator_mode'])) {
            // Hydrate one by one
            return $query->iterate(null, $hydrationMode);
        } elseif (empty($args['ignore_paginator'])) {
            // Paginator
            return new Paginator($query, false);
        } else {
            // All results
            return $query->getResult($hydrationMode);
        }
    }

    /**
     * Get a single entity.
     *
     * @param int $id
     *
     * @return null|object
     */
    public function getEntity($id = 0)
    {
        try {
            if (is_array($id)) {
                $q = $this->createQueryBuilder($this->getTableAlias());
                $this->buildSelectClause($q, $id['select']);
                $q->where($this->getTableAlias().'.id = '.(int) $id['id']);
                $entity = $q->getQuery()->getSingleResult();
            } else {
                $entity = $this->find((int) $id);
            }
        } catch (\Exception $e) {
            $entity = null;
        }

        return $entity;
    }

    /**
     * @return ExpressionBuilder
     */
    public function getExpressionBuilder()
    {
        if (self::$expressionBuilder === null) {
            self::$expressionBuilder = new ExpressionBuilder();
        }

        return self::$expressionBuilder;
    }

    /**
     * @param      $q
     * @param      $filter
     * @param null $parameterName
     *
     * @return array
     */
    public function getFilterExpr(&$q, $filter, $parameterName = null)
    {
        $unique    = ($parameterName) ? $parameterName : $this->generateRandomParameterName();
        $parameter = [];

        if (isset($filter['group'])) {
            $expr = $q->expr()->orX();
            foreach ($filter['group'] as $orGroup) {
                $groupExpr = $q->expr()->andX();
                foreach ($orGroup as $subFilter) {
                    list($subExpr, $subParameters) = $this->getFilterExpr($q, $subFilter);

                    $groupExpr->add($subExpr);
                    if (!empty($subParameters)) {
                        $parameter = array_merge($parameter, $subParameters);
                    }
                }
                $expr->add($groupExpr);
            }
        } elseif (strpos($filter['column'], ',') !== false) {
            $columns      = explode(',', $filter['column']);
            $expr         = $q->expr()->orX();
            $setParameter = false;
            foreach ($columns as $c) {
                $subFilter           = $filter;
                $subFilter['column'] = trim($c);

                list($subExpr, $parameterUsed) = $this->getFilterExpr($q, $subFilter, $unique);

                if ($parameterUsed) {
                    $setParameter = true;
                }

                $expr->add($subExpr);
            }
            if ($setParameter) {
                $parameter = [$unique => $filter['value']];
            }
        } else {
            $func = (!empty($filter['operator'])) ? $filter['operator'] : $filter['expr'];

            if (in_array($func, ['isNull', 'isNotNull'])) {
                $expr = $q->expr()->{$func}($filter['column']);
            } elseif (in_array($func, ['in', 'notIn'])) {
                $expr = $q->expr()->{$func}($filter['column'], $filter['value']);
            } elseif (in_array($func, ['like', 'notLike'])) {
                if (isset($filter['strict']) && !$filter['strict']) {
                    if (is_numeric($filter['value'])) {
                        // Postgres doesn't like using "LIKE" with numbers
                        $func = ($func == 'like') ? 'eq' : 'neq';
                    } else {
                        $filter['value'] = "%{$filter['value']}%";
                    }
                }
                $expr      = $q->expr()->{$func}($filter['column'], ':'.$unique);
                $parameter = [$unique => $filter['value']];
            } else {
                if (isset($filter['strict']) && !$filter['strict']) {
                    $filter['value'] = "%{$filter['value']}%";
                }
                $expr      = $q->expr()->{$func}($filter['column'], ':'.$unique);
                $parameter = [$unique => $filter['value']];
            }
            if (!empty($filter['not'])) {
                $expr = $q->expr()->not($expr);
            }
        }

        return [$expr, $parameter];
    }

    /**
     * Returns a andX Expr() that takes into account isPublished, publishUp and publishDown dates
     * The Expr() sets a :now and :true parameter that must be set in the calling function.
     *
     * @param      $q
     * @param null $alias
     * @param bool $setNowParameter
     * @param bool $setTrueParameter
     * @param bool $allowNullForPublishedUp Allow entities without a published up date
     *
     * @return mixed
     */
    public function getPublishedByDateExpression(
        $q,
        $alias = null,
        $setNowParameter = true,
        $setTrueParameter = true,
        $allowNullForPublishedUp = true
    ) {
        $isORM = ($q instanceof QueryBuilder);

        if ($alias === null) {
            $alias = $this->getTableAlias();
        }

        if ($setNowParameter) {
            $now = new \DateTime();
            if (!$isORM) {
                $dtHelper = new DateTimeHelper($now, 'Y-m-d H:i:s', 'local');
                $now      = $dtHelper->toUtcString();
            }
            $q->setParameter('now', $now);
        }

        if ($setTrueParameter) {
            $q->setParameter('true', true, 'boolean');
        }

        if ($isORM) {
            $pub     = 'isPublished';
            $pubUp   = 'publishUp';
            $pubDown = 'publishDown';
        } else {
            $pub     = 'is_published';
            $pubUp   = 'publish_up';
            $pubDown = 'publish_down';
        }

        $expr = $q->expr()->andX(
            $q->expr()->eq("$alias.$pub", ':true'),
            $q->expr()->orX(
                $q->expr()->isNull("$alias.$pubDown"),
                $q->expr()->gte("$alias.$pubDown", ':now')
            )
        );

        if ($allowNullForPublishedUp) {
            $expr->add(
                $q->expr()->orX(
                    $q->expr()->isNull("$alias.$pubUp"),
                    $q->expr()->lte("$alias.$pubUp", ':now')
                )
            );
        } else {
            $expr->add(
                $q->expr()->andX(
                    $q->expr()->isNotNull("$alias.$pubUp"),
                    $q->expr()->lte("$alias.$pubUp", ':now')
                )
            );
        }

        return $expr;
    }

    /**
     * Get an array of rows from one table using DBAL.
     *
     * @param int   $start
     * @param int   $limit
     * @param array $order
     * @param array $where
     * @param array $select
     * @param array $allowedJoins
     *
     * @return array
     */
    public function getRows($start = 0, $limit = 100, array $order = [], array $where = [], array $select = null, array $allowedJoins = [])
    {
        $alias    = $this->getTableAlias();
        $metadata = $this->getClassMetadata();
        $table    = $metadata->getTableName();
        $q        = $this->_em->getConnection()->createQueryBuilder();

        $q->select('count(*)')
          ->from($table, $alias);

        // Join associations for permission filtering
        $this->buildDbalJoinsFromAssociations($q, $metadata->getAssociationMappings(), $alias, $allowedJoins);

        $this->buildWhereClauseFromArray($q, $where);

        $count = $q->execute()->fetchColumn();

        if ($select) {
            foreach ($select as &$column) {
                if (strpos($column, '.') === false) {
                    $column = $alias.'.'.$column;
                }
            }
            $selectString = implode(', ', $select);
        } else {
            $selectString = $alias.'.*';
        }

        $q->resetQueryPart('select')
          ->select($selectString)
          ->setFirstResult($start)
          ->setMaxResults($limit);

        $this->buildOrderByClauseFromArray($q, $order);

        $results = $q->execute()->fetchAll();

        return [
            'total'   => $count,
            'results' => $results,
        ];
    }

    /**
     * Array of search commands supported by the repository.
     *
     * @return array
     */
    public function getSearchCommands()
    {
        return ['mautic.core.searchcommand.ids'];
    }

    /**
     * Gets a list of published entities as an array id => label.
     *
     * @param CompositeExpression $expr
     * @param array               $parameters   Parameters used in $expr
     * @param string              $labelColumn  Column that houses the label
     * @param string              $valueColumn  Column that houses the value
     * @param string              $extraColumns String of extra select columns
     * @param int                 $limit        Limit for results
     *
     * @return array
     */
    public function getSimpleList(CompositeExpression $expr = null, array $parameters = [], $labelColumn = null, $valueColumn = 'id', $extraColumns = null, $limit = 0)
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

        $q->select($prefix.$valueColumn.' as value, '.$prefix.$labelColumn.' as label'.($extraColumns ? ", $extraColumns" : ''))
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

        if ($limit) {
            $q->setMaxResults((int) $limit);
        }

        return $q->execute()->fetchAll();
    }

    /**
     * @return array
     */
    public function getStandardSearchCommands()
    {
        return [
            'mautic.core.searchcommand.ispublished',
            'mautic.core.searchcommand.isunpublished',
            'mautic.core.searchcommand.isuncategorized',
            'mautic.core.searchcommand.ismine',
            'mautic.core.searchcommand.category',
            'mautic.core.searchcommand.ids',
        ];
    }

    /**
     * @return string
     */
    public function getTableAlias()
    {
        return 'e';
    }

    /**
     * @return array
     */
    public function getTableColumns()
    {
        $columns = $this->getClassMetadata()->getColumnNames();

        if ($associations = $this->getClassMetadata()->getAssociationMappings()) {
            foreach ($associations as $property => $association) {
                if (!empty($association['joinColumnFieldNames'])) {
                    $columns = array_merge($columns, array_values($association['joinColumnFieldNames']));
                }
            }
        }

        natcasesort($columns);

        return array_values($columns);
    }

    /**
     * Returns entity table name.
     *
     * @return string
     */
    public function getTableName()
    {
        return $this->getClassMetadata()->getTableName();
    }

    /**
     * Persist an array of entities.
     *
     * @param array $entities
     */
    public function saveEntities($entities)
    {
        //iterate over the results so the events are dispatched on each delete
        $batchSize = 20;
        foreach ($entities as $k => $entity) {
            $this->saveEntity($entity, false);

            if ((($k + 1) % $batchSize) === 0) {
                $this->getEntityManager()->flush();
            }
        }
        $this->getEntityManager()->flush();
    }

    /**
     * Save an entity through the repository.
     *
     * @param object $entity
     * @param bool   $flush  true by default; use false if persisting in batches
     *
     * @return int
     */
    public function saveEntity($entity, $flush = true)
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush($entity);
        }
    }

    /**
     * Set the current user (i.e. from security context) for use within repositories.
     *
     * @param User $user
     */
    public function setCurrentUser($user)
    {
        if (!$user instanceof User) {
            //just create a blank user entity
            $user = new User();
        }
        $this->currentUser = $user;
    }

    /**
     * @param MauticFactory $factory
     */
    public function setFactory(MauticFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * @param TranslatorInterface $translator
     */
    public function setTranslator(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * Validate array for one order by condition.
     *
     * @deprecated 2.6.0 to be removed in 3.0; use validateOrderByClause() instead
     *
     * @param array $args ['col' => 'column_a', 'dir' => 'ASC']
     *
     * @throws \InvalidArgumentException
     *
     * @return array
     */
    public function validateDbalOrderByArray(array $args)
    {
        return $this->validateOrderByClause($args);
    }

    /**
     * Validate the array for one where condition.
     *
     * @deprecated 2.6.0 to be removed in 3.0; use validateWhereClause() instead
     *
     * @param array $args ['expr' => 'expression', 'col' => 'DB column', 'val' => 'value to search for']
     *
     * @throws \InvalidArgumentException
     *
     * @return array
     */
    public function validateDbalWhereArray(array $args)
    {
        return $this->validateWhereClause($args);
    }

    /**
     * Validate array for one order by condition.
     *
     * @param array $clause ['col' => 'column_a', 'dir' => 'ASC']
     *
     * @throws \InvalidArgumentException
     *
     * @return array
     */
    protected function validateOrderByClause($clause)
    {
        $msg = '"%s" is missing in the order by clause array.';
        if (empty($clause['col'])) {
            throw new \InvalidArgumentException(sprintf($msg, 'col'));
        }

        if (empty($clause['dir'])) {
            $clause['dir'] = 'ASC';
        }

        $clause['dir'] = $this->sanitize(strtoupper($clause['dir']));
        $clause['col'] = $this->sanitize($clause['col'], ['_']);

        return $clause;
    }

    /**
     * Validate the array for one where condition.
     *
     * @param array $clause ['expr' => 'expression', 'col' => 'DB column', 'val' => 'value to search for']
     *
     * @throws \InvalidArgumentException
     *
     * @return array
     */
    protected function validateWhereClause(array $clause)
    {
        $msg = '"%s" is missing in the where clause array.';
        if (empty($clause['expr'])) {
            throw new \InvalidArgumentException(sprintf($msg, 'expr'));
        }

        if (empty($clause['col']) && empty($clause['column'])) {
            throw new \InvalidArgumentException(sprintf($msg, 'col'));
        }

        if (!array_key_exists('val', $clause) && !array_key_exists('value', $clause)) {
            $clause['val'] = '';
        }

        $clause['expr'] = $this->sanitize($clause['expr']);
        $clause['col']  = $this->sanitize((isset($clause['column']) ? $clause['column'] : $clause['col']), ['_', '.']);
        if (isset($clause['value'])) {
            $clause['val'] = $clause['value'];
        }
        unset($clause['value'], $clause['column']);

        // Value will be sanitized by Doctrine

        return $clause;
    }

    /**
     * @param \Doctrine\ORM\QueryBuilder $qb
     * @param array                      $filters
     *
     * @return array
     */
    protected function addAdvancedSearchWhereClause(&$qb, $filters)
    {
        $parseFilters = [];
        if (isset($filters->root[0])) {
            // Function is determined by the second clause type
            $type         = (isset($filters->root[1])) ? $filters->root[1]->type : $filters->root[0]->type;
            $parseFilters = &$filters->root;
        } elseif (isset($filters->children[0])) {
            $type         = (isset($filters->children[1])) ? $filters->children[1]->type : $filters->children[0]->type;
            $parseFilters = &$filters->children;
        } elseif (is_array($filters)) {
            $type         = (isset($filters[1])) ? $filters[1]->type : $filters[0]->type;
            $parseFilters = &$filters;
        }

        if (empty($type)) {
            $type = 'and';
        }

        $parameters  = [];
        $expressions = $qb->expr()->{"{$type}X"}();

        if ($parseFilters) {
            $this->parseSearchFilters($parseFilters, $qb, $expressions, $parameters);
        }

        return [$expressions, $parameters];
    }

    /**
     * @param \Doctrine\ORM\QueryBuilder $qb
     * @param array                      $filter
     *
     * @return array
     */
    protected function addCatchAllWhereClause(QueryBuilder $qb, $filter)
    {
        foreach (['name', 'title'] as $column) {
            if ($this->getClassMetadata()->hasField($column)) {
                return $this->addStandardCatchAllWhereClause(
                    $qb,
                    $filter,
                    [
                        $this->getTableAlias().'.'.$column,
                    ]
                );
            }
        }

        return [
            false,
            [],
        ];
    }

    /**
     * Unique handling for $filter->not since dbal does not support the not() function with it's QueryBuilder.
     *
     * @param QueryBuilder $q
     * @param object       $filter
     * @param array        $columns
     *
     * @return array
     */
    protected function addDbalCatchAllWhereClause(&$q, $filter, array $columns)
    {
        $unique = $this->generateRandomParameterName(); //ensure that the string has a unique parameter identifier
        $string = ($filter->strict) ? $filter->string : "{$filter->string}";
        if ($filter->not) {
            $xFunc    = 'andX';
            $exprFunc = 'notLike';
        } else {
            $xFunc    = 'orX';
            $exprFunc = 'like';
        }
        $expr = $q->expr()->$xFunc();

        foreach ($columns as $column) {
            $expr->add(
                $q->expr()->$exprFunc($column, ":$unique")
            );
        }

        return [
            $expr,
            ["$unique" => $string],
        ];
    }

    /**
     * @param $q
     * @param $filter
     *
     * @return array
     */
    protected function addSearchCommandWhereClause(QueryBuilder $q, $filter)
    {
        $command = $filter->command;
        $expr    = false;

        switch ($command) {
            case $this->translator->trans('mautic.core.searchcommand.ids'):
            case $this->translator->trans('mautic.core.searchcommand.ids', [], null, 'en_US'):
                $expr = $this->getIdsExpr($q, $filter);
                break;
        }

        return [
            $expr,
            [],
        ];
    }

    /**
     * @param \Doctrine\ORM\QueryBuilder $q
     * @param object                     $filter
     * @param array                      $columns
     *
     * @return array
     */
    protected function addStandardCatchAllWhereClause(&$q, $filter, array $columns)
    {
        $unique = $this->generateRandomParameterName(); //ensure that the string has a unique parameter identifier
        $string = $filter->string;

        if (!$filter->strict) {
            if (strpos($string, '%') === false) {
                $string = "$string%";
            }
        }

        $ormQb = true;

        if ($q instanceof QueryBuilder) {
            $xFunc    = 'orX';
            $exprFunc = 'like';
        } else {
            $ormQb = false;
            if ($filter->not) {
                $xFunc    = 'andX';
                $exprFunc = 'notLike';
            } else {
                $xFunc    = 'orX';
                $exprFunc = 'like';
            }
        }

        $expr = $q->expr()->$xFunc();
        foreach ($columns as $col) {
            $expr->add(
                $q->expr()->$exprFunc($col, ":$unique")
            );
        }

        if ($ormQb && $filter->not) {
            $expr = $q->expr()->not($expr);
        }

        return [
            $expr,
            ["$unique" => $string],
        ];
    }

    /**
     * @param \Doctrine\ORM\QueryBuilder $q
     * @param object                     $filter
     *
     * @return array
     */
    protected function addStandardSearchCommandWhereClause(&$q, $filter)
    {
        $command         = $filter->command;
        $unique          = $this->generateRandomParameterName();
        $returnParameter = true; //returning a parameter that is not used will lead to a Doctrine error
        $expr            = false;
        $prefix          = $this->getTableAlias();

        switch ($command) {
            case $this->translator->trans('mautic.core.searchcommand.ispublished'):
            case $this->translator->trans('mautic.core.searchcommand.ispublished', [], null, 'en_US'):
                $expr            = $q->expr()->eq("$prefix.isPublished", ":$unique");
                $forceParameters = [$unique => true];
                break;
            case $this->translator->trans('mautic.core.searchcommand.isunpublished'):
            case $this->translator->trans('mautic.core.searchcommand.isunpublished', [], null, 'en_US'):
                $expr            = $q->expr()->eq("$prefix.isPublished", ":$unique");
                $forceParameters = [$unique => false];
                break;
            case $this->translator->trans('mautic.core.searchcommand.isuncategorized'):
            case $this->translator->trans('mautic.core.searchcommand.isuncategorized', [], null, 'en_US'):
                $expr = $q->expr()->orX(
                    $q->expr()->isNull("$prefix.category"),
                    $q->expr()->eq("$prefix.category", $q->expr()->literal(''))
                );
                $returnParameter = false;
                break;
            case $this->translator->trans('mautic.core.searchcommand.ismine'):
            case $this->translator->trans('mautic.core.searchcommand.ismine', [], null, 'en_US'):
                $expr            = $q->expr()->eq("IDENTITY($prefix.createdBy)", $this->currentUser->getId());
                $returnParameter = false;
                break;
            case $this->translator->trans('mautic.core.searchcommand.category'):
            case $this->translator->trans('mautic.core.searchcommand.category', [], null, 'en_US'):
                // Find the category prefix
                $joins     = $q->getDQLPart('join');
                $catPrefix = false;
                foreach ($joins as $joinPrefix => $joinStatements) {
                    /** @var Query\Expr\Join $join */
                    foreach ($joinStatements as $join) {
                        if (strpos($join->getJoin(), '.category') !== false) {
                            $catPrefix = $join->getAlias();
                            break;
                        }
                    }
                    if ($catPrefix !== false) {
                        break;
                    }
                }
                if (false === $catPrefix) {
                    $catPrefix = 'c';
                }
                $expr           = $q->expr()->like("{$catPrefix}.alias", ":$unique");
                $filter->strict = true;
                break;
            case $this->translator->trans('mautic.core.searchcommand.ids'):
            case $this->translator->trans('mautic.core.searchcommand.ids', [], null, 'en_US'):
                $expr            = $this->getIdsExpr($q, $filter);
                $returnParameter = false;
                break;
        }

        if ($expr && $filter->not) {
            $expr = $q->expr()->not($expr);
        }

        if (!empty($forceParameters)) {
            $parameters = $forceParameters;
        } elseif (!$returnParameter) {
            $parameters = [];
        } else {
            $string = $filter->string;
            if (!$filter->strict) {
                if (strpos($string, '%') === false) {
                    $string = "$string%";
                }
            }

            $parameters = ["$unique" => $string];
        }

        return [$expr, $parameters];
    }

    /**
     * @param $appendTo
     * @param $expr
     */
    protected function appendExpression($appendTo, $expr)
    {
        if ($expr instanceof CompositeExpression || $expr instanceof Query\Expr\Composite) {
            if ($expr->count()) {
                $appendTo->add($expr);
            }
        } elseif (!empty($expr)) {
            $appendTo->add($expr);
        }
    }

    /**
     * @param \Doctrine\ORM\QueryBuilder $q
     * @param array                      $args
     *
     * @return bool
     */
    protected function buildClauses(&$q, array $args)
    {
        $this->buildSelectClause($q, $args);
        $this->buildIndexByClause($q, $args);
        $this->buildWhereClause($q, $args);
        $this->buildOrderByClause($q, $args);
        $this->buildLimiterClauses($q, $args);

        return true;
    }

    /**
     * @param \Doctrine\DBAL\Query\QueryBuilder $q
     * @param                                   $associations
     * @param                                   $alias
     * @param array                             $allowed
     *
     * @return bool
     */
    protected function buildDbalJoinsFromAssociations(\Doctrine\DBAL\Query\QueryBuilder $q, $associations, $alias, array $allowed)
    {
        $joinAdded = false;
        foreach ($associations as $property => $association) {
            $subJoinAdded  = false;
            $targetMetdata = $this->_em->getRepository($association['targetEntity'])->getClassMetadata();
            if ($propertyAllowedJoins = preg_grep('/^'.$property.'\..*/', $allowed)) {
                foreach ($propertyAllowedJoins as $key => $join) {
                    $propertyAllowedJoins[$key] = str_replace($property.'.', '', $join);
                }

                $subJoinAdded = $this->buildDbalJoinsFromAssociations($q, $targetMetdata->getAssociationMappings(), $property, $propertyAllowedJoins);
            }

            if ($subJoinAdded || in_array($property, $allowed)) {
                // Unset the property so that it's not used again in other the next level
                unset($allowed[$property]);
                $targetTable = $targetMetdata->getTableName();
                $hasNullable = false;
                $joinColumns = [];
                foreach ($association['joinColumns'] as $join) {
                    if (!empty($join['nullable'])) {
                        $hasNullable = true;
                    }

                    $joinColumns[] = $alias.'.'.$join['name'].' = '.$property.'.'.$join['referencedColumnName'];
                }

                $joinType = ($hasNullable) ? 'leftJoin' : 'join';
                $q->$joinType($alias, $targetTable, $property, implode(' AND ', $joinColumns));
                $joinAdded = true;
            }
        }

        return $joinAdded;
    }

    /**
     * Build order by from an array.
     *
     * @deprecated 2.6.0 to be removed in 3.0; use buildOrderByClauseFromArray() instead
     *
     * @param QueryBuilder $query
     * @param array        $args  [['col' => 'column_a', 'dir' => 'ASC']]
     *
     * @return array
     */
    protected function buildDbalOrderBy($query, $args)
    {
        $this->buildOrderByClauseFromArray($query, $args);
    }

    /**
     * @deprecated 2.6.0 to be removed in 3.0
     *
     * @param QueryBuilder $query
     * @param array        $args  [['expr' => 'DBAL expression', 'col' => 'DB column', 'val' => 'value to search for']]
     */
    protected function buildDbalWhere($query, $args)
    {
        $this->buildWhereClauseFromArray($query, $args);
    }

    /**
     * @param       $q
     * @param array $args
     */
    protected function buildIndexByClause($q, array $args)
    {
        if (!empty($args['index_by'])) {
            if (is_array($args['index_by'])) {
                list($indexAlias, $indexBy) = $args['index_by'];
            } else {
                $indexAlias = $this->getTableAlias();
                $indexBy    = $args['index_by'];
            }
            if (strpos($indexBy, $indexAlias) !== 0) {
                $indexBy = $indexAlias.'.'.$indexBy;
            }
            $q->indexBy($indexAlias, $indexBy);
        }
    }

    /**
     * @param \Doctrine\ORM\QueryBuilder $q
     * @param array                      $args
     *
     * @return bool
     */
    protected function buildLimiterClauses(&$q, array $args)
    {
        $start = array_key_exists('start', $args) ? $args['start'] : 0;
        $limit = array_key_exists('limit', $args) ? $args['limit'] : 0;

        if (!empty($limit)) {
            $q->setFirstResult($start)
              ->setMaxResults($limit);
        }
    }

    /**
     * @param \Doctrine\ORM\QueryBuilder $q
     * @param array                      $args
     */
    protected function buildOrderByClause(&$q, array $args)
    {
        $orderBy    = array_key_exists('orderBy', $args) ? $args['orderBy'] : '';
        $orderByDir = $this->sanitize(
            array_key_exists('orderByDir', $args) ? $args['orderByDir'] : ''
        );

        if (empty($orderBy)) {
            $defaultOrder = $this->getDefaultOrder();

            foreach ($defaultOrder as $order) {
                $q->addOrderBy($order[0], $order[1]);
            }
        } else {
            //add direction after each column
            $parts = explode(',', $orderBy);
            foreach ($parts as $order) {
                $order = $this->sanitize($order, ['_', '.']);

                $q->addOrderBy($order, $orderByDir);
            }
        }
    }

    /**
     * Build order by from an array.
     *
     * @param QueryBuilder $query
     * @param array        $clauses [['col' => 'column_a', 'dir' => 'ASC']]
     *
     * @return array
     */
    protected function buildOrderByClauseFromArray($query, array $clauses)
    {
        if ($clauses && is_array($clauses)) {
            foreach ($clauses as $clause) {
                $clause = $this->validateOrderByClause($clause);
                $column = (strpos($clause['col'], '.') === false) ? $this->getTableAlias().'.'.$clause['col'] : $clause['col'];
                $query->addOrderBy($column, $clause['dir']);
            }
        }
    }

    /**
     * @param \Doctrine\ORM\QueryBuilder|\Doctrine\DBAL\Query\QueryBuilder $q
     * @param                                                              $q
     * @param array                                                        $args
     */
    protected function buildSelectClause($q, array $args)
    {
        $isOrm = $q instanceof QueryBuilder;
        if (isset($args['select'])) {
            // Build a custom select
            if (!is_array($args['select'])) {
                $args['select'] = [$args['select']];
            }

            $selects        = [];
            $args['select'] = explode(',', $args['select']);
            foreach ($args['select'] as $select) {
                if (strpos($select, '.') !== false) {
                    list($alias, $select) = explode('.', $select);
                } else {
                    $alias = $this->getTableAlias();
                }

                if (!isset($selects[$alias])) {
                    $selects[$alias] = [];
                }

                $selects[$alias][] = $select;
            }

            $partials    = [];
            $ormColumns  = $this->getBaseColumns($this->getClassName());
            $dbalColumns = $this->getTableColumns();
            foreach ($selects as $alias => $columns) {
                if ($isOrm) {
                    if ($columns = array_intersect($columns, $ormColumns)) {
                        $columns    = array_map([$this, 'sanitize'], $columns);
                        $partials[] = 'partial '.$alias.'.{'.implode(',', $columns).'}';
                    }
                } else {
                    if ($columns = array_intersect($columns, $dbalColumns)) {
                        foreach ($columns as $column) {
                            $partials[] = $alias.'.'.$this->sanitize($column);
                        }
                    }
                }
            }

            if ($partials) {
                $newSelect = implode(', ', $partials);
                $select    = ($isOrm) ? $q->getDQLPart('select') : $q->getQueryPart('select');
                if (!$select || $this->getTableAlias() === $select || $this->getTableAlias().'.*' === $select) {
                    $q->select($newSelect);
                } elseif (strpos($select, $this->getTableAlias().',') !== false) {
                    $q->select(str_replace($this->getTableAlias().',', $newSelect.','));
                } elseif (strpos($select, $this->getTableAlias().'.*,') !== false) {
                    $q->select(str_replace($this->getTableAlias().'.*,', $newSelect.','));
                }
            }
        }

        if ($isOrm) {
            if (!$q->getDQLPart('select')) {
                $q->select($this->getTableAlias());
            }
        } else {
            if (!$q->getQueryPart('select')) {
                $q->select($this->getTableAlias().'.*');
            }
        }
    }

    /**
     * @param \Doctrine\ORM\QueryBuilder $q
     * @param array                      $args
     */
    protected function buildWhereClause(&$q, array $args)
    {
        $filter                    = array_key_exists('filter', $args) ? $args['filter'] : '';
        $filterHelper              = new SearchStringHelper();
        $advancedFilters           = new \stdClass();
        $advancedFilters->root     = [];
        $advancedFilters->commands = [];
        // Reset advanced filter commands to be used in search query building
        $this->advancedFilterCommands = [];
        $advancedFilterStrings        = [];
        $queryParameters              = [];
        $queryExpression              = $q->expr()->andX();

        if (isset($args['ids'])) {
            $ids = array_map('intval', $args['ids']);
            if ($q instanceof QueryBuilder) {
                $param = $this->generateRandomParameterName();
                $queryExpression->add(
                    $q->expr()->in($this->getTableAlias().'.id', ':'.$param)
                );
                $queryParameters[$param] = $ids;
            } else {
                $queryExpression->add(
                    $q->expr()->in($this->getTableAlias().'.id', $ids)
                );
            }
        } elseif (!empty($args['ownedBy'])) {
            $queryExpression->add(
                $q->expr()->in($this->getTableAlias().'.'.$args['ownedBy'][0], (int) $args['ownedBy'][1])
            );
        }

        if (!empty($filter)) {
            if (is_array($filter)) {
                if (!empty($filter['where'])) {
                    // build clauses from array
                    $this->buildWhereClauseFromArray($q, $filter['where']);
                } elseif (!empty($filter['criteria']) || !empty($filter['force'])) {
                    $criteria = !empty($filter['criteria']) ? $filter['criteria'] : $filter['force'];
                    if (is_array($criteria)) {
                        //defined columns with keys of column, expr, value
                        foreach ($criteria as $criterion) {
                            if ($criterion instanceof Query\Expr || $criterion instanceof CompositeExpression) {
                                $queryExpression->add($criterion);

                                if (isset($criterion->parameters) && is_array($criterion->parameters)) {
                                    $queryParameters = array_merge($queryParameters, $criterion->parameters);
                                    unset($criterion->parameters);
                                }
                            } elseif (is_array($criterion)) {
                                list($expr, $parameters) = $this->getFilterExpr($q, $criterion);
                                $queryExpression->add($expr);
                                if (is_array($parameters)) {
                                    $queryParameters = array_merge($queryParameters, $parameters);
                                }
                            } else {
                                //string so parse as advanced search
                                $advancedFilterStrings[] = $criterion;
                            }
                        }
                    } else {
                        //string so parse as advanced search
                        $advancedFilterStrings[] = $criteria;
                    }
                }

                if (!empty($filter['string'])) {
                    $advancedFilterStrings[] = $filter['string'];
                }
            } else {
                $advancedFilterStrings[] = $filter;
            }

            if (!empty($advancedFilterStrings)) {
                foreach ($advancedFilterStrings as $parseString) {
                    $parsed = $filterHelper->parseString($parseString);

                    $advancedFilters->root = array_merge($advancedFilters->root, $parsed->root);
                    $filterHelper->mergeCommands($advancedFilters, $parsed->commands);
                }
                $this->advancedFilterCommands = $advancedFilters->commands;

                list($expr, $parameters) = $this->addAdvancedSearchWhereClause($q, $advancedFilters);
                $this->appendExpression($queryExpression, $expr);

                if (is_array($parameters)) {
                    $queryParameters = array_merge($queryParameters, $parameters);
                }
            }
        }

        //parse the filter if set
        if ($queryExpression->count()) {
            $q->andWhere($queryExpression);
        }

        // Parameters have to be set even if there are no expressions just in case a search command
        // passed back a parameter it used
        foreach ($queryParameters as $k => $v) {
            if ($v === true || $v === false) {
                $q->setParameter($k, $v, 'boolean');
            } else {
                $q->setParameter($k, $v);
            }
        }
    }

    /**
     * @param QueryBuilder|\Doctrine\DBAL\Query\QueryBuilder $query
     * @param array                                          $clauses [['expr' => 'expression', 'col' => 'DB column', 'val' => 'value to search for']]
     * @param $expr
     */
    protected function buildWhereClauseFromArray($query, array $clauses, $expr = null)
    {
        $isOrm       = $query instanceof QueryBuilder;
        $columnValue = ['eq', 'neq', 'lt', 'lte', 'gt', 'gte', 'like', 'notLike', 'in', 'notIn', 'between', 'notBetween'];
        $justColumn  = ['isNull', 'isNotNull', 'isEmpty', 'isNotEmpty'];
        $andOr       = ['andX', 'orX'];

        if ($clauses && is_array($clauses)) {
            foreach ($clauses as $clause) {
                if (!empty($clause['internal']) && 'formula' === $clause['expr']) {
                    $whereClause = array_key_exists('value', $clause) ? $clause['value'] : $clause['val'];
                    if ($expr) {
                        $expr->add($whereClause);
                    } else {
                        $query->andWhere($whereClause);
                    }

                    continue;
                }

                if (in_array($clause['expr'], $andOr)) {
                    $composite = $query->expr()->{$clause['expr']}();
                    $this->buildWhereClauseFromArray($query, $clause['val'], $composite);

                    if (null === $expr) {
                        $query->andWhere($composite);
                    } else {
                        $expr->add($composite);
                    }
                } else {
                    $clause = $this->validateWhereClause($clause);
                    $column = (strpos($clause['col'], '.') === false) ? $this->getTableAlias().'.'.$clause['col'] : $clause['col'];

                    $whereClause = null;
                    switch ($clause['expr']) {
                        case 'between':
                        case 'notBetween':
                            if (is_array($clause['val']) && count($clause['val']) === 2) {
                                $not   = 'notBetween' === $clause['expr'] ? ' NOT' : '';
                                $param = $this->generateRandomParameterName();
                                $query->setParameter($param, $clause['val'][0]);
                                $param2 = $this->generateRandomParameterName();
                                $query->setParameter($param2, $clause['val'][1]);

                                $whereClause = $column.$not.' BETWEEN :'.$param.' AND :'.$param2;
                            }
                            break;
                        case 'isEmpty':
                        case 'isNotEmpty':
                            if ('empty' === $clause['expr']) {
                                $whereClause = $query->expr()->orX(
                                    $query->expr()->eq($column, $query->expr()->literal('')),
                                    $query->expr()->isNull($column)
                                );
                            } else {
                                $whereClause = $query->expr()->andX(
                                    $query->expr()->neq($column, $query->expr()->literal('')),
                                    $query->expr()->isNotNull($column)
                                );
                            }
                            break;
                        case 'in':
                        case 'notIn':
                            if (!$isOrm) {
                                $whereClause = $query->expr()->{$clause['expr']}($column, (array) $clause['val']);
                            } else {
                                $param       = $this->generateRandomParameterName();
                                $whereClause = $query->expr()->{$clause['expr']}($column, ':'.$param);
                                $query->setParameter($param, $clause['val']);
                            }
                        default:
                            if (method_exists($query->expr(), $clause['expr'])) {
                                if (in_array($clause['expr'], $columnValue)) {
                                    $param       = $this->generateRandomParameterName();
                                    $whereClause = $query->expr()->{$clause['expr']}($column, ':'.$param);
                                    $query->setParameter($param, $clause['val']);
                                } elseif (in_array($clause['expr'], $justColumn)) {
                                    $whereClause = $query->expr()->{$clause['expr']}($column);
                                }
                            }
                    }

                    if ($whereClause) {
                        if ($expr) {
                            $expr->add($whereClause);
                        } else {
                            $query->andWhere($whereClause);
                        }
                    }
                }
            }
        }
    }

    /**
     * @return string
     */
    protected function generateRandomParameterName()
    {
        $alpha_numeric = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

        return substr(str_shuffle($alpha_numeric), 0, 8);
    }

    /**
     * @return array
     */
    protected function getDefaultOrder()
    {
        return [];
    }

    /**
     * @param $q
     * @param $filter
     *
     * @return mixed
     */
    protected function getIdsExpr(&$q, $filter)
    {
        if ($ids = array_map('intval', explode(',', $filter->string))) {
            return $q->expr()->in($this->getTableAlias().'.id', $ids);
        }

        return false;
    }

    /**
     * Test to see if a given command is supported by the repository.
     *
     * @param string $command
     * @param string $subcommand
     *
     * @return bool
     */
    protected function isSupportedSearchCommand(&$command, &$subcommand)
    {
        $commands = $this->getSearchCommands();
        foreach ($commands as $k => $c) {
            if (is_array($c)) {
                //subcommands
                if ($this->translator->trans($k) == $command || $this->translator->trans($k, [], null, 'en_US') == $command) {
                    foreach ($c as $subc) {
                        if ($this->translator->trans($subc) == $subcommand || $this->translator->trans($subc, [], null, 'en_US') == $subcommand) {
                            return true;
                        }
                    }
                }
            } elseif ($this->translator->trans($c) == $command || $this->translator->trans($c, [], null, 'en_US') == $command) {
                return true;
            } elseif ($this->translator->trans($c) == "{$command}:{$subcommand}"
                || $this->translator->trans($c, [], null, 'en_US') == "{$command}:{$subcommand}"
            ) {
                $command    = "{$command}:{$subcommand}";
                $subcommand = '';

                return true;
            }
        }

        return false;
    }

    /**
     * @param $parseFilters
     * @param $qb
     * @param $expressions
     * @param $parameters
     */
    protected function parseSearchFilters($parseFilters, $qb, $expressions, &$parameters)
    {
        foreach ($parseFilters as $f) {
            if (isset($f->children)) {
                list($expr, $params) = $this->addAdvancedSearchWhereClause($qb, $f);
            } else {
                if (!empty($f->command)) {
                    if ($this->isSupportedSearchCommand($f->command, $f->string)) {
                        list($expr, $params) = $this->addSearchCommandWhereClause($qb, $f);
                    } else {
                        //treat the command:string as if its a single word
                        $f->string           = $f->command.':'.$f->string;
                        $f->not              = false;
                        $f->strict           = true;
                        list($expr, $params) = $this->addCatchAllWhereClause($qb, $f);
                    }
                } else {
                    list($expr, $params) = $this->addCatchAllWhereClause($qb, $f);
                }
            }
            if (!empty($params)) {
                $parameters = array_merge($parameters, $params);
            }

            $this->appendExpression($expressions, $expr);
        }
    }

    /**
     * @deprecated 2.5 to be removed in 3.0; BC for mispelled method
     *
     * @param $parseFilters
     * @param $qb
     * @param $expressions
     * @param $parameters
     */
    protected function parseSearchFitlers($parseFilters, $qb, $expressions, &$parameters)
    {
        $this->parseSearchFilters($parseFilters, $qb, $expressions, $parameters);
    }

    /**
     * Sanitizes a string to alphanum plus characters in the second argument.
     *
     * @param string $sqlAttr
     * @param array  $allowedCharacters
     *
     * @return string
     */
    protected function sanitize($sqlAttr, $allowedCharacters = [])
    {
        return InputHelper::alphanum($sqlAttr, false, false, $allowedCharacters);
    }

    /**
     * @param array $filters
     * @param array $properties
     */
    private function convertOrmPropertiesToColumns(array &$filters, array $properties)
    {
        foreach ($filters as $k => &$f) {
            $key   = (isset($f['col'])) ? 'col' : 'column';
            $col   = $f[$key];
            $alias = '';
            if (strpos($col, '.') !== false) {
                list($alias, $col) = explode('.', $col);
            }

            if (in_array($col, $properties)) {
                $col = preg_replace('/(?<=\\w)(?=[A-Z])/', '_$1', $col);
                $col = strtolower($col);
            }

            $f[$key] = (!empty($alias)) ? $alias.'.'.$col : $col;
        }
    }
}
