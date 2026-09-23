<?php

namespace Mautic\LeadBundle\Segment\Decorator\Date\Other;

use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Mautic\LeadBundle\Segment\ContactSegmentFilterCrate;
use Mautic\LeadBundle\Segment\Decorator\Date\DateOptionParameters;
use Mautic\LeadBundle\Segment\Decorator\DateDecorator;
use Mautic\LeadBundle\Segment\Decorator\FilterDecoratorInterface;
use Mautic\LeadBundle\Segment\Decorator\ParseDateFilterValueTrait;
use Mautic\LeadBundle\Segment\OperatorOptions;

final class DateRelativeInterval implements FilterDecoratorInterface
{
    use ParseDateFilterValueTrait;

    /**
     * @param string $originalValue
     */
    public function __construct(
        private readonly DateDecorator $dateDecorator,
        private $originalValue,
        private readonly DateOptionParameters $dateOptionParameters,
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
     */
    public function getParameterHolder(ContactSegmentFilterCrate $contactSegmentFilterCrate, $argument): string|array
    {
        return $this->dateDecorator->getParameterHolder($contactSegmentFilterCrate, $argument);
    }

    public function getParameterValue(ContactSegmentFilterCrate $contactSegmentFilterCrate): mixed
    {
        $date = $this->dateOptionParameters->getDefaultDate();
        $date->modify($this->parseDateFilterValue(
            $this->originalValue,
            $contactSegmentFilterCrate->getOperator())
        );

        $operator = $this->getOperator($contactSegmentFilterCrate);
        $format   = 'Y-m-d';

        $isLikeOperator = 'like' === $operator || 'notLike' === $operator;
        if (!$isLikeOperator && $contactSegmentFilterCrate->hasTimeParts() && !in_array($operator, [OperatorOptions::IN_LAST, OperatorOptions::IN_NEXT])) {
            $format .= ' H:i:s';
        }
        if ($isLikeOperator) {
            $format .= '%';
        }
        if (!$contactSegmentFilterCrate->hasTimeParts() && 'gt' === $operator) {
            $format .= ' 23:59:59';
        }

        if (OperatorOptions::IN_NEXT === $operator) {
            $format .= ' 23:59:59';
        } elseif (OperatorOptions::IN_LAST === $operator) {
            $format .= ' 00:00:00';
        }

        return $date->toUtcString($format);
    }

    public function getQueryType(ContactSegmentFilterCrate $contactSegmentFilterCrate): string
    {
        return $this->dateDecorator->getQueryType($contactSegmentFilterCrate);
    }

    public function getAggregateFunc(ContactSegmentFilterCrate $contactSegmentFilterCrate): string|bool
    {
        return $this->dateDecorator->getAggregateFunc($contactSegmentFilterCrate);
    }

    public function getWhere(ContactSegmentFilterCrate $contactSegmentFilterCrate): CompositeExpression|string|null
    {
        return $this->dateDecorator->getWhere($contactSegmentFilterCrate);
    }
}
