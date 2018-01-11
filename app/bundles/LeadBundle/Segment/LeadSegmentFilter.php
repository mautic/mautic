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
use Doctrine\DBAL\Schema\Column;
use Doctrine\ORM\EntityManager;
use Mautic\LeadBundle\Segment\Decorator\FilterDecoratorInterface;
use Mautic\LeadBundle\Segment\QueryBuilder\BaseFilterQueryBuilder;
use Mautic\LeadBundle\Services\LeadSegmentFilterQueryBuilderTrait;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;

class LeadSegmentFilter
{
    use LeadSegmentFilterQueryBuilderTrait;

    /**
     * @var LeadSegmentFilterCrate
     */
    private $leadSegmentFilterCrate;

    /**
     * @var FilterDecoratorInterface
     */
    private $filterDecorator;

    /**
     * @var BaseFilterQueryBuilder
     */
    private $queryBuilder;

    /**
     * @var BaseDecorator
     */
    private $decorator;
    /**
     * @var array
     */
    private $queryDescription = null;

    /** @var Column */
    private $dbColumn;

    /** @var EntityManager */
    private $em;

    public function __construct(
        LeadSegmentFilterCrate $leadSegmentFilterCrate,
        FilterDecoratorInterface $filterDecorator,
        \ArrayIterator $dictionary = null,
        EntityManager $em = null
    ) {
        $this->leadSegmentFilterCrate = $leadSegmentFilterCrate;
        $this->filterDecorator        = $filterDecorator;
        $this->em                     = $em;
        if (!is_null($dictionary)) {
            $this->translateQueryDescription($dictionary);
        }
    }

    /**
     * @param null $argument
     *
     * @return string
     *
     * @throws \Exception
     */
    public function getFilterConditionValue($argument = null)
    {
        switch ($this->getDBColumn()->getType()->getName()) {
            case 'number':
            case 'integer':
            case 'float':
                return ':'.$argument;
            case 'datetime':
            case 'date':
                return sprintf('":%s"', $argument);
            case 'text':
            case 'string':
                switch ($this->getFunc()) {
                    case 'eq':
                    case 'ne':
                    case 'neq':
                        return sprintf("':%s'", $argument);
                    default:
                        throw new \Exception('Unknown operator '.$this->getFunc());
                }
            default:
                var_dump($this->getDBColumn()->getType()->getName());
                var_dump($this);
                die();
        }
        var_dump($filter);
        throw new \Exception(sprintf('Unknown value type \'%s\'.', $filter->getName()));
    }

    public function createQuery(QueryBuilder $queryBuilder, $alias = false)
    {
        dump('creating query:'.$this->getObject());
        $glueFunc = $this->getGlue().'Where';

        $parameterName = $this->generateRandomParameterName();

        $queryBuilder = $this->createExpression($queryBuilder, $parameterName, $this->getFunc());

        $queryBuilder->setParameter($parameterName, $this->getFilter());

        dump($queryBuilder->getSQL());

        return $queryBuilder;
    }

    public function createExpression(QueryBuilder $queryBuilder, $parameterName, $func = null)
    {
        dump('creating query:'.$this->getField());
        $func  = is_null($func) ? $this->getFunc() : $func;
        $alias = $this->getTableAlias($this->getEntityName(), $queryBuilder);
        $desc  = $this->getQueryDescription();
        if (!$alias) {
            if ($desc['func']) {
                $queryBuilder = $this->createJoin($queryBuilder, $this->getEntityName(), $alias = $this->generateRandomParameterName());
                $expr         = $queryBuilder->expr()->$func($desc['func'].'('.$alias.'.'.$this->getDBColumn()->getName().')', $this->getFilterConditionValue($parameterName));
                $queryBuilder = $queryBuilder->andHaving($expr);
            } else {
                if ($alias != 'l') {
                    $queryBuilder = $this->createJoin($queryBuilder, $this->getEntityName(), $alias = $this->generateRandomParameterName());
                    $expr         = $queryBuilder->expr()->$func($alias.'.'.$this->getDBColumn()->getName(), $this->getFilterConditionValue($parameterName));
                    $queryBuilder = $this->AddJoinCondition($queryBuilder, $alias, $expr);
                } else {
                    dump('lead restriction');
                    $expr = $queryBuilder->expr()->$func($alias.'.'.$this->getDBColumn()->getName(), $this->getFilterConditionValue($parameterName));
                    var_dump($expr);
                    die();
                    $queryBuilder = $queryBuilder->andWhere($expr);
                }
            }
        } else {
            if ($alias != 'l') {
                $expr         = $queryBuilder->expr()->$func($alias.'.'.$this->getDBColumn()->getName(), $this->getFilterConditionValue($parameterName));
                $queryBuilder = $this->AddJoinCondition($queryBuilder, $alias, $expr);
            } else {
                $expr         = $queryBuilder->expr()->$func($alias.'.'.$this->getDBColumn()->getName(), $this->getFilterConditionValue($parameterName));
                $queryBuilder = $queryBuilder->andWhere($expr);
            }
        }

        return $queryBuilder;
    }

