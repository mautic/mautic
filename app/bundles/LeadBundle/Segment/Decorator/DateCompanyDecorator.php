<?php

namespace Mautic\LeadBundle\Segment\Decorator;

use Mautic\LeadBundle\Segment\ContactSegmentFilterCrate;
use Mautic\LeadBundle\Segment\Query\Filter\ComplexRelationValueFilterQueryBuilder;

class DateCompanyDecorator implements FilterDecoratorInterface
{
    public function __construct(
        private FilterDecoratorInterface $dateDecorator,
    ) {
    }

    /**
     * @return string|null
     */
    public function getField(ContactSegmentFilterCrate $contactSegmentFilterCrate)
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
     *
     * @return array|string
     */
    public function getParameterHolder(ContactSegmentFilterCrate $contactSegmentFilterCrate, $argument)
    {
        return $this->dateDecorator->getParameterHolder($contactSegmentFilterCrate, $argument);
    }

    /**
     * @return array|bool|float|string|null
     */
    public function getParameterValue(ContactSegmentFilterCrate $contactSegmentFilterCrate): mixed
    {
        return $this->dateDecorator->getParameterValue($contactSegmentFilterCrate);
    }

    public function getQueryType(ContactSegmentFilterCrate $contactSegmentFilterCrate): string
    {
        return ComplexRelationValueFilterQueryBuilder::getServiceId();
    }

    public function getAggregateFunc(ContactSegmentFilterCrate $contactSegmentFilterCrate): string|bool
    {
        return $this->dateDecorator->getAggregateFunc($contactSegmentFilterCrate);
    }

    /**
     * @return \Doctrine\DBAL\Query\Expression\CompositeExpression|string|null
     */
    public function getWhere(ContactSegmentFilterCrate $contactSegmentFilterCrate)
    {
        return $this->dateDecorator->getWhere($contactSegmentFilterCrate);
    }

    public function getRelationJoinTable(): string
    {
        return MAUTIC_TABLE_PREFIX.'companies_leads';
    }

    public function getRelationJoinTableField(): string
    {
        return 'company_id';
    }
}
