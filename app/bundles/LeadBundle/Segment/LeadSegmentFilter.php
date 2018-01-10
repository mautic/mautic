<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Segment;

use Doctrine\Common\Persistence\Mapping\MappingException;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Column;
use Doctrine\ORM\EntityManager;
use Mautic\LeadBundle\Services\LeadSegmentFilterDescriptor;
use Mautic\LeadBundle\Services\LeadSegmentFilterQueryBuilderTrait;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;

class LeadSegmentFilter
{
    use LeadSegmentFilterQueryBuilderTrait;

    const LEAD_OBJECT = 'lead';
    const COMPANY_OBJECT = 'company';

    /**
     * @var string|null
     */
    private $glue;

    /**
     * @var string|null
     */
    private $field;

    /**
     * @var string|null
     */
    private $object;

    /**
     * @var string|null
     */
    private $type;

    /**
     * @var string|array|bool|float|null
     */
    private $filter;

    /**
     * @var string|null
     */
    private $display;

    /**
     * @var string|null
     */
    private $operator;

    /**
     * @var string
     */
    private $func;

    /**
     * @var array
     */
    private $queryDescription = null;

    /** @var Column */
    private $dbColumn;

    /** @var EntityManager  */
    private $em;

    public function __construct(array $filter, \ArrayIterator $dictionary = null, EntityManager $em = null)
    {
        $this->glue    = isset($filter['glue']) ? $filter['glue'] : null;
        $this->field   = isset($filter['field']) ? $filter['field'] : null;
        $this->object  = isset($filter['object']) ? $filter['object'] : self::LEAD_OBJECT;
        $this->type    = isset($filter['type']) ? $filter['type'] : null;
        $this->display = isset($filter['display']) ? $filter['display'] : null;
        $this->func    = isset($filter['func']) ? $filter['func'] : null;
        $operatorValue = isset($filter['operator']) ? $filter['operator'] : null;
        $this->setOperator($operatorValue);

        $filterValue = isset($filter['filter']) ? $filter['filter'] : null;
        $this->setFilter($filterValue);
        $this->em = $em;
        if (!is_null($dictionary)) {
            $this->translateQueryDescription($dictionary);
        }
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getSQLOperator()
    {
        switch ($this->getOperator()) {
            case 'gt':
                return '>';
            case 'eq':
                return '=';
            case 'gt':
                return '>';
            case 'gte':
                return '>=';
            case 'lt':
                return '<';
            case 'lte':
                return '<=';
        }
        throw new \Exception(sprintf('Unknown operator \'%s\'.', $this->getOperator()));
    }

    public function getFilterConditionValue($argument = null) {
        switch ($this->getDBColumn()->getType()->getName()) {
            case 'number':
            case 'integer':
                return ":" . $argument;
            case 'datetime':
                return sprintf('":%s"', $argument);
            case 'string':
                switch ($this->getFunc()) {
                    case 'eq':
                    case 'ne':
                        return sprintf("':%s'", $argument);
                    default:
                        throw new \Exception('Unknown operator ' . $this->getFunc());
                }
            default:
                var_dump($this->getDBColumn()->getType()->getName());
                var_dump($this);
                die();
        }
        var_dump($filter);
        throw new \Exception(sprintf('Unknown value type \'%s\'.', $filter->getName()));
    }


    public function createQuery(QueryBuilder $queryBuilder, $alias = false) {
        $glueFunc = $this->getGlue() . 'Where';

        $parameterName = $this->generateRandomParameterName();

        $queryBuilder = $queryBuilder->$glueFunc(
            $this->createExpression($queryBuilder, $parameterName, $this->getFunc())
        );

        $queryBuilder->setParameter($parameterName, $this->getFilter());

        return $queryBuilder;
    }

    public function createExpression(QueryBuilder $queryBuilder, $parameterName, $func = null) {
        dump('creating expression'); dump($this);
        $func = is_null($func) ? $this->getFunc() : $func;
        $alias = $this->getTableAlias($this->getEntityName(), $queryBuilder);
        if (!$alias) {
            $expr = $queryBuilder->expr()->$func($this->getDBColumn()->getName(), $this->getFilterConditionValue($parameterName));
            $queryBuilder = $this->createJoin($queryBuilder, $this->getEntityName(), $this->generateRandomParameterName(), $expr);
        } else {
            $expr = $queryBuilder->expr()->$func($alias . '.' . $this->getDBColumn()->getName(), $this->getFilterConditionValue($parameterName));
            if ($alias != 'l') {
                $queryBuilder = $this->AddJoinCondition($queryBuilder, $alias, $expr);

            } else {
                dump('lead restriction');
                $queryBuilder = $queryBuilder->andWhere($expr);
            }
        }

        dump($queryBuilder->getQueryParts()); //die();

        return $queryBuilder;
    }

    public function getDBTable() {
        //@todo cache metadata
        try {
            $tableName = $this->em->getClassMetadata($this->getEntityName())->getTableName();
        } catch (MappingException $e) {
            return $this->getObject();
        }


        return $tableName;
    }

    public function getEntityName() {
        $converter = new CamelCaseToSnakeCaseNameConverter();
        $entity = sprintf('MauticLeadBundle:%s',ucfirst($converter->denormalize($this->getObject())));
        return $entity;
    }

    /**
     * @return Column
     * @throws \Exception
     */
    public function getDBColumn() {
        if (is_null($this->dbColumn)) {
            if($this->getQueryDescription()) {
                $this->dbColumn = $this->schema->listTableColumns($this->getDBTable())[$this->queryDescription['field']];
            } else {
                $dbTableColumns = $this->em->getConnection()->getSchemaManager()->listTableColumns($this->getDBTable());
                if (!$dbTableColumns) {
                    throw new \Exception('Unknown database table and no translation provided for type "' . $this->getType() .'"');
                }
                if (!isset($dbTableColumns[$this->getField()])) {
                    throw new \Exception('Unknown database column and no translation provided for type "' . $this->getType() .'"');
                }
                $this->dbColumn = $dbTableColumns[$this->getField()];
            }
        }

        return $this->dbColumn;
    }

    /**
     * @return string|null
     */
    public function getGlue()
    {
        return $this->glue;
    }

    /**
     * @return string|null
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * @return string|null
     */
    public function getObject()
    {
        return $this->object;
    }

    /**
     * @return bool
     */
    public function isLeadType()
    {
        return $this->object === self::LEAD_OBJECT;
    }

    /**
     * @return bool
     */
    public function isCompanyType()
    {
        return $this->object === self::COMPANY_OBJECT;
    }

    /**
     * @return string|null
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return string|array|null
     */
    public function getFilter()
    {
        return $this->filter;
    }

    /**
     * @return string|null
     */
    public function getDisplay()
    {
        return $this->display;
    }

    /**
     * @return string|null
     */
    public function getOperator()
    {
        return $this->operator;
    }

    /**
     * @param string|null $operator
     */
    public function setOperator($operator)
    {
        $this->operator = $operator;
    }

    /**
     * @param string|array|bool|float|null $filter
     */
    public function setFilter($filter)
    {
        $filter = $this->sanitizeFilter($filter);

        $this->filter = $filter;
    }

    /**
     * @return string
     */
    public function getFunc()
    {
        return $this->func;
    }

    /**
     * @param string $func
     */
    public function setFunc($func)
    {
        $this->func = $func;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'glue'     => $this->getGlue(),
            'field'    => $this->getField(),
            'object'   => $this->getObject(),
            'type'     => $this->getType(),
            'filter'   => $this->getFilter(),
            'display'  => $this->getDisplay(),
            'operator' => $this->getOperator(),
            'func'     => $this->getFunc(),
        ];
    }

    /**
     * @param string|array|bool|float|null $filter
     *
     * @return string|array|bool|float|null
     */
    private function sanitizeFilter($filter)
    {
        if ($filter === null || is_array($filter) || !$this->getType()) {
            return $filter;
        }

        switch ($this->getType()) {
            case 'number':
                $filter = (float)$filter;
                break;

            case 'boolean':
                $filter = (bool)$filter;
                break;
        }

        return $filter;
    }

    /**
     * @return array
     */
    public function getQueryDescription($dictionary = null)
    {
        if (is_null($this->queryDescription)) {
            $this->translateQueryDescription($dictionary);
        }
        return $this->queryDescription;
    }

    /**
     * @param array $queryDescription
     * @return LeadSegmentFilter
     */
    public function setQueryDescription($queryDescription)
    {
        $this->queryDescription = $queryDescription;
        return $this;
    }

    /**
     * @return $this
     */
    public function translateQueryDescription(\ArrayIterator $dictionary = null) {
        $this->queryDescription = isset($dictionary[$this->getField()])
            ? $dictionary[$this->getField()]
            : false;

        return $this;
    }
}
