<?php

namespace Mautic\LeadBundle\Segment\Decorator;

use Mautic\CoreBundle\Form\Type\AbsoluteRelativeDateFilterType;
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

        return $this->parseAbsoluteDate($filter)
            ?? $this->parseRelativeDate($filter, $operator)
            ?? $this->parseIntervalDate($filter, $operator)
            ?? $filter;
    }

    /**
     * @param array<mixed> $filter
     */
    private function parseAbsoluteDate(array $filter): ?string
    {
        if ($this->isDateFilterType(AbsoluteRelativeDateFilterType::ABSOLUTE_DATE_TYPE, $filter)) {
            return $filter['absoluteDate'];
        }

        return null;
    }

    /**
     * @param array<mixed> $filter
     */
    private function parseRelativeDate(array $filter, ?string $operator): ?string
    {
        $operatorToSign = [
            OperatorOptions::GREATER_THAN          => '+',
            OperatorOptions::GREATER_THAN_OR_EQUAL => '+',
            OperatorOptions::LESS_THAN             => '-',
            OperatorOptions::LESS_THAN_OR_EQUAL    => '-',
        ];

        if ($this->isDateFilterType(AbsoluteRelativeDateFilterType::RELATIVE_DATE_TYPE, $filter)
            && isset($operatorToSign[$operator])
        ) {
            return $operatorToSign[$operator].(int) $filter['relativeDateInterval'].' '.$filter['relativeDateIntervalUnit'];
        }

        return null;
    }

    /**
     * @param array<mixed> $filter
     */
    private function parseIntervalDate(array $filter, ?string $operator): ?string
    {
        $operatorToSign = [
            OperatorOptions::IN_NEXT => '+',
            OperatorOptions::IN_LAST => '-',
        ];

        if (isset($filter['interval']) && isset($filter['unit']) && isset($operatorToSign[$operator])) {
            return $operatorToSign[$operator].(int) $filter['interval'].' '.$filter['unit'];
        }

        return null;
    }

    /**
     * @param array<mixed> $filter
     */
    private function isDateFilterType(string $type, array $filter): bool
    {
        $dateTypeMode = $filter['dateTypeMode'] ?? '';

        return $type === $dateTypeMode;
    }
}
