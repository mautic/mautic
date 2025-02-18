<?php

namespace Mautic\CoreBundle\Entity;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\ExpressionBuilder;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Doctrine\DBAL\Query\QueryBuilder as DbalQueryBuilder;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use Mautic\CoreBundle\Cache\ResultCacheHelper;
use Mautic\CoreBundle\Cache\ResultCacheOptions;
use Mautic\CoreBundle\Doctrine\Paginator\SimplePaginator;
use Mautic\CoreBundle\Event\GlobalSearchEvent;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Helper\SearchStringHelper;
use Mautic\UserBundle\Entity\User;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @template T of object
 *
 * @extends ServiceEntityRepository<T>
 */
class CommonRepository extends ServiceEntityRepository
{
    /**
     * @phpstan-param class-string<T>|null $entityFQCN
     */
    public function __construct(ManagerRegistry $registry, string $entityFQCN = null)
    {
        parent::__construct($registry, $entityFQCN ?? str_replace('Repository', '', static::class));
    }

    /**
     * Stores the parsed columns and their negate status for addAdvancedSearchWhereClause().
     *
     * @var array
     */
    protected $advancedFilterCommands = [];

    /**
     * @var User|null
     */
    protected $currentUser;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * This eliminates chance for parameter name collision.
     *
     * @see CommonRepository::generateRandomParameterName()
     *
     * @var int
     */
    protected $lastUsedParameterId = 0;