    public function getDBTable()
    {
        //@todo cache metadata
        try {
            $tableName = $this->em->getClassMetadata($this->getEntityName())->getTableName();
        } catch (MappingException $e) {
            return $this->getObject();
        }

        return $tableName;
    }

    public function getEntityName()
    {
        $converter = new CamelCaseToSnakeCaseNameConverter();
        if ($this->getQueryDescription()) {
            $table = $this->queryDescription['foreign_table'];
        } else {
            $table = $this->getObject();
        }

        $entity = sprintf('MauticLeadBundle:%s', ucfirst($converter->denormalize($table)));

        return $entity;
    }

    /**
     * @return Column
     *
     * @throws \Exception
     */
    public function getDBColumn()
    {
        if (is_null($this->dbColumn)) {
            if ($descr = $this->getQueryDescription()) {
                $this->dbColumn = $this->em->getConnection()->getSchemaManager()->listTableColumns($this->queryDescription['foreign_table'])[$this->queryDescription['field']];
            } else {
                $dbTableColumns = $this->em->getConnection()->getSchemaManager()->listTableColumns($this->getDBTable());
                if (!$dbTableColumns) {
                    var_dump($this);
                    throw new \Exception('Unknown database table and no translation provided for type "'.$this->getType().'"');
                }
                if (!isset($dbTableColumns[$this->getField()])) {
                    throw new \Exception('Unknown database column and no translation provided for type "'.$this->getType().'"');
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
        return $this->leadSegmentFilterCrate->getGlue();
    }

    /**
     * @return string|null
     */
    public function getField()
    {
        return $this->leadSegmentFilterCrate->getField();
    }

    /**
     * @return string|null
     */
    public function getObject()
    {
        return $this->leadSegmentFilterCrate->getObject();
    }

    /**
     * @return bool
     */
    public function isLeadType()
    {
        return $this->leadSegmentFilterCrate->isLeadType();
    }

    /**
     * @return bool
     */
    public function isCompanyType()
    {
        return $this->leadSegmentFilterCrate->isCompanyType();
    }

    /**
     * @return string|null
     */
    public function getType()
    {
        return $this->leadSegmentFilterCrate->getType();
    }

    /**
     * @return string|array|null
     */
    public function getFilter()
    {
        return $this->leadSegmentFilterCrate->getFilter();
    }

    /**
     * @return string|null
     */
    public function getDisplay()
    {
        return $this->leadSegmentFilterCrate->getDisplay();
    }

    /**
     * @return string|null
     */
    public function getOperator()
    {
        return $this->filterDecorator->getOperator($this->leadSegmentFilterCrate);
    }

    /**
     * @return string
     */
    public function getFunc()
    {
        return $this->leadSegmentFilterCrate->getFunc();
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
     *
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
    public function translateQueryDescription(\ArrayIterator $dictionary = null)
    {
        $this->queryDescription = isset($dictionary[$this->getField()])
            ? $dictionary[$this->getField()]
            : false;

        return $this;
    }

    /**
     * @return BaseFilterQueryBuilder
     */
    public function getQueryBuilder()
    {
        return $this->queryBuilder;
    }

    /**
     * @param BaseFilterQueryBuilder $queryBuilder
     *
     * @return LeadSegmentFilter
     */
    public function setQueryBuilder($queryBuilder)
    {
        $this->queryBuilder = $queryBuilder;

        return $this;
    }
}
