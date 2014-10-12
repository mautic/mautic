<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
namespace Mautic\CoreBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Helper\SearchStringHelper;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class CommonRepository
 *
 * @package Mautic\CoreBundle\Entity
 */
class CommonRepository extends EntityRepository
{

    /**
     * @var Translator
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

    public function setTranslator(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * Set the current user (i.e. from security context) for use within repositories
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
     * Get a single entity
     *
     * @param int $id
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
     * Get a list of entities
     *
     * @param array      $args
     * @return Paginator
     */
    public function getEntities($args = array())
    {
        $alias = $this->getTableAlias();

        if (isset($args['qb'])) {
            $q = $args['qb'];
        } else {
            $q = $this
                ->createQueryBuilder($alias)
                ->select($alias);
        }

        $this->buildClauses($q, $args);
        $query = $q->getQuery();

        if (isset($args['hydration_mode'])) {
            $mode = strtoupper($args['hydration_mode']);
            $query->setHydrationMode(constant("\\Doctrine\\ORM\\Query::$mode"));
        }

        $results = new Paginator($query);
        return $results;
    }

    /**
     * @param      $alias
     * @param null $id
     * @param null $entity
     * @return mixed
     */
    public function checkUniqueAlias($alias, $entity = null)
    {
        $q = $this->createQueryBuilder('e')
            ->select('count(e.id) as aliasCount')
            ->where('e.alias = :alias');
        $q->setParameter('alias', $alias);

        if (!empty($entity)) {
            $q->andWhere('e.id != :id');
            $q->setParameter('id', $entity->getId());
        }

        $results = $q->getQuery()->getSingleResult();
        return $results['aliasCount'];
    }

    /**
     * Save an entity through the repository
     *
     * @param $entity
     * @param $flush true by default; use false if persisting in batches
     * @return int
     */
    public function saveEntity($entity, $flush = true)
    {
        $this->_em->persist($entity);
        if ($flush)
            $this->_em->flush();
    }

    /**
     * Persist an array of entities
     *
     * @param $entities
     */
    public function saveEntities($entities)
    {
        //iterate over the results so the events are dispatched on each delete
        $batchSize = 20;
        foreach ($entities as $k => $entity) {
            $this->saveEntity($entity, false);

            if ((($k + 1) % $batchSize) === 0) {
                $this->_em->flush();
            }
        }
        $this->_em->flush();
    }

    /**
     * Delete an entity through the repository
     *
     * @param $entity
     * @param $flush true by default; use false if persisting in batches
     * @return int
     */
    public function deleteEntity($entity, $flush = true)
    {
        //delete entity
        $this->_em->remove($entity);

        if ($flush)
            $this->_em->flush();
    }

    protected function buildClauses(&$q, array $args)
    {
        $this->buildWhereClause($q, $args);
        $this->buildOrderByClause($q, $args);
        $this->buildLimiterClauses($q, $args);

        return true;
    }

    protected function buildWhereClause(&$q, array $args)
    {
        $filter       = array_key_exists('filter', $args) ? $args['filter'] : '';
        $filterHelper = new SearchStringHelper();
        $string       = '';

        if (!empty($filter)) {
            if (is_array($filter)) {
                if (!empty($filter['force'])) {
                    if (is_array($filter['force'])) {
                        //defined columns with keys of column, expr, value
                        $forceParameters  = array();
                        $forceExpressions = $q->expr()->andX();
                        foreach ($filter['force'] as $f) {
                            list ($expr, $parameters) = $this->getFilterExpr($q, $f);
                            $forceExpressions->add($expr);
                            if (is_array($parameters))
                                $forceParameters = array_merge($forceParameters, $parameters);
                        }
                    } else {
                        //string so parse as advanced search
                        $parsed = $filterHelper->parseSearchString($filter['force']);
                        list($forceExpressions, $forceParameters) = $this->addAdvancedSearchWhereClause($q, $parsed);
                    }
                }

                if (!empty($filter['string'])) {
                    $string = $filter['string'];
                }
            } else {
                $string = $filter;
            }

            //parse the filter if set
            if (!empty($string) || !empty($forceExpressions)) {
                if (!empty($string)) {
                    //remove wildcards passed by user
                    if (strpos($string, '%') !== false) {
                        $string = str_replace('%', '', $string);
                    }

                    $filter = $filterHelper->parseSearchString($string);
                    list($expressions, $parameters) = $this->addAdvancedSearchWhereClause($q, $filter);

                    if (!empty($forceExpressions)) {
                        $expressions->add($forceExpressions);
                        $parameters = array_merge($parameters, $forceParameters);
                    }
                } elseif (!empty($forceExpressions)) {
                    //We do not have a user search but have some required filters
                    $expressions = $forceExpressions;
                    $parameters  = $forceParameters;
                }

                if ($expressions instanceof \Countable) {
                    //dbal
                    $filterCount = count($expressions);
                } else {
                    //orm
                    $filterCount = count($expressions->getParts());
                }

                if (!empty($filterCount)) {
                    $q->where($expressions)
                        ->setParameters($parameters);
                }
            }
        }
    }

