<?php

namespace Mautic\LeadBundle\Segment\Decorator\Date\Other;

use Mautic\LeadBundle\Segment\ContactSegmentFilterCrate;
use Mautic\LeadBundle\Segment\Decorator\DateDecorator;
use Mautic\LeadBundle\Segment\Decorator\FilterDecoratorInterface;

class DateDefault implements FilterDecoratorInterface
{
    /**
     * @param string $originalValue
     */
    public function __construct(
        private DateDecorator $dateDecorator,
        private $originalValue,
    ) {
    }

    /**
     * @return string|null
     */
    public function getField(ContactSegmentFilterCrate $contactSegmentFilterCrate)
    {
        return $this->dateDecorator->getField($contactSegmentFilterCrate);
    }

    public function getTable(ContactSegmentFilterCrate $contactSegmentFilterCrate): string
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
        $filter = $this->originalValue;

        return match ($contactSegmentFilterCrate->getOperator()) {
            'like', '!like' => !str_contains($filter, '%') ? '%'.$filter.'%' : $filter,
            'contains'   => '%'.$filter.'%',
            'startsWith' => $filter.'%',
            'endsWith'   => '%'.$filter,
            default      => $this->originalValue,
        };
    }

    public function getQueryType(ContactSegmentFilterCrate $contactSegmentFilterCrate): string
    {
        return $this->dateDecorator->getQueryType($contactSegmentFilterCrate);
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
}
