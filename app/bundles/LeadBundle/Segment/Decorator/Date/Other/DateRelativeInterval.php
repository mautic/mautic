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
    public function __construct(
        private DateDecorator $dateDecorator,
        private $originalValue,
        private DateOptionParameters $dateOptionParameters,
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
        $date = $this->dateOptionParameters->getDefaultDate();
        $date->modify($this->originalValue);

        $operator = $this->getOperator($contactSegmentFilterCrate);
        $format   = 'Y-m-d';
        if ('like' === $operator || 'notLike' === $operator) {
            $format .= '%';
        }

        return $date->toLocalString($format);
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
