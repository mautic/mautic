<?php

namespace Mautic\LeadBundle\Segment\Decorator;

use Mautic\LeadBundle\Segment\ContactSegmentFilterCrate;
use Mautic\LeadBundle\Segment\Query\Filter\ComplexRelationValueFilterQueryBuilder;

/**
 * Class DateCompanyDecorator.
 */
class DateCompanyDecorator implements FilterDecoratorInterface
{
    public function __construct(private FilterDecoratorInterface $dateDecorator)
    {
    }

    public function getField(ContactSegmentFilterCrate $contactSegmentFilterCrate): ?string
    {
        return $this->dateDecorator->getField($contactSegmentFilterCrate);
    }

    /**
     * @return string
     */
    public function getTable(ContactSegmentFilterCrate $contactSegmentFilterCrate)
    {
        return $this->dateDecorator->getTable($contactSegmentFilterCrate);
    }

    /**
     * @return string
     */
    public function getOperator(ContactSegmentFilterCrate $contactSegmentFilterCrate)
    {
        return $this->dateDecorator->getOperator($contactSegmentFilterCrate);
    }

    /**
     * @param array|string $argument
     */
    public function getParameterHolder(ContactSegmentFilterCrate $contactSegmentFilterCrate, $argument): array|string
    {
        return $this->dateDecorator->getParameterHolder($contactSegmentFilterCrate, $argument);
    }

    public function getParameterValue(ContactSegmentFilterCrate $contactSegmentFilterCrate): array|bool|float|string|null
    {
        return $this->dateDecorator->getParameterValue($contactSegmentFilterCrate);
    }

    /**
     * @return string
     */
    public function getQueryType(ContactSegmentFilterCrate $contactSegmentFilterCrate)
    {
        return ComplexRelationValueFilterQueryBuilder::getServiceId();
    }

    public function getAggregateFunc(ContactSegmentFilterCrate $contactSegmentFilterCrate): bool|string
    {
        return $this->dateDecorator->getAggregateFunc($contactSegmentFilterCrate);
    }

    public function getWhere(ContactSegmentFilterCrate $contactSegmentFilterCrate): \Mautic\LeadBundle\Segment\Query\Expression\CompositeExpression|string|null
    {
        return $this->dateDecorator->getWhere($contactSegmentFilterCrate);
    }

    /**
     * @return string
     */
    public function getRelationJoinTable()
    {
        return MAUTIC_TABLE_PREFIX.'companies_leads';
    }

    /**
     * @return string
     */
    public function getRelationJoinTableField()
    {
        return 'company_id';
    }
}
