<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Segment\Decorator;

use Mautic\LeadBundle\Segment\OperatorOptions;

trait ParseDateFilterValueTrait
{
    /**
     * @param string|array<mixed>|bool|null $filter
     *
     * @return string|array<mixed>|null
     */
    private function parseDateFilterValue($filter, ?string $operator): mixed
    {
        if (!is_array($filter)) {
            return $filter;
        }

        return $this->parseIntervalDate($filter, $operator)
            ?? $filter;
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
}
