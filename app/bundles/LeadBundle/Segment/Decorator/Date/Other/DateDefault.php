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
    public function __construct(private DateDecorator $dateDecorator, private $originalValue)
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
        $filter = $this->originalValue;

        return match ($contactSegmentFilterCrate->getOperator()) {
            'like', '!like' => !str_contains($filter, '%') ? '%'.$filter.'%' : $filter,
            'contains'   => '%'.$filter.'%',
            'startsWith' => $filter.'%',
            'endsWith'   => '%'.$filter,
            default      => $this->originalValue,
        };
    }

    /**
     * @return string
     */
    public function getQueryType(ContactSegmentFilterCrate $contactSegmentFilterCrate)
    {
        return $this->dateDecorator->getQueryType($contactSegmentFilterCrate);
    }

    public function getAggregateFunc(ContactSegmentFilterCrate $contactSegmentFilterCrate): bool|string
    {
        return $this->dateDecorator->getAggregateFunc($contactSegmentFilterCrate);
    }

    public function getWhere(ContactSegmentFilterCrate $contactSegmentFilterCrate): \Mautic\LeadBundle\Segment\Query\Expression\CompositeExpression|string|null
    {
        return $this->dateDecorator->getWhere($contactSegmentFilterCrate);
    }
}
