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

use Doctrine\DBAL\Schema\Column;
use Doctrine\ORM\EntityManager;
use Mautic\LeadBundle\Segment\Decorator\BaseDecorator;
use Mautic\LeadBundle\Segment\Decorator\FilterDecoratorInterface;
use Mautic\LeadBundle\Segment\FilterQueryBuilder\BaseFilterQueryBuilder;
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
     * @var FilterDecoratorInterface|BaseDecorator
     */
    private $filterDecorator;

    /**
     * @var BaseFilterQueryBuilder
     */
    private $filterQueryBuilder;

    /** @var Column */
    private $dbColumn;

    public function __construct(
        LeadSegmentFilterCrate $leadSegmentFilterCrate,
        FilterDecoratorInterface $filterDecorator,
        EntityManager $em = null
    ) {
        $this->leadSegmentFilterCrate = $leadSegmentFilterCrate;
        $this->filterDecorator        = $filterDecorator;
        $this->em                     = $em;
    }

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

    public function getField()
    {
        return $this->filterDecorator->getField($this->leadSegmentFilterCrate);
    }

    public function getTable()
    {
        return $this->filterDecorator->getTable($this->leadSegmentFilterCrate);
    }

    public function getParameterHolder($argument)
    {
        return $this->filterDecorator->getParameterHolder($this->leadSegmentFilterCrate, $argument);
    }

    public function getParameterValue()
    {
        return $this->filterDecorator->getParameterValue($this->leadSegmentFilterCrate);
    }

    public function getGlue()
    {
        return $this->leadSegmentFilterCrate->getGlue();
    }

    public function getAggregateFunction()
    {
        return $this->filterDecorator->getAggregateFunc($this->leadSegmentFilterCrate);
    }

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
        return sprintf('%s %s', $this->getTable(), $this->getField());
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
