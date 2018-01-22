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

use Doctrine\ORM\EntityManager;
use Mautic\LeadBundle\Segment\Decorator\FilterDecoratorInterface;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;
use Mautic\LeadBundle\Services\LeadSegmentFilterQueryBuilderTrait;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;

class LeadSegmentFilter
{
    use LeadSegmentFilterQueryBuilderTrait;

    /**
     * @var LeadSegmentFilterCrate
     */
    public $leadSegmentFilterCrate;

    /**
     * @var FilterDecoratorInterface
     */
    private $filterDecorator;

    /**
     * @var BaseFilterQueryBuilder
     */
    private $filterQueryBuilder;

    /**
     * @var EntityManager
     */
    private $em;

    public function __construct(
        LeadSegmentFilterCrate $leadSegmentFilterCrate,
        FilterDecoratorInterface $filterDecorator,
        EntityManager $em = null
    ) {
        $this->leadSegmentFilterCrate = $leadSegmentFilterCrate;
        $this->filterDecorator        = $filterDecorator;
        $this->em                     = $em;
    }

    /**
     * @return \Doctrine\DBAL\Schema\Column
     *
     * @throws \Exception
     */
    public function getColumn()
    {
        $columns = $this->em->getConnection()->getSchemaManager()->listTableColumns($this->getTable());
        if (!isset($columns[$this->getField()])) {
            throw new \Exception(sprintf('Database schema does not contain field %s.%s', $this->getTable(), $this->getField()));
        }

        return $columns[$this->getField()];
    }

    /**
     * @return string
     *
     * @deprecated This function might be not used at all
     */
    public function getEntity()
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
     * @return string
     */
    public function getQueryType()
    {
        return $this->filterDecorator->getQueryType($this->leadSegmentFilterCrate);
    }

    /**
     * @return string|null
     */
    public function getOperator()
    {
        return $this->filterDecorator->getOperator($this->leadSegmentFilterCrate);
    }

    /**
     * @return mixed
     */
    public function getField()
    {
        return $this->filterDecorator->getField($this->leadSegmentFilterCrate);
    }

    /**
     * @return mixed
     */
    public function getTable()
    {
        return $this->filterDecorator->getTable($this->leadSegmentFilterCrate);
    }

    /**
     * @param $argument
     *
     * @return mixed
     */
    public function getParameterHolder($argument)
    {
        return $this->filterDecorator->getParameterHolder($this->leadSegmentFilterCrate, $argument);
    }

    /**
     * @return mixed
     */
    public function getParameterValue()
    {
        return $this->filterDecorator->getParameterValue($this->leadSegmentFilterCrate);
    }

    /**
     * @return null|string
     */
    public function getGlue()
    {
        return $this->leadSegmentFilterCrate->getGlue();
    }

    /**
     * @return mixed
     */
    public function getAggregateFunction()
    {
        return $this->filterDecorator->getAggregateFunc($this->leadSegmentFilterCrate);
    }

    /**
     * @todo check whether necessary and replace or remove
     *
     * @param null $field
     *
     * @return array|mixed
     *
     * @throws \Exception
     */
    public function getCrate($field = null)
    {
        $fields = (array) $this->toArray();

        if (is_null($field)) {
            return $fields;
        }

        if (isset($fields[$field])) {
            return $fields[$field];
        }

        throw new \Exception('Unknown crate field "'.$field."'");
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'glue'     => $this->leadSegmentFilterCrate->getGlue(),
            'field'    => $this->leadSegmentFilterCrate->getField(),
            'object'   => $this->leadSegmentFilterCrate->getObject(),
            'type'     => $this->leadSegmentFilterCrate->getType(),
            'filter'   => $this->leadSegmentFilterCrate->getFilter(),
            'display'  => $this->leadSegmentFilterCrate->getDisplay(),
            'operator' => $this->leadSegmentFilterCrate->getOperator(),
            'func'     => $this->leadSegmentFilterCrate->getFunc(),
            'aggr'     => $this->getAggregateFunction(),
        ];
    }

    /**
     * @return BaseFilterQueryBuilder
     */
    public function getFilterQueryBuilder()
    {
        return $this->filterQueryBuilder;
    }

    /**
     * @param BaseFilterQueryBuilder $filterQueryBuilder
     *
     * @return LeadSegmentFilter
     */
    public function setFilterQueryBuilder($filterQueryBuilder)
    {
        $this->filterQueryBuilder = $filterQueryBuilder;

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        if (!is_array($this->getParameterValue())) {
            return sprintf('table:%s field:%s holder:%s value:%s', $this->getTable(), $this->getField(), $this->getParameterHolder('holder'), $this->getParameterValue());
        }

        return sprintf('table:%s field:%s holder:%s value:%s', $this->getTable(), $this->getField(), print_r($this->getParameterHolder($this->getParameterValue()), true), print_r($this->getParameterValue(), true));
    }

    /**
     * @param QueryBuilder $queryBuilder
     *
     * @return QueryBuilder
     */
    public function applyQuery(QueryBuilder $queryBuilder)
    {
        return $this->filterQueryBuilder->applyQuery($queryBuilder, $this);
    }
}
