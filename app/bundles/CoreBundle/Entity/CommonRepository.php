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

use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Doctrine\ORM\EntityRepository;
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
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var MauticFactory
     */
    protected $factory;

    /**
     * @var User
     */
    protected $currentUser;

    /**
     * Stores the parsed columns and their negate status for addAdvancedSearchWhereClause().
     *
     * @var array
     */
    protected $advancedFilterCommands = [];

    /**
     * @param TranslatorInterface $translator
     */
    public function setTranslator(TranslatorInterface $translator)
    {
        $this->translator = $translator;
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
     * Get a single entity.
     *
     * @param int $id
     *
     * @return null|object
     */
    public function getEntity($id = 0)
    {
        try {
            $entity = $this->find($id);
        } catch (\Exception $e) {
            $entity = null;
        }

        return $entity;
    }

    /**
     * Get a list of entities.
     *
     * @param array $args
     *
     * @return Paginator
     */
    public function getEntities($args = [])
    {
        $alias = $this->getTableAlias();

        if (isset($args['qb'])) {
            $q = $args['qb'];
        } else {
            $q = $this->_em
                ->createQueryBuilder()
                ->select($alias)
                ->from($this->_entityName, $alias, "{$alias}.id");
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
     * @param \Doctrine\ORM\QueryBuilder $q
     * @param array                      $args
     *
     * @return bool
     */
    protected function buildClauses(&$q, array $args)
    {
        $this->buildWhereClause($q, $args);
        $this->buildOrderByClause($q, $args);
        $this->buildLimiterClauses($q, $args);

        return true;
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

        if (!empty($filter)) {
            if (is_array($filter)) {
                if (!empty($filter['force'])) {
                    if (is_array($filter['force'])) {
                        //defined columns with keys of column, expr, value
                        foreach ($filter['force'] as $f) {
                            if ($f instanceof Query\Expr || $f instanceof CompositeExpression) {
                                $queryExpression->add($f);

                                if (isset($f->parameters) && is_array($f->parameters)) {
                                    $queryParameters = array_merge($queryParameters, $f->parameters);
                                    unset($f->parameters);
                                }
                            } elseif (is_array($f)) {
                                list($expr, $parameters) = $this->getFilterExpr($q, $f);
                                $queryExpression->add($expr);
                                if (is_array($parameters)) {
                                    $queryParameters = array_merge($queryParameters, $parameters);
                                }
                            } else {
                                //string so parse as advanced search
                                $advancedFilterStrings[] = $f;
                            }
                        }
                    } else {
                        //string so parse as advanced search
                        $advancedFilterStrings[] = $filter['force'];
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
                $queryExpression->add($expr);
                if (is_array($parameters)) {
                    $queryParameters = array_merge($queryParameters, $parameters);
                }
            }

            //parse the filter if set
            if ($queryExpression->count()) {
                $q->andWhere($queryExpression);
                foreach ($queryParameters as $k => $v) {
                    if ($v === true || $v === false) {
                        $q->setParameter($k, $v, 'boolean');
                    } else {
                        $q->setParameter($k, $v);
                    }
                }
            }
        }
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
     * Array of search commands supported by the repository.
     *
     * @return array
     */
    public function getSearchCommands()
    {
        return [];
    }

    /**
     * @param \Doctrine\ORM\QueryBuilder $qb
     * @param array                      $filter
     *
     * @return array
     */
    protected function addCatchAllWhereClause(&$qb, $filter)
    {
        return [
            false,
            [],
        ];
    }

    /**
     * @param \Doctrine\ORM\QueryBuilder $qb
     * @param array                      $filters
     *
     * @return array
     */
    protected function addAdvancedSearchWhereClause(&$qb, $filters)
    {
        $type         = 'and';
        $parseFilters = [];
        if (isset($filters->root)) {
            // Function is determined by the second clause type
            $type         = (isset($filters->root[1])) ? $filters->root[1]->type : $filters->root[0]->type;
            $parseFilters = &$filters->root;
        } elseif (isset($filters->children)) {
            $type         = (isset($filters->children[1])) ? $filters->children[1]->type : $filters->children[0]->type;
            $parseFilters = &$filters->children;
        } elseif (is_array($filters)) {
            $type         = (isset($filters[1])) ? $filters[1]->type : $filters[0]->type;
            $parseFilters = &$filters;
        }

        $parameters  = [];
        $expressions = $qb->expr()->{"{$type}X"}();

        if ($parseFilters) {
            $this->parseSearchFitlers($parseFilters, $qb, $expressions, $parameters);
        }

        return [$expressions, $parameters];
    }

    /**
     * @param $parseFilters
     * @param $expr
     * @param $parameters
     */
    protected function parseSearchFitlers($parseFilters, $qb, $expressions, &$parameters)
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

            if (!empty($expr)) {
                $expressions->add($expr);
            }
        }
    }

    /**
     * @param $qb
     * @param $filter
     *
     * @return array
     */
    protected function addSearchCommandWhereClause(&$qb, $filter)
    {
        return [false, false];
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
                if ($this->translator->trans($k) == $command) {
                    foreach ($c as $subc) {
                        if ($this->translator->trans($subc) == $subcommand) {
                            return true;
                        }
                    }
                }
            } elseif ($this->translator->trans($c) == $command) {
                return true;
            } elseif ($this->translator->trans($c) == "{$command}:{$subcommand}") {
                $command    = "{$command}:{$subcommand}";
                $subcommand = '';

                return true;
            }
        }

        return false;
    }

    /**
     * @param \Doctrine\ORM\QueryBuilder $q
     * @param array                      $args
     */
    protected function buildOrderByClause(&$q, array $args)
    {
        $orderBy    = array_key_exists('orderBy', $args) ? $args['orderBy'] : '';
        $orderByDir = InputHelper::alphanum(
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
                $order = InputHelper::alphanum($order, false, false, ['_', '.']);

                $q->addOrderBy($order, $orderByDir);
            }
        }
    }

    /**
     * @return array
     */
    protected function getDefaultOrder()
    {
        return [];
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
     * @return string
     */
    protected function generateRandomParameterName()
    {
        $alpha_numeric = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

        return substr(str_shuffle($alpha_numeric), 0, 8);
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
                $expr            = $q->expr()->eq("$prefix.isPublished", ":$unique");
                $forceParameters = [$unique => true];
                break;
            case $this->translator->trans('mautic.core.searchcommand.isunpublished'):
                $expr            = $q->expr()->eq("$prefix.isPublished", ":$unique");
                $forceParameters = [$unique => false];
                break;
            case $this->translator->trans('mautic.core.searchcommand.isuncategorized'):
                $expr = $q->expr()->orX(
                    $q->expr()->isNull("$prefix.category"),
                    $q->expr()->eq("$prefix.category", $q->expr()->literal(''))
                );
                $returnParameter = false;
                break;
            case $this->translator->trans('mautic.core.searchcommand.ismine'):
                $expr            = $q->expr()->eq("IDENTITY($prefix.createdBy)", $this->currentUser->getId());
                $returnParameter = false;
                break;
            case $this->translator->trans('mautic.core.searchcommand.category'):
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
        ];
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
    public function getPublishedByDateExpression($q, $alias = null, $setNowParameter = true, $setTrueParameter = true, $allowNullForPublishedUp = true)
    {
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
     * @return string
     */
    public function getTableAlias()
    {
        return 'e';
    }

    /**
     * Gets the properties of an ORM entity.
     *
     * @param string $entityClass
     * @param bool   $convertCamelCase
     *
     * @return array
     */
    public function getBaseColumns($entityClass, $convertCamelCase = false)
    {
        static $baseCols = [true => [], false => []];

        if (empty($baseCols[$convertCamelCase][$entityClass])) {
            //get a list of properties from the Lead entity so that anything not listed is a custom field
            $entity  = new $entityClass();
            $reflect = new \ReflectionClass($entity);
            $props   = $reflect->getProperties();

            if ($parentClass = $reflect->getParentClass()) {
                $parentProps = $parentClass->getProperties();
                $props       = array_merge($parentProps, $props);
            }

            $baseCols[$convertCamelCase][$entityClass] = [];
            foreach ($props as $p) {
                if (!in_array($p->name, $baseCols[$convertCamelCase][$entityClass])) {
                    $n = $p->name;

                    if ($convertCamelCase) {
                        $n                                                   = preg_replace('/(?<=\\w)(?=[A-Z])/', '_$1', $n);
                        $n                                                   = strtolower($n);
                        $baseCols[$convertCamelCase][$entityClass][$p->name] = $n;
                    } else {
                        $baseCols[$convertCamelCase][$entityClass][] = $n;
                    }
                }
            }
        }

        return $baseCols[$convertCamelCase][$entityClass];
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
            foreach ($args['filter']['force'] as $k => &$f) {
                $col   = $f['column'];
                $alias = '';
                if (strpos($col, '.') !== false) {
                    list($alias, $col) = explode('.', $col);
                }

                if (in_array($col, $properties)) {
                    $col = preg_replace('/(?<=\\w)(?=[A-Z])/', '_$1', $col);
                    $col = strtolower($col);
                }

                $f['column'] = (!empty($alias)) ? $alias.'.'.$col : $col;
            }
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
     * Gets a list of published entities as an array id => label.
     *
     * @param CompositeExpression $expr        Use $factory->getDatabase()->getExpressionBuilder()->andX()
     * @param array               $parameters  Parameters used in $expr
     * @param string              $labelColumn Column that houses the label
     * @param string              $valueColumn Column that houses the value
     *
     * @return array
     */
    public function getSimpleList(CompositeExpression $expr = null, array $parameters = [], $labelColumn = null, $valueColumn = 'id')
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

        $q->select($prefix.$valueColumn.' as value, '.$prefix.$labelColumn.' as label')
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
}
