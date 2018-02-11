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

use Mautic\LeadBundle\Segment\Decorator\FilterDecoratorInterface;
use Mautic\LeadBundle\Segment\Query\Filter\FilterQueryBuilderInterface;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;
use Mautic\LeadBundle\Segment\Query\QueryException;

class LeadSegmentFilter
{
    /**
     * @var LeadSegmentFilterCrate
     */
    public $leadSegmentFilterCrate;

    /**
     * @var FilterDecoratorInterface
     */
    private $filterDecorator;

    /**
     * @var FilterQueryBuilderInterface
     */
    private $filterQueryBuilder;

    /**
     * @var TableSchemaColumnsCache
     */
    private $schemaCache;

    public function __construct(
        LeadSegmentFilterCrate $leadSegmentFilterCrate,
        FilterDecoratorInterface $filterDecorator,
        TableSchemaColumnsCache $cache,
        FilterQueryBuilderInterface $filterQueryBuilder
    ) {
        $this->leadSegmentFilterCrate = $leadSegmentFilterCrate;
        $this->filterDecorator        = $filterDecorator;
        $this->schemaCache            = $cache;
        $this->filterQueryBuilder     = $filterQueryBuilder;
    }

    /**
     * @return \Doctrine\DBAL\Schema\Column
     *
     * @throws QueryException
     */
    public function getColumn()
    {
        $currentDBName = $this->schemaCache->getCurrentDatabaseName();

        $table = preg_replace("/^{$currentDBName}\./", '', $this->getTable());

        $columns = $this->schemaCache->getColumns($table);

        if (!isset($columns[$this->getField()])) {
            throw new QueryException(sprintf('Database schema does not contain field %s.%s', $this->getTable(), $this->getField()));
        }

        return $columns[$this->getField()];
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
     * @todo check where necessary and remove after debugging is done
     *
     * @param null $field
     *
     * @return array|mixed
     *
     * @deprecated
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
     * @return FilterQueryBuilderInterface
     */
    public function getFilterQueryBuilder()
    {
        return $this->filterQueryBuilder;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        if (!is_array($this->getParameterValue())) {
            return sprintf('table:%s field:%s operator:%s holder:%s value:%s, crate:%s',
                           $this->getTable(),
                           $this->getField(),
                           $this->getOperator(),
                           $this->getParameterHolder('holder'),
                           $this->getParameterValue(),
                           print_r($this->getCrate(), true));
        }

        return sprintf('table:%s field:%s holder:%s value:%s, crate: %s',
                       $this->getTable(),
                       $this->getField(),
                       print_r($this->getParameterHolder($this->getParameterValue()), true),
                       print_r($this->getParameterValue(), true),
                       print_r($this->getCrate(), true));
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

    /**
     * @return bool
     */
    public function isContactSegmentReference()
    {
        return $this->getField() === 'leadlist';
    }
}
