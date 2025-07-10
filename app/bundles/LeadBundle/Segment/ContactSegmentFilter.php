<?php

namespace Mautic\LeadBundle\Segment;

use Doctrine\DBAL\Schema\Column;
use Mautic\LeadBundle\Segment\Decorator\ContactDecoratorForeignInterface;
use Mautic\LeadBundle\Segment\Decorator\FilterDecoratorInterface;
use Mautic\LeadBundle\Segment\DoNotContact\DoNotContactParts;
use Mautic\LeadBundle\Segment\Exception\FieldNotFoundException;
use Mautic\LeadBundle\Segment\Exception\TableNotFoundException;
use Mautic\LeadBundle\Segment\IntegrationCampaign\IntegrationCampaignParts;
use Mautic\LeadBundle\Segment\Query\Filter\FilterQueryBuilderInterface;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;

/**
 * Used for accessing $filter as an object and to keep logic in an object.
 */
class ContactSegmentFilter implements \Stringable
{
    /**
     * @var ContactSegmentFilterCrate
     */
    public $contactSegmentFilterCrate;

    /**
     * @param array<string, mixed> $batchLimiters
     */
    public function __construct(
        ContactSegmentFilterCrate $contactSegmentFilterCrate,
        private FilterDecoratorInterface $filterDecorator,
        private TableSchemaColumnsCache $schemaCache,
        private FilterQueryBuilderInterface $filterQueryBuilder,
        private array $batchLimiters = [],
    ) {
        $this->contactSegmentFilterCrate = $contactSegmentFilterCrate;
    }

    /**
     * @return Column
     *
     * @throws FieldNotFoundException
     */
    public function getColumn()
    {
        $currentDBName = $this->schemaCache->getCurrentDatabaseName();

        $table = preg_replace("/^{$currentDBName}\./", '', $this->getTable());

        if (!$table) {
            throw new TableNotFoundException('Segment Query is missing table, perhaps incorrectly registered custom Query Builder. ');
        }

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
     * @return string
     */
    public function getType()
    {
        return $this->contactSegmentFilterCrate->getType();
    }

    /**
     * @return mixed
     */
    public function getTable()
    {
        return $this->filterDecorator->getTable($this->contactSegmentFilterCrate);
    }

    public function getForeignContactColumn(): ?string
    {
        if ($this->filterDecorator instanceof ContactDecoratorForeignInterface) {
            return $this->filterDecorator->getForeignContactColumn($this->contactSegmentFilterCrate);
        } else {
            return 'lead_id';
        }
    }

    /**
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
     * @return string|null
     */
    public function getWhere()
    {
        return $this->filterDecorator->getWhere($this->contactSegmentFilterCrate);
    }

    /**
     * @return string|null
     */
    public function getGlue()
    {
        return $this->contactSegmentFilterCrate->getGlue();
    }

    public function getAggregateFunction(): string|bool
    {
        return $this->filterDecorator->getAggregateFunc($this->contactSegmentFilterCrate);
    }

    public function getFilterQueryBuilder(): FilterQueryBuilderInterface
    {
        return $this->filterQueryBuilder;
    }

    public function applyQuery(QueryBuilder $queryBuilder): QueryBuilder
    {
        return $this->filterQueryBuilder->applyQuery($queryBuilder, $this);
    }

    /**
     * Whether the filter references another ContactSegment.
     */
    public function isContactSegmentReference(): bool
    {
        return 'leadlist' === $this->getField();
    }

    public function isColumnTypeBoolean(): bool
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

    public function getDoNotContactParts(): DoNotContactParts
    {
        return new DoNotContactParts($this->contactSegmentFilterCrate->getField());
    }

    public function getIntegrationCampaignParts(): IntegrationCampaignParts
    {
        return new IntegrationCampaignParts($this->getParameterValue());
    }

    public function __toString(): string
    {
        return sprintf(
            'table: %s,  %s on %s %s %s',
            $this->getTable(),
            $this->getField(),
            $this->getQueryType(),
            $this->getOperator(),
            json_encode($this->getParameterValue())
        );
    }

    /**
     * @return string|null
     */
    public function getRelationJoinTable()
    {
        return method_exists($this->filterDecorator, 'getRelationJoinTable') ? $this->filterDecorator->getRelationJoinTable() : null;
    }

    /**
     * @return string|null
     */
    public function getRelationJoinTableField()
    {
        return method_exists($this->filterDecorator, 'getRelationJoinTableField') ?
            $this->filterDecorator->getRelationJoinTableField() : null;
    }

    public function doesColumnSupportEmptyValue(): bool
    {
        return !in_array($this->contactSegmentFilterCrate->getType(), ['date', 'datetime'], true);
    }

    /**
     * @return array<string, mixed>
     */
    public function getBatchLimiters(): array
    {
        return $this->batchLimiters;
    }
}