    protected function getFilterExpr(&$q, $filter)
    {
        $unique    = $this->generateRandomParameterName();
        $func      = (!empty($filter['operator'])) ? $filter['operator'] : $filter['expr'];
        $parameter = false;
        if (in_array($func, array('isNull', 'isNotNull'))) {
            $expr      = $q->expr()->{$func}($filter['column']);
        } elseif (in_array($func, array('in', 'notIn'))) {
            $expr = $q->expr()->{$func}($filter['column'], $filter['value']);
        } else {
            if (isset($filter['strict']) && !$filter['strict'])
                $filter['value'] = "%{$filter['value']}%";
            $expr = $q->expr()->{$func}($filter['column'], ':' . $unique);
            $parameter = array($unique => $filter['value']);
        }
        if (!empty($filter['not']))
            $expr = $q->expr()->not($expr);
        return  array($expr, $parameter);
    }

    protected function addCatchAllWhereClause(&$qb, $filter)
    {
        return array(
            false,
            array()
        );
    }

    protected function addAdvancedSearchWhereClause(&$qb, $filters)
    {
        if (isset($filters->root)) {
            //function is determined by the second clause type
            $type         = (isset($filters->root[1])) ? $filters->root[1]->type : $filters->root[0]->type;
            $parseFilters =& $filters->root;
        } elseif (isset($filters->children)) {
            $type         = (isset($filters->children[1])) ? $filters->children[1]->type : $filters->children[0]->type;
            $parseFilters =& $filters->children;
        } else {
            $type         = (isset($filters[1])) ? $filters[1]->type : $filters[0]->type;
            $parseFilters =& $filters;
        }

        $parameters  = array();
        $expressions = $qb->expr()->{"{$type}X"}();

        foreach ($parseFilters as $f) {
            if (isset($f->children)) {
                list($expr, $params) = $this->addAdvancedSearchWhereClause($qb, $f);
            } else {
                if (!empty($f->command)) {
                    if ($this->isSupportedSearchCommand($f->command, $f->string)) {
                        list($expr, $params) = $this->addSearchCommandWhereClause($qb, $f);
                    } else {
                        //treat the command:string as if its a single word
                        $f->string = $f->command . ":" . $f->string;
                        $f->not    = false;
                        $f->strict = true;
                        list($expr, $params) = $this->addCatchAllWhereClause($qb, $f);
                    }
                } else {
                    list($expr, $params) = $this->addCatchAllWhereClause($qb, $f);
                }
            }
            if (!empty($params))
                $parameters = array_merge($parameters, $params);

            if (!empty($expr))
                $expressions->add($expr);
        }
        return array($expressions, $parameters);
    }

    /**
     * Array of search commands supported by the repository
     *
     * @return array
     */
    public function getSearchCommands()
    {
        return array();
    }

