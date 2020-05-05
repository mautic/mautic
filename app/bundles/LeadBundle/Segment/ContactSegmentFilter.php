<?php
/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Segment;

use Mautic\LeadBundle\Segment\Decorator\FilterDecoratorInterface;
use Mautic\LeadBundle\Segment\DoNotContact\DoNotContactParts;
use Mautic\LeadBundle\Segment\Exception\FieldNotFoundException;
use Mautic\LeadBundle\Segment\IntegrationCampaign\IntegrationCampaignParts;
use Mautic\LeadBundle\Segment\Query\Filter\FilterQueryBuilderInterface;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;

/**
 * Class ContactSegmentFilter is used for accessing $filter as an object and to keep logic in an object.
 */
class ContactSegmentFilter
{
    /**
     * @var ContactSegmentFilterCrate
     */
    public $contactSegmentFilterCrate;

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

    /**
     * @param ContactSegmentFilterCrate   $contactSegmentFilterCrate
     * @param FilterDecoratorInterface    $filterDecorator
     * @param TableSchemaColumnsCache     $cache
     * @param FilterQueryBuilderInterface $filterQueryBuilder
     */
    public function __construct(
        ContactSegmentFilterCrate $contactSegmentFilterCrate,
        FilterDecoratorInterface $filterDecorator,
        TableSchemaColumnsCache $cache,
        FilterQueryBuilderInterface $filterQueryBuilder
    ) {
        $this->contactSegmentFilterCrate = $contactSegmentFilterCrate;
        $this->filterDecorator           = $filterDecorator;
        $this->schemaCache               = $cache;
        $this->filterQueryBuilder        = $filterQueryBuilder;
    }

    /**
     * @return \Doctrine\DBAL\Schema\Column
     *
     * @throws FieldNotFoundException
     */
    public function getColumn()
    {
        $currentDBName = $this->schemaCache->getCurrentDatabaseName();

        $table = preg_replace("/^{$currentDBName}\./", '', $this->getTable());

        $columns = $this->schemaCache->getColumns($table);

        if (!isset($columns[$this->getField()])) {
            throw new FieldNotFoundException(sprintf('Database schema does not contain field %s.%s', $this->getTable(), $this->getField()));
        }

        return $columns[$this->getField()];
    }

    /**
     * @return string
     */
    public function getQueryType()
    {
        return $this->filterDecorator->getQueryType($this->contactSegmentFilterCrate);
    }

    /**
     * @return string|null
     */
    public function getOperator()
    {
        return $this->filterDecorator->getOperator($this->contactSegmentFilterCrate);
    }

    /**
     * @return mixed
     */
    public function getField()
    {
        return $this->filterDecorator->getField($this->contactSegmentFilterCrate);
    }

    /**
     * @return mixed
     */
    public function getTable()
    {
        return $this->filterDecorator->getTable($this->contactSegmentFilterCrate);
    }

    /**
     * @param $argument
     *
     * @return mixed
     */
    public function getParameterHolder($argument)
    {
        return $this->filterDecorator->getParameterHolder($this->contactSegmentFilterCrate, $argument);
    }

    /**
     * @return mixed
     */
    public function getParameterValue()
    {
        return $this->filterDecorator->getParameterValue($this->contactSegmentFilterCrate);
    }

    /**
     * @return null|string
     */
    public function getWhere()
    {
        return $this->filterDecorator->getWhere($this->contactSegmentFilterCrate);
    }

    /**
     * @return null|string
     */
    public function getGlue()
    {
        return $this->contactSegmentFilterCrate->getGlue();
    }

    /**
     * @return mixed
     */
    public function getAggregateFunction()
    {
        return $this->filterDecorator->getAggregateFunc($this->contactSegmentFilterCrate);
    }

    /**
     * @return FilterQueryBuilderInterface
     */
    public function getFilterQueryBuilder()
    {
        return $this->filterQueryBuilder;
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
     * Whether the filter references another ContactSegment.
     *
     * @return bool
     */
    public function isContactSegmentReference()
    {
        return $this->getField() === 'leadlist';
    }

    /**
     * @return bool
     */
    public function isColumnTypeBoolean()
    {
        return $this->contactSegmentFilterCrate->isBooleanType();
    }

    /**
     * @return mixed
     */
    public function getNullValue()
    {
        return $this->contactSegmentFilterCrate->getNullValue();
    }

    /**
     * @return DoNotContactParts
     */
    public function getDoNotContactParts()
    {
        return new DoNotContactParts($this->contactSegmentFilterCrate->getField());
    }

    /**
     * @return IntegrationCampaignParts
     */
    public function getIntegrationCampaignParts()
    {
        return new IntegrationCampaignParts($this->getParameterValue());
    }

    public function __toString()
    {
        $debug = sprintf(
            'table: %s,  %s on %s %s %s',
                $this->getTable(),
            $this->getField(),
            $this->getQueryType(),
            $this->getOperator(),
            json_encode($this->getParameterValue())
        );

        return $debug;
    }

    public function getRelationJoinTable()
    {
        return method_exists($this->filterDecorator, 'getRelationJoinTable') ? $this->filterDecorator->getRelationJoinTable() : null;
    }

    public function getRelationJoinTableField()
    {
        return method_exists($this->filterDecorator, 'getRelationJoinTableField') ?
            $this->filterDecorator->getRelationJoinTableField() : null;
    }
}
