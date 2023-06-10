<?php

namespace Mautic\LeadBundle\Segment\Decorator\Date\Other;

use Mautic\LeadBundle\Segment\ContactSegmentFilterCrate;
use Mautic\LeadBundle\Segment\Decorator\Date\DateOptionParameters;
use Mautic\LeadBundle\Segment\Decorator\DateDecorator;
use Mautic\LeadBundle\Segment\Decorator\FilterDecoratorInterface;

class DateRelativeInterval implements FilterDecoratorInterface
{
    /**
     * @param string $originalValue
     */
    public function __construct(private DateDecorator $dateDecorator, private $originalValue, private DateOptionParameters $dateOptionParameters)
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
        if ('=' === $contactSegmentFilterCrate->getOperator()) {
            return 'like';
        }
        if ('!=' === $contactSegmentFilterCrate->getOperator()) {
            return 'notLike';
        }

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
        $date = $this->dateOptionParameters->getDefaultDate();
        $date->modify($this->originalValue);

        $operator = $this->getOperator($contactSegmentFilterCrate);
        $format   = 'Y-m-d';
        if ('like' === $operator || 'notLike' === $operator) {
            $format .= '%';
        }

        return $date->toLocalString($format);
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