    /**
     * Test to see if a given command is supported by the repository
     *
     * @param $command
     * @param $subcommand
     * @return bool
     */
    protected function isSupportedSearchCommand($command, $subcommand = '')
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
            }
        }
        return false;
    }

    /**
     * @param QueryBuilder $q
     * @param array        $args
     */
    protected function buildOrderByClause(&$q, array $args)
    {
        $orderBy    = array_key_exists('orderBy', $args) ? $args['orderBy'] : '';
        $orderByDir = array_key_exists('orderByDir', $args) ? $args['orderByDir'] : '';
        if (empty($orderBy)) {
            $defaultOrder = $this->getDefaultOrder();

            foreach ($defaultOrder as $order) {
                $q->addOrderBy($order[0], $order[1]);
            }
        } else {
            //add direction after each column
            $parts = explode(',', $orderBy);
            foreach ($parts as $order) {
                $q->addOrderBy($order, $orderByDir);
            }
        }
    }

    protected function getDefaultOrder()
    {
        return array();
    }

    protected function buildLimiterClauses(&$q, array $args)
    {
        $start      = array_key_exists('start', $args) ? $args['start'] : 0;
        $limit      = array_key_exists('limit', $args) ? $args['limit'] : 30;

        if (!empty($limit)) {
            $q->setFirstResult($start)
                ->setMaxResults($limit);
        }
    }

    protected function generateRandomParameterName()
    {
        $alpha_numeric = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
        return substr(str_shuffle($alpha_numeric), 0, 8);
    }

    /**
     * @param QueryBuilder $q
     * @param              $filter
     * @return array
     */
    protected function addStandardCatchAllWhereClause(&$q, $filter, array $columns)
    {
        $unique  = $this->generateRandomParameterName(); //ensure that the string has a unique parameter identifier
        $string  = ($filter->strict) ? $filter->string : "%{$filter->string}%";

        $expr = $q->expr()->orX();
        foreach ($columns as $col) {
            $expr->add(
                $q->expr()->like($col,  ":$unique")
            );
        }

        if ($filter->not) {
            $expr = $q->expr()->not($expr);
        }
        return array(
            $expr,
            array("$unique" => $string)
        );
    }

    /**
     * @param QueryBuilder $q
     * @param              $filter
     * @return array
     */
    protected function addStandardSearchCommandWhereClause(&$q, $filter)
    {
        $command         = $field = $filter->command;
        $string          = $filter->string;
        $unique          = $this->generateRandomParameterName();
        $returnParameter = true; //returning a parameter that is not used will lead to a Doctrine error
        $expr            = false;
        $prefix          = $this->getTableAlias();

        switch ($command) {
            case $this->translator->trans('mautic.core.searchcommand.is'):
                switch($string) {
                    case $this->translator->trans('mautic.core.searchcommand.ispublished'):
                        $expr = $q->expr()->eq("$prefix.isPublished", 1);
                        break;
                    case $this->translator->trans('mautic.core.searchcommand.isunpublished'):
                        $expr = $q->expr()->eq("$prefix.isPublished", 0);
                        break;
                    case $this->translator->trans('mautic.core.searchcommand.isuncategorized'):
                        $expr = $q->expr()->orX(
                            $q->expr()->isNull("$prefix.category"),
                            $q->expr()->eq("$prefix.category", $q->expr()->literal(''))
                        );
                        break;
                    case $this->translator->trans('mautic.core.searchcommand.ismine'):
                        $expr = $q->expr()->eq("IDENTITY($prefix.createdBy)", $this->currentUser->getId());
                        break;

                }
                $returnParameter = false;
                break;
            case $this->translator->trans('mautic.core.searchcommand.category'):
                $expr = $q->expr()->like("c.alias", ":$unique");
                $filter->strict = true;
                break;
        }

        if ($expr && $filter->not) {
            $expr = $q->expr()->not($expr);
        }

        if (!empty($forceParameters)) {
            $parameters = $forceParameters;
        } elseif (!$returnParameter) {
            $parameters = array();
        } else {
            $string     = ($filter->strict) ? $filter->string : "%{$filter->string}%";
            $parameters = array("$unique" => $string);
        }

        return array( $expr, $parameters );
    }

    /**
     * @return array
     */
    public function getStandardSearchCommands()
    {
        return array(
            'mautic.core.searchcommand.is' => array(
                'mautic.core.searchcommand.ispublished',
                'mautic.core.searchcommand.isunpublished',
                'mautic.core.searchcommand.isuncategorized',
                'mautic.core.searchcommand.ismine',
            ),
            'mautic.core.searchcommand.category'
        );
    }

    /**
     * Returns a andX Expr() that takes into account isPublished, publishUp and publishDown dates
     * The Expr() sets a :now parameter that must be set in the calling function
     *
     * @param $q
     *
     * @return mixed
     */
    public function getPublishedByDateExpression($q, $alias = null)
    {
        if ($alias === null) {
            $alias = $this->getTableAlias();
        }

        return $q->expr()->andX(
            $q->expr()->eq("$alias.isPublished", true),
            $q->expr()->orX(
                $q->expr()->isNull("$alias.publishUp"),
                $q->expr()->gte("$alias.publishUp", ':now')
            ),
            $q->expr()->orX(
                $q->expr()->isNull("$alias.publishDown"),
                $q->expr()->lte("$alias.publishDown", ':now')
            )
        );
    }

    /**
     * @return string
     */
    public function getTableAlias()
    {
        return 'e';
    }

    /**
     * Gets the properties of an ORM entity
     *
     * @param      $entityClass
     * @param bool $convertCamelCase
     *
     * @return array
     */
    public function getBaseColumns($entityClass, $convertCamelCase = false)
    {
        static $baseCols = array();

        if (empty($baseCols[$entityClass])) {
            //get a list of properties from the Lead entity so that anything not listed is a custom field
            $entity = new $entityClass();
            $reflect    = new \ReflectionClass($entity);
            $props      = $reflect->getProperties();

            if ($parentClass = $reflect->getParentClass()) {
                $parentProps = $parentClass->getProperties();
                $props       = array_merge($parentProps, $props);
            }
            $baseCols[$entityClass] = array();
            foreach ($props as $p) {
                if (!in_array($p->name, $baseCols[$entityClass])) {
                    $n = $p->name;
                    if ($convertCamelCase) {
                        $n = preg_replace('/(?<=\\w)(?=[A-Z])/',"_$1", $n);
                        $n = strtolower($n);
                    }
                    $baseCols[$entityClass][] = $n;
                }
            }
        }

        return $baseCols[$entityClass];
    }

    /**
     * Examines the arguments passed to getEntities and converts ORM properties to dBAL column names
     *
     * @param $entityClass
     * @param $args
     *
     * return array
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
                    $col = preg_replace('/(?<=\\w)(?=[A-Z])/',"_$1", $col);
                    $col = strtolower($col);
                }

                $f['column'] = (!empty($alias)) ? $alias . '.' . $col : $col;
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
                        $o = preg_replace('/(?<=\\w)(?=[A-Z])/',"_$1", $o);
                        $o = strtolower($o);
                    }

                    $o = (!empty($alias)) ? $alias . '.' . $o : $o;
                }
            }
        }

        return $args;
    }
}
