<?php

namespace Mautic\LeadBundle\Segment\Decorator;

trait ParseDateFilterValueTrait
{
    /**
     * @param string|array<mixed>|null $filter
     *
     * @return string|array<mixed>|null
     */
    private function parseDateFilterValue(null|string|array $filter): null|string|array
    {
        if (!is_array($filter)) {
            return $filter;
        }

        if (!isset($filter['dateTypeMode'])) {
            return $filter;
        }

        if ('absolute' === $filter['dateTypeMode']) {
            $filterVal = $filter['absoluteDate'];
        } else {
            $filterVal = (int) $filter['relativeDateInterval'].' '.$filter['relativeDateIntervalUnit'];
        }

        return $filterVal;
    }
}
