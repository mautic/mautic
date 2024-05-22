<?php

namespace Mautic\LeadBundle\Segment\Decorator;

use Mautic\LeadBundle\Form\Type\SegmentDateFilterType;
use Mautic\LeadBundle\Segment\OperatorOptions;

trait ParseDateFilterValueTrait
{
    /**
     * @param string|array<mixed>|bool|null $filter
     *
     * @return string|array<mixed>|null
     */
    private function parseDateFilterValue($filter, ?string $operator)
    {
        if (!is_array($filter)) {
            return $filter;
        }

        if (!isset($filter['dateTypeMode'])) {
            return $filter;
        }

        $filterVal = '';

        if (SegmentDateFilterType::ABSOLUTE_DATE_TYPE === $filter['dateTypeMode']) {
            $filterVal = $filter['absoluteDate'];
        }

        if (SegmentDateFilterType::RELATIVE_DATE_TYPE === $filter['dateTypeMode']) {
            if (in_array($operator, [OperatorOptions::GREATER_THAN, OperatorOptions::GREATER_THAN_OR_EQUAL])) {
                $filterVal = '+'.(int) $filter['relativeDateInterval'].' '.$filter['relativeDateIntervalUnit'];
            } elseif (in_array($operator, [OperatorOptions::LESS_THAN, OperatorOptions::LESS_THAN_OR_EQUAL])) {
                $filterVal = '-'.(int) $filter['relativeDateInterval'].' '.$filter['relativeDateIntervalUnit'];
            }
        }

        return $filterVal;
    }
}