    /**
     * @var ExpressionBuilder|null
     */
    private $expressionBuilder;

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
     */
    public function convertOrmProperties($entityClass, array $args): array
    {
        $properties = $this->getBaseColumns($entityClass);

        // check force filters
        if (isset($args['filter']['force']) && is_array($args['filter']['force'])) {
            $this->convertOrmPropertiesToColumns($args['filter']['force'], $properties);
        }

        if (isset($args['filter']['where']) && is_array($args['filter']['where'])) {
            $this->convertOrmPropertiesToColumns($args['filter']['where'], $properties);
        }

        // check order by
        if (isset($args['order'])) {
            if (is_array($args['order'])) {
                foreach ($args['order'] as &$o) {
                    $alias = '';
                    if (str_contains($o, '.')) {
                        [$alias, $o] = explode('.', $o);
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
     * @param class-string $className
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
    public function deleteEntities($entities): void
    {
        // iterate over the results so the events are dispatched on each delete
        $batchSize = 20;
        $i         = 0;
        foreach ($entities as $entity) {
            $this->deleteEntity($entity, false);

            if (0 === ++$i % $batchSize) {
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
     */
    public function deleteEntity($entity, $flush = true): void
    {
        // delete entity
        $this->_em->remove($entity);

        if ($flush) {
            $this->_em->flush();
        }
    }

    public function detachEntities(array $entities): void
    {
        foreach ($entities as $entity) {
            $this->getEntityManager()->detach($entity);
        }
    }

    /**
     * @param mixed $entity
     */
    public function detachEntity($entity): void
    {
        $this->getEntityManager()->detach($entity);
    }

    public function refetchEntity(object &$entity): void
    {
        if ($this->getEntityManager()->contains($entity)) {
            $this->getEntityManager()->detach($entity);

            $metadata         = $this->getEntityManager()->getClassMetadata(ClassUtils::getClass($entity));
            $identifierValues = $metadata->getIdentifierValues($entity);
            if (count($identifierValues) > 1) {
                throw new \RuntimeException('Multiple identifiers are not supported.');
            }

            $entity = $this->getEntity(array_pop($identifierValues));
        }
    }

    /**
     * @return mixed|null
     */
    public function findOneBySlugs($alias, $catAlias = null, $lang = null)
    {
        try {
            $q = $this->createQueryBuilder($this->getTableAlias())
                      ->setParameter('alias', $alias);

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
        } catch (\Exception) {
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
     * @param array<string, string|array<int, array<int|string, int|string|bool|null>>> $filter
     */
    public function getEntitiesForGlobalSearch(array $filter): Paginator
    {
        $args = [
            'filter'           => $filter,
            'start'            => 0,
            'limit'            => GlobalSearchEvent::RESULTS_LIMIT,
            'ignore_paginator' => false,
        ];

        return $this->getEntities($args);
    }

    /**
     * Get a list of entities.
     *
     * @param array<string,mixed> $args
     *
     * @return object[]|array<int,mixed>|iterable<object>|\Doctrine\ORM\Internal\Hydration\IterableResult<object>|Paginator<object>|SimplePaginator<mixed>
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
                ->from($this->getEntityName(), $alias, "{$alias}.id");

            if ($this->getClassMetadata()->hasAssociation('category')) {
                $q->leftJoin($this->getTableAlias().'.category', 'cat');
            }
        }

        $this->buildClauses($q, $args);
        $query = $q->getQuery();

        if (isset($args['result_cache'])) {
            if (!$args['result_cache'] instanceof ResultCacheOptions) {
                throw new \InvalidArgumentException(sprintf('The value of the key "result_cache" must be an instance of "%s"', ResultCacheOptions::class));
            }

            ResultCacheHelper::enableOrmQueryCache($query, $args['result_cache']);
        }

        if (isset($args['hydration_mode'])) {
            $hydrationMode = constant('\\Doctrine\\ORM\\Query::'.strtoupper($args['hydration_mode']));
            $query->setHydrationMode($hydrationMode);
        } else {
            $hydrationMode = Query::HYDRATE_OBJECT;
        }

        if (array_key_exists('iterable_mode', $args) && true === $args['iterable_mode']) {
            // Hydrate one by one
            return $query->toIterable([], $hydrationMode);
        }

        if (!empty($args['iterator_mode'])) {
            // When you remove the following, please search for the "iterator_mode" in the project.
            @\trigger_error('Using "iterator_mode" is deprecated. Use "iterable_mode" instead. Usage of "iterator_mode" will be removed in 6.0.', \E_USER_DEPRECATED);

            return $query->iterate(null, $hydrationMode);
        } elseif (empty($args['ignore_paginator'])) {
            if (!empty($args['use_simple_paginator'])) {
                // FAST paginator that can handle only simple queries using no joins or ManyToOne joins.
                return new SimplePaginator($query);
            } else {
                // SLOW paginator that can handle complex queries using oneToMany/ManyToMany joins.
                return new Paginator($query, false);
            }
        } else {
            // All results
            return $query->getResult($hydrationMode);
        }
    }

    /**
     * Get a single entity.
     *
     * @param int $id
     */
    public function getEntity($id = 0): ?object
    {
        try {
            if (is_array($id)) {
                $q = $this->createQueryBuilder($this->getTableAlias());
                $this->buildSelectClause($q, $id['select']);
                $q->where($this->getTableAlias().'.id = :id')
                ->setParameter('id', (int) $id['id']);
                $entity = $q->getQuery()->getSingleResult();
            } else {
                $entity = $this->find((int) $id);
            }
        } catch (\Exception) {
            $entity = null;
        }

        return $entity;
    }

    /**
     * @return ExpressionBuilder
     */
    public function getExpressionBuilder()
    {
        if (null === $this->expressionBuilder) {
            $this->expressionBuilder = new ExpressionBuilder();
        }

        return $this->expressionBuilder;
    }

    /**
     * @param QueryBuilder|DbalQueryBuilder $q
     * @param array<mixed>                  $filter
     */
    public function getFilterExpr($q, array $filter, ?string $unique = null): array
    {
        $unique    = ($unique) ?: $this->generateRandomParameterName();
        $parameter = [];

        if (isset($filter['group'])) {
            $expr = $q->expr()->orX();
            foreach ($filter['group'] as $orGroup) {
                $groupExpr = $q->expr()->andX();
                foreach ($orGroup as $subFilter) {
                    [$subExpr, $subParameters] = $this->getFilterExpr($q, $subFilter);

                    $groupExpr->add($subExpr);
                    if (!empty($subParameters)) {
                        $parameter = array_merge($parameter, $subParameters);
                    }
                }
                $expr->add($groupExpr);
            }
        } elseif (str_contains($filter['column'], ',')) {
            $columns      = explode(',', $filter['column']);
            $expr         = $q->expr()->orX();
            $setParameter = false;
            foreach ($columns as $c) {
                $subFilter           = $filter;
                $subFilter['column'] = trim($c);

                [$subExpr, $parameterUsed] = $this->getFilterExpr($q, $subFilter, $unique);

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
                $expr = $q->expr()->{$func}($filter['column'], ':'.$unique);
                $q->setParameter($unique, $filter['value'], ArrayParameterType::STRING);
            } elseif (in_array($func, ['like', 'notLike'])) {
                if (isset($filter['strict']) && !$filter['strict']) {
                    if (is_numeric($filter['value'])) {
                        // Postgres doesn't like using "LIKE" with numbers
                        $func = ('like' == $func) ? 'eq' : 'neq';
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
     * @param string|null $alias
     * @param bool        $setNowParameter
     * @param bool        $setTrueParameter
     * @param bool        $allowNullForPublishedUp Allow entities without a published up date
     *
     * @return mixed
     */
    public function getPublishedByDateExpression(
        $q,
        $alias = null,
        $setNowParameter = true,
        $setTrueParameter = true,
        $allowNullForPublishedUp = true,
    ) {
        $isORM = $q instanceof QueryBuilder;

        if (null === $alias) {
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
     * @param int $start
     * @param int $limit
     */
    public function getRows($start = 0, $limit = 100, array $order = [], array $where = [], array $select = null, array $allowedJoins = []): array
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

        $count = $q->executeQuery()->fetchOne();

        if ($select) {
            foreach ($select as &$column) {
                if (!str_contains($column, '.')) {
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

        $results = $q->executeQuery()->fetchAllAssociative();

        return [
            'total'   => $count,
            'results' => $results,
        ];
    }

    /**
     * Returns a single value for a single row.
     *
     * @param int    $id
     * @param string $column
     *
     * @return string|null
     */
    public function getValue($id, $column)
    {
        $q = $this->_em->getConnection()->createQueryBuilder();
        $q->select($this->getTableAlias().'.'.$column)
            ->from($this->getClassMetadata()->getTableName(), $this->getTableAlias())
            ->where($this->getTableAlias().'.id = :id')
            ->setParameter('id', $id);

        $result = $q->executeQuery()->fetchAssociative();

        return $result[$column] ?? null;
    }

    /**
     * Array of search commands supported by the repository.
     *
     * @return string[]|array<string, string[]>
     */
    public function getSearchCommands(): array
    {
        return ['mautic.core.searchcommand.ids'];
    }

    /**
     * Gets a list of published entities as an array id => label.
     *
     * @param array  $parameters   Parameters used in $expr
     * @param string $labelColumn  Column that houses the label
     * @param string $valueColumn  Column that houses the value
     * @param string $extraColumns String of extra select columns
     * @param int    $limit        Limit for results
     *
     * @return mixed[]
     */
    public function getSimpleList(CompositeExpression $expr = null, array $parameters = [], $labelColumn = null, $valueColumn = 'id', $extraColumns = null, $limit = 0): array
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
            $labelColumn = $reflection->hasMethod('getTitle') ? 'title' : 'name';
        }

        $q->select($prefix.$valueColumn.' as value, '.$prefix.$labelColumn.' as label'.($extraColumns ? ", $extraColumns" : ''))
          ->from($tableName, $alias)
          ->orderBy($prefix.$labelColumn);

        if (null !== $expr && $expr->count()) {
            $q->where($expr);
        }

        foreach ($parameters as $key => $value) {
            $q->setParameter($key, $value, is_array($value) ? ArrayParameterType::STRING : null);
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

        return $q->executeQuery()->fetchAllAssociative();
    }

    /**
     * @return string[]
     */
    public function getStandardSearchCommands(): array
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
     * @return literal-string
     */
    public function getTableAlias(): string
    {
        return 'e';
    }

    /**
     * @return mixed[]
     */
    public function getTableColumns(): array
    {
        $columns = $this->getClassMetadata()->getColumnNames();

        if ($associations = $this->getClassMetadata()->getAssociationMappings()) {
            foreach ($associations as $association) {
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
     * @param array|ArrayCollection $entities
     */
    public function saveEntities($entities): void
    {
        // iterate over the results so the events are dispatched on each delete
        $batchSize = 20;
        $i         = 0;

        foreach ($entities as $entity) {
            $this->saveEntity($entity, false);

            if (0 === ++$i % $batchSize) {
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
     */
    public function saveEntity($entity, $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Insert entity if it does not exist, update if it does.
     * ID is set to the enity after upsert.
     * Main reason to use this over fetch/save is to avoid race conditions.
     *
     * Warning: This method use DBAL, not ORM. It will save only the entity you send it.
     * It will NOT save the entity's associations. Entity manager won't know that the entity was flushed.
     */
    public function upsert(object $entity): void
    {
        $connection = $this->getEntityManager()->getConnection();
        $metadata   = $this->getClassMetadata();
        $identifier = $metadata->getSingleIdentifierFieldName();
        $makeUpdate = fn (string $column) => "{$column} = VALUES({$column})";
        $columns    = [];
        $values     = [];
        $types      = [];
        $set        = [];
        $update     = [];
        $hasId      = $metadata->containsForeignIdentifier;

        foreach ($metadata->getFieldNames() as $fieldName) {
            $value = $metadata->getFieldValue($entity, $fieldName);
            if ($metadata->isIdentifier($fieldName)) {
                if ($value) {
                    $hasId = true;
                } elseif ($fieldName === $identifier) {
                    // https://bugs.php.net/bug.php?id=76896
                    // mysql_last_insert_id might return 0 if our insert updates a row
                    // Call LAST_INSERT_ID() for the column to ensure the correct value
                    $column   = $metadata->getColumnName($fieldName);
                    $update[] = "{$column} = LAST_INSERT_ID({$column})";
                    continue;
                } else {
                    continue;
                }
            }
            $column    = $metadata->getColumnName($fieldName);
            $columns[] = $column;
            $values[]  = $value;
            $types[]   = $metadata->getTypeOfField($fieldName);
            $set[]     = '?';
            $update[]  = $makeUpdate($column);
        }

        foreach ($metadata->getAssociationNames() as $fieldName) {
            $assocEntity = $metadata->getFieldValue($entity, $fieldName);
            if (!$metadata->isAssociationWithSingleJoinColumn($fieldName) || !is_object($assocEntity)) {
                continue;
            }
            $idCol     = ucfirst($metadata->getSingleAssociationReferencedJoinColumnName($fieldName));
            $idGetter  = "get{$idCol}";
            $column    = $metadata->getSingleAssociationJoinColumnName($fieldName);
            $columns[] = $column;
            $values[]  = $assocEntity->$idGetter();
            $types[]   = Types::STRING;
            $set[]     = '?';
            $update[]  = $makeUpdate($column);
        }

        $numberOfRowsAffected = $connection->executeStatement(
            'INSERT INTO '.$this->getTableName().' ('.implode(', ', $columns).')'.
            ' VALUES ('.implode(', ', $set).')'.
            ' ON DUPLICATE KEY UPDATE '.implode(', ', $update),
            $values,
            $types
        );

        if ($entity instanceof UpsertInterface) {
            $entity->setHasBeenInserted(UpsertInterface::ROWS_AFFECTED_ON_INSERT === $numberOfRowsAffected);
            $entity->setHasBeenUpdated(UpsertInterface::ROWS_AFFECTED_ON_UPDATE === $numberOfRowsAffected);
        }
        if ($hasId) {
            return;
        }

        $id = (int) $connection->lastInsertId();

        $metadata->setFieldValue($entity, $identifier, $id);
    }

    /**
     * Set the current user (i.e. from security context) for use within repositories.
     *
     * @param User $user
     */
    public function setCurrentUser($user): void
    {
        if (!$user instanceof User) {
            // just create a blank user entity
            $user = new User();
        }
        $this->currentUser = $user;
    }

    public function setTranslator(TranslatorInterface $translator): void
    {
        $this->translator = $translator;
    }

    /**
     * Validate array for one order by condition.
     *
     * @param array $clause ['col' => 'column_a', 'dir' => 'ASC']
     *
     * @return array
     *
     * @throws \InvalidArgumentException
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
        $clause['col'] = $this->sanitize($clause['col'], ['_.']);

        return $clause;
    }

    /**
     * Validate the array for one where condition.
     *
     * @param array $clause ['expr' => 'expression', 'col' => 'DB column', 'val' => 'value to search for']
     *
     * @throws \InvalidArgumentException
     */
    protected function validateWhereClause(array $clause): array
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
        $clause['col']  = $this->sanitize($clause['column'] ?? $clause['col'], ['_', '.']);
        if (isset($clause['value'])) {
            $clause['val'] = $clause['value'];
        }
        unset($clause['value'], $clause['column']);

        // Value will be sanitized by Doctrine

        return $clause;
    }

    /**
     * @param QueryBuilder|DbalQueryBuilder $qb
     * @param \StdClass|mixed[]             $filters
     *
     * @return mixed[]
     */
    protected function addAdvancedSearchWhereClause($qb, $filters): array
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
     * @param QueryBuilder|DbalQueryBuilder $qb
     * @param \StdClass                     $filter
     */
    protected function addCatchAllWhereClause($qb, $filter): array
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
     */
    protected function addDbalCatchAllWhereClause(&$q, $filter, array $columns): array
    {
        $unique = $this->generateRandomParameterName(); // ensure that the string has a unique parameter identifier
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
     * @param QueryBuilder|DbalQueryBuilder $q
     * @param \StdClass                     $filter
     */
    protected function addSearchCommandWhereClause($q, $filter): array
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
     * @param QueryBuilder $q
     * @param object       $filter
     */
    protected function addStandardCatchAllWhereClause(&$q, $filter, array $columns): array
    {
        $unique = $this->generateRandomParameterName(); // ensure that the string has a unique parameter identifier
        $string = $filter->string;

        if (!$filter->strict) {
            if (!str_contains($string, '%')) {
                $string = "%$string%";
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

        if ($filter->not) {
            $expr = $q->expr()->not($expr);
        }

        return [
            $expr,
            ["$unique" => $string],
        ];
    }

    /**
     * @param DbalQueryBuilder|QueryBuilder $q
     * @param \StdClass                     $filter
     */
    protected function addStandardSearchCommandWhereClause(&$q, $filter): array
    {
        $command         = $filter->command;
        $unique          = $this->generateRandomParameterName();
        $returnParameter = true; // returning a parameter that is not used will lead to a Doctrine error
        $expr            = false;
        $prefix          = $this->getTableAlias();
        $isDbalQB        = $q instanceof DbalQueryBuilder;

        switch ($command) {
            case $this->translator->trans('mautic.core.searchcommand.ispublished'):
            case $this->translator->trans('mautic.core.searchcommand.ispublished', [], null, 'en_US'):
                $column          = $isDbalQB ? 'is_published' : 'isPublished';
                $expr            = $q->expr()->eq("{$prefix}.{$column}", ":{$unique}");
                $forceParameters = [$unique => true];
                break;
            case $this->translator->trans('mautic.core.searchcommand.isunpublished'):
            case $this->translator->trans('mautic.core.searchcommand.isunpublished', [], null, 'en_US'):
                $column          = $isDbalQB ? 'is_published' : 'isPublished';
                $expr            = $q->expr()->eq("{$prefix}.{$column}", ":{$unique}");
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
                $column          = $isDbalQB ? 'created_by' : 'createdBy';
                $expr            = $q->expr()->eq("{$prefix}.{$column}", ":{$unique}");
                $forceParameters = [$unique => $this->currentUser->getId()];
                break;
            case $this->translator->trans('mautic.core.searchcommand.category'):
            case $this->translator->trans('mautic.core.searchcommand.category', [], null, 'en_US'):
                // Find the category prefix
                $joins     = $q->getDQLPart('join');
                $catPrefix = false;
                foreach ($joins as $joinStatements) {
                    /** @var Query\Expr\Join $join */
                    foreach ($joinStatements as $join) {
                        if (str_contains($join->getJoin(), '.category')) {
                            $catPrefix = $join->getAlias();
                            break;
                        }
                    }
                    if (false !== $catPrefix) {
                        break;
                    }
                }
                if (false === $catPrefix) {
                    $catPrefix = 'c';
                }
                $expr           = $q->expr()->like("{$catPrefix}.alias", ":{$unique}");
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
                if (!str_contains($string, '%')) {
                    $string = "$string%";
                }
            }

            $parameters = ["$unique" => $string];
        }

        return [$expr, $parameters];
    }

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
     * @param QueryBuilder $q
     */
    protected function buildClauses($q, array $args): bool
    {
        $this->buildSelectClause($q, $args);
        $this->buildIndexByClause($q, $args);
        $this->buildWhereClause($q, $args);
        $this->buildOrderByClause($q, $args);
        $this->buildLimiterClauses($q, $args);

        return true;
    }

    protected function buildDbalJoinsFromAssociations(DbalQueryBuilder $q, $associations, $alias, array $allowed): bool
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

    protected function buildIndexByClause($q, array $args)
    {
        if (!empty($args['index_by'])) {
            if (is_array($args['index_by'])) {
                [$indexAlias, $indexBy] = $args['index_by'];
            } else {
                $indexAlias = $this->getTableAlias();
                $indexBy    = $args['index_by'];
            }
            if (!str_starts_with($indexBy, $indexAlias)) {
                $indexBy = $indexAlias.'.'.$indexBy;
            }
            $q->indexBy($indexAlias, $indexBy);
        }
    }

    /**
     * @param QueryBuilder|DbalQueryBuilder $q
     */
    protected function buildLimiterClauses($q, array $args): void
    {
        $start = array_key_exists('start', $args) ? $args['start'] : 0;
        $limit = array_key_exists('limit', $args) ? $args['limit'] : 0;

        if (!empty($limit)) {
            $q->setFirstResult($start)
              ->setMaxResults($limit);
        }
    }

    /**
     * @param QueryBuilder|DbalQueryBuilder $q
     */
    protected function buildOrderByClause($q, array $args): void
    {
        $orderBy = array_key_exists('orderBy', $args) ? $args['orderBy'] : '';

        if (!empty($args['filter']['order'])) {
            $this->buildOrderByClauseFromArray($q, $args['filter']['order']);
        } elseif (empty($orderBy)) {
            $defaultOrder = $this->getDefaultOrder();

            foreach ($defaultOrder as $order) {
                $q->addOrderBy($order[0], $order[1]);
            }
        } else {
            $orderByDir = $this->sanitize(
                array_key_exists('orderByDir', $args) ? $args['orderByDir'] : ''
            );
            // add direction after each column
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
     * @param QueryBuilder|DbalQueryBuilder $query
     * @param array                         $clauses [['col' => 'column_a', 'dir' => 'ASC']]
     */
    protected function buildOrderByClauseFromArray($query, array $clauses): void
    {
        foreach ($clauses as $clause) {
            $clause = $this->validateOrderByClause($clause);
            $column = (!str_contains($clause['col'], '.')) ? $this->getTableAlias().'.'.$clause['col'] : $clause['col'];
            $query->addOrderBy($column, $clause['dir']);
        }
    }

    /**
     * @param QueryBuilder|DbalQueryBuilder $q
     */
    protected function buildSelectClause($q, array $args)
    {
        $isOrm = $q instanceof QueryBuilder;
        if (isset($args['select'])) {
            // Build a custom select
            if (is_string($args['select'])) {
                $args['select'] = explode(',', $args['select']);
            }

            $selects = [];
            foreach ($args['select'] as $select) {
                if (str_contains($select, '.')) {
                    [$alias, $select] = explode('.', $select);
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
                if ($isOrm) {
                    $q->select($newSelect);
                } else {
                    if (!$select || $this->getTableAlias() === $select || $this->getTableAlias().'.*' === $select) {
                        $q->select($newSelect);
                    } elseif (is_string($select) && str_contains($select, $this->getTableAlias().',')) {
                        $q->select(str_replace($this->getTableAlias().',', $newSelect.',', $select));
                    } elseif (is_string($select) && str_contains($select, $this->getTableAlias().'.*,')) {
                        $q->select(str_replace($this->getTableAlias().'.*,', $newSelect.',', $select));
                    }
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
     * @param QueryBuilder|DbalQueryBuilder $q
     */
    protected function buildWhereClause($q, array $args)
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
            $ids   = array_map('intval', $args['ids']);
            $param = $this->generateRandomParameterName();
            if ($q instanceof QueryBuilder) {
                $queryExpression->add(
                    $q->expr()->in($this->getTableAlias().'.id', ':'.$param)
                );
                $queryParameters[$param] = $ids;
            } else {
                $queryExpression->add(
                    $q->expr()->in($this->getTableAlias().'.id', ':'.$param)
                );
                $q->setParameter($param, $ids, ArrayParameterType::INTEGER);
            }
        } elseif (!empty($args['ownedBy'])) {
            $queryExpression->add(
                $q->expr()->in($this->getTableAlias().'.'.$args['ownedBy'][0], (string) $args['ownedBy'][1])
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
                        // defined columns with keys of column, expr, value
                        foreach ($criteria as $criterion) {
                            if ($criterion instanceof Query\Expr || $criterion instanceof CompositeExpression) {
                                $queryExpression->add($criterion);

                                if (isset($criterion->parameters) && is_array($criterion->parameters)) {
                                    $queryParameters = array_merge($queryParameters, $criterion->parameters);
                                    unset($criterion->parameters);
                                }
                            } elseif (is_array($criterion)) {
                                [$expr, $parameters] = $this->getFilterExpr($q, $criterion);
                                $queryExpression->add($expr);
                                if (is_array($parameters)) {
                                    $queryParameters = array_merge($queryParameters, $parameters);
                                }
                            } else {
                                // string so parse as advanced search
                                $advancedFilterStrings[] = $criterion;
                            }
                        }
                    } else {
                        // string so parse as advanced search
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

                [$expr, $parameters] = $this->addAdvancedSearchWhereClause($q, $advancedFilters);
                $this->appendExpression($queryExpression, $expr);

                if (is_array($parameters)) {
                    $queryParameters = array_merge($queryParameters, $parameters);
                }
            }
        }

        // parse the filter if set
        if ($queryExpression->count()) {
            $q->andWhere($queryExpression);
        }

        // Parameters have to be set even if there are no expressions just in case a search command
        // passed back a parameter it used
        foreach ($queryParameters as $k => $v) {
            if (true === $v || false === $v) {
                $q->setParameter($k, $v, 'boolean');
            } else {
                $q->setParameter($k, $v);
            }
        }
    }

    /**
     * @param QueryBuilder|DbalQueryBuilder $query
     * @param array                         $clauses [['expr' => 'expression', 'col' => 'DB column', 'val' => 'value to search for']]
     */
    protected function buildWhereClauseFromArray($query, array $clauses, $expr = null)
    {
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
                    $column = (!str_contains($clause['col'], '.')) ? $this->getTableAlias().'.'.$clause['col'] : $clause['col'];

                    $whereClause = null;
                    switch ($clause['expr']) {
                        case 'between':
                        case 'notBetween':
                            if (is_array($clause['val']) && 2 === count($clause['val'])) {
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
                            if ('isEmpty' === $clause['expr']) {
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
                            $parsed = str_getcsv(html_entity_decode($clause['val']), ',', '"');

                            $param = $this->generateRandomParameterName();
                            $arg   = count($parsed) > 1 ? $parsed : array_shift($parsed);

                            if (is_array($arg)) {
                                $whereClause = $query->expr()->{$clause['expr']}($column, ':'.$param);
                                $query->setParameter($param, $arg, ArrayParameterType::STRING);
                            } else {
                                $expression  = 'in' === $clause['expr'] ? 'eq' : 'neq';
                                $whereClause = $query->expr()->{$expression}($column, ':'.$param);
                                $query->setParameter($param, $arg);
                            }
                            break;
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
     * Generate a unique parameter name from int using base conversion.
     * This eliminates chance for parameter name collision and provides unique result for each number.
     * Duplicate method because of DI refactoring difficulty.
     *
     * @see \Mautic\LeadBundle\Segment\RandomParameterName
     * @see https://stackoverflow.com/questions/307486/short-unique-id-in-php/1516430#1516430
     */
    public function generateRandomParameterName(): string
    {
        $value = base_convert((string) $this->lastUsedParameterId, 10, 36);

        ++$this->lastUsedParameterId;

        return 'par'.$value;
    }

    protected function getDefaultOrder(): array
    {
        return [];
    }

    /**
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
     */
    protected function isSupportedSearchCommand(&$command, &$subcommand): bool
    {
        $commands = $this->getSearchCommands();
        foreach ($commands as $k => $c) {
            if (is_array($c)) {
                // subcommands
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
     * @param \StdClass                     $parseFilters
     * @param QueryBuilder|DbalQueryBuilder $qb
     */
    protected function parseSearchFilters($parseFilters, $qb, $expressions, &$parameters)
    {
        foreach ($parseFilters as $f) { /** @phpstan-ignore-line we are iterating over StdClass. We should refactor this into a collection of DTO objects in M6 */
            if (isset($f->children)) {
                [$expr, $params] = $this->addAdvancedSearchWhereClause($qb, $f);
            } else {
                if (!empty($f->command)) {
                    if ($this->isSupportedSearchCommand($f->command, $f->string)) {
                        [$expr, $params] = $this->addSearchCommandWhereClause($qb, $f);
                    } else {
                        // treat the command:string as if its a single word
                        $f->string       = $f->command.':'.$f->string;
                        $f->not          = false;
                        $f->strict       = true;
                        [$expr, $params] = $this->addCatchAllWhereClause($qb, $f);
                    }
                } else {
                    [$expr, $params] = $this->addCatchAllWhereClause($qb, $f);
                }
            }
            if (!empty($params)) {
                $parameters = array_merge($parameters, $params);
            }

            $this->appendExpression($expressions, $expr);
        }
    }

    /**
     * Sanitizes a string to alphanum plus characters in the second argument.
     *
     * @param string $sqlAttr
     * @param array  $allowedCharacters
     */
    protected function sanitize($sqlAttr, $allowedCharacters = []): string
    {
        return InputHelper::alphanum($sqlAttr, false, null, $allowedCharacters);
    }

    private function convertOrmPropertiesToColumns(array &$filters, array $properties): void
    {
        foreach ($filters as &$f) {
            $key   = (isset($f['col'])) ? 'col' : 'column';
            $col   = $f[$key];
            $alias = '';
            if (str_contains($col, '.')) {
                [$alias, $col] = explode('.', $col);
            }

            if (in_array($col, $properties)) {
                $col = preg_replace('/(?<=\\w)(?=[A-Z])/', '_$1', $col);
                $col = strtolower($col);
            }

            $f[$key] = (!empty($alias)) ? $alias.'.'.$col : $col;
        }
    }

    /**
     * Checks if table contains any rows.
     */
    protected function tableHasRows(string $table): bool
    {
        $query = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $query->select('null')
            ->from($table)
            ->setMaxResults(1);

        return (bool) count($query->executeQuery()->fetchAllAssociative());
    }
}
